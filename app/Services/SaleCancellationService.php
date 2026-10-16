<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleCancellationService
{
    public function __construct(private readonly StockLedger $stockLedger) {}

    public function cancel(Sale $sale, string $reason, User $admin): Sale
    {
        return DB::transaction(function () use ($sale, $reason, $admin): Sale {
            $lockedSale = Sale::query()->with('items')->whereKey($sale->id)->lockForUpdate()->firstOrFail();
            if ($lockedSale->status !== 'completed' || $lockedSale->cancelled_at !== null) {
                throw ValidationException::withMessages(['sale' => 'Transaksi ini sudah dibatalkan atau tidak dapat dibatalkan.']);
            }

            foreach ($lockedSale->items as $item) {
                if ($item->product_id === null) {
                    continue;
                }

                $productType = $item->product_type ?: $item->product?->type;
                if ($productType !== 'barang') {
                    continue;
                }

                $this->stockLedger->move(
                    productId: (int) $item->product_id,
                    direction: 'in',
                    quantity: (float) $item->quantity,
                    movementType: 'sale_return',
                    referenceType: Sale::class,
                    referenceId: $lockedSale->id,
                    note: "Retur pembatalan {$lockedSale->number}: ".trim($reason),
                );
            }

            $lockedSale->forceFill([
                'status' => 'cancelled',
                'cancelled_at' => now(config('app.timezone')),
                'cancelled_by' => $admin->id,
                'cancellation_reason' => trim($reason),
            ])->save();

            ActivityLogger::log('sale.cancelled', Sale::class, $lockedSale->id, "Transaksi {$lockedSale->number} dibatalkan dan stok barang dikembalikan.", [
                'reason' => trim($reason),
                'total' => (int) $lockedSale->total,
            ], $admin->id);

            return $lockedSale->load(['items', 'user', 'cancelledBy']);
        }, attempts: 3);
    }
}
