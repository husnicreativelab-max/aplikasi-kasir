<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockIn;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;

class StockInService
{
    public function __construct(private readonly StockLedger $stockLedger) {}

    /** @param array<string, mixed> $data */
    public function receive(array $data, User $user): StockIn
    {
        return DB::transaction(function () use ($data, $user): StockIn {
            $product = Product::query()
                ->available()
                ->goods()
                ->whereKey((int) $data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $quantity = round((float) $data['quantity'], 3);
            $purchasePrice = (int) $data['purchase_price'];
            $totalCost = (int) round($quantity * $purchasePrice);
            $receivedAt = now(config('app.timezone'));

            $stockIn = StockIn::query()->create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'quantity' => $quantity,
                'purchase_price' => $purchasePrice,
                'total_cost' => $totalCost,
                'note' => $data['note'] ?? null,
                'received_at' => $receivedAt,
            ]);

            // Simpan harga beli terbaru untuk transaksi berikutnya dan laporan laba.
            $product->forceFill(['purchase_price' => $purchasePrice])->save();

            $this->stockLedger->move(
                productId: $product->id,
                direction: 'in',
                quantity: $quantity,
                movementType: 'stock_in',
                referenceType: StockIn::class,
                referenceId: $stockIn->id,
                note: $stockIn->note ?: "Barang masuk #{$stockIn->id}",
            );

            ActivityLogger::log('stock_in.created', StockIn::class, $stockIn->id, "Barang masuk {$product->name} dicatat.", [
                'quantity' => $quantity,
                'purchase_price' => $purchasePrice,
                'total_cost' => $totalCost,
            ], $user->id);

            return $stockIn->load(['product', 'user']);
        }, attempts: 3);
    }
}
