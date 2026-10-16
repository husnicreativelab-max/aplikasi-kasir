<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        private readonly StockLedger $stockLedger,
        private readonly DocumentNumberService $documentNumbers,
    ) {}

    /** @param array<string, mixed> $data */
    public function checkout(array $data, User $cashier): Sale
    {
        return DB::transaction(function () use ($data, $cashier): Sale {
            $requestedItems = collect($data['items'] ?? [])
                ->groupBy(fn (array $item) => (int) $item['product_id'])
                ->map(fn ($items) => round($items->sum(fn (array $item) => (float) $item['quantity']), 3));

            if ($requestedItems->isEmpty() || $requestedItems->contains(fn (float $quantity) => $quantity <= 0)) {
                throw ValidationException::withMessages(['items' => 'Keranjang masih kosong atau jumlah produk tidak valid.']);
            }

            $products = Product::query()
                ->available()
                ->whereIn('id', $requestedItems->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $requestedItems->count()) {
                throw ValidationException::withMessages(['items' => 'Ada produk yang sudah tidak aktif. Muat ulang halaman Kasir.']);
            }

            $subtotal = 0;
            $lines = [];

            foreach ($requestedItems as $productId => $quantity) {
                /** @var Product $product */
                $product = $products->get((int) $productId);
                $lineTotal = (int) round((float) $product->selling_price * (float) $quantity);
                $subtotal += $lineTotal;
                $lines[] = [
                    'product' => $product,
                    'quantity' => (float) $quantity,
                    'line_total' => $lineTotal,
                ];
            }

            $discountType = $data['discount_type'] ?? 'nominal';
            $discountValue = (float) ($data['discount_value'] ?? 0);
            if ($discountValue < 0) {
                throw ValidationException::withMessages(['discount_value' => 'Nilai diskon tidak boleh negatif.']);
            }
            if ($discountType === 'none') {
                $discountValue = 0;
            }
            $discountTotal = match ($discountType) {
                'persen' => (int) round($subtotal * min($discountValue, 100) / 100),
                'nominal' => (int) round($discountValue),
                'none' => 0,
                default => throw ValidationException::withMessages(['discount_type' => 'Jenis diskon tidak valid.']),
            };

            if ($discountType === 'persen' && ($discountValue < 0 || $discountValue > 100)) {
                throw ValidationException::withMessages(['discount_value' => 'Diskon persen harus antara 0 dan 100.']);
            }

            if ($discountTotal > $subtotal) {
                throw ValidationException::withMessages(['discount_value' => 'Diskon tidak boleh melebihi subtotal.']);
            }

            $total = max(0, $subtotal - $discountTotal);
            $paymentMethod = $data['payment_method'];
            $amountPaid = $paymentMethod === 'tunai' ? (int) ($data['amount_paid'] ?? 0) : $total;

            if ($amountPaid < $total) {
                throw ValidationException::withMessages(['amount_paid' => 'Jumlah pembayaran masih kurang dari total.']);
            }

            $prefix = strtoupper((string) Setting::valueOf('invoice_prefix', 'INV'));
            $sale = Sale::query()->create([
                'number' => $this->documentNumbers->next($prefix),
                'user_id' => $cashier->id,
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_total' => $discountTotal,
                'total' => $total,
                'payment_method' => $paymentMethod,
                'amount_paid' => $amountPaid,
                'change_due' => $paymentMethod === 'tunai' ? $amountPaid - $total : 0,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'sold_at' => now(config('app.timezone')),
            ]);

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $line['product'];
                SaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_type' => $product->type,
                    'unit' => $product->unit,
                    'barcode' => $product->barcode,
                    'quantity' => $line['quantity'],
                    'unit_price' => $product->selling_price,
                    'purchase_price' => $product->purchase_price,
                    'line_total' => $line['line_total'],
                ]);

                if ($product->type === 'barang') {
                    $this->stockLedger->move(
                        productId: $product->id,
                        direction: 'out',
                        quantity: $line['quantity'],
                        movementType: 'sale',
                        referenceType: Sale::class,
                        referenceId: $sale->id,
                        note: "Penjualan {$sale->number}",
                    );
                }
            }

            ActivityLogger::log('sale.created', Sale::class, $sale->id, "Transaksi {$sale->number} selesai.", [
                'total' => $total,
                'payment_method' => $paymentMethod,
            ], $cashier->id);

            return $sale->load(['items', 'user']);
        }, attempts: 3);
    }
}
