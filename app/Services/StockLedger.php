<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class StockLedger
{
    /**
     * Adjust a product's stock and append its immutable movement record.
     * Call this inside the same database transaction as the business operation.
     */
    public function move(
        int $productId,
        string $direction,
        float $quantity,
        string $movementType,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $note = null,
    ): Product {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('Perubahan stok harus berada di dalam DB transaction.');
        }

        $quantity = round($quantity, 3);
        if (! in_array($direction, ['in', 'out'], true) || $quantity <= 0) {
            throw new LogicException('Arah dan jumlah perubahan stok tidak valid.');
        }

        $product = Product::query()->whereKey($productId)->lockForUpdate()->firstOrFail();

        if ($product->type !== 'barang') {
            throw ValidationException::withMessages([
                'items' => "Produk jasa \"{$product->name}\" tidak memiliki stok barang.",
            ]);
        }

        $before = (float) $product->stock;
        $after = round($before + ($direction === 'in' ? $quantity : -$quantity), 3);
        $allowNegative = filter_var(Setting::valueOf('allow_negative_stock', '0'), FILTER_VALIDATE_BOOLEAN);

        if ($after < -0.0005 && ! $allowNegative) {
            throw ValidationException::withMessages([
                'items' => "Stok {$product->name} tidak cukup. Tersedia {$this->formatQuantity($before)} {$product->unit}.",
            ]);
        }

        $product->forceFill(['stock' => $after])->save();

        StockMovement::query()->create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'movement_type' => $movementType,
            'direction' => $direction,
            'quantity' => $quantity,
            'stock_before' => $before,
            'stock_after' => $after,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'note' => $note,
        ]);

        $stockVerb = $direction === 'in' ? 'bertambah' : 'berkurang';
        ActivityLogger::log(
            'stock.moved',
            Product::class,
            $product->id,
            "Stok {$product->name} {$stockVerb}.",
            ['movement_type' => $movementType, 'direction' => $direction, 'quantity' => $quantity, 'before' => $before, 'after' => $after],
        );

        return $product;
    }

    private function formatQuantity(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 3, ',', '.'), '0'), ',');
    }
}
