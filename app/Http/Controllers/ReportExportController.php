<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function sales(Request $request): StreamedResponse
    {
        $filters = $this->dateRange($request, [
            'payment_method' => ['nullable', Rule::in(['tunai', 'transfer', 'qris'])],
        ]);
        $filters['payment_method'] = $filters['payment_method'] ?? null;

        return $this->csvDownload(
            "laporan-penjualan-{$filters['fromDate']}-{$filters['toDate']}.csv",
            function ($stream) use ($filters): void {
                $this->writeCsvRow($stream, [
                    'Nomor transaksi', 'Tanggal', 'Kasir', 'Status', 'Metode pembayaran',
                    'Nama produk/jasa', 'Barcode', 'Tipe', 'Satuan', 'Jumlah', 'Harga jual per unit',
                    'Modal per unit (khusus Admin)', 'Total baris', 'Laba kotor baris sebelum diskon',
                    'Subtotal transaksi', 'Diskon transaksi', 'Total transaksi', 'Dibayar', 'Kembalian',
                ]);

                $rows = DB::table('sale_items as item')
                    ->join('sales as sale', 'sale.id', '=', 'item.sale_id')
                    ->leftJoin('users as cashier', 'cashier.id', '=', 'sale.user_id')
                    ->where('sale.status', 'completed')
                    ->whereBetween('sale.sold_at', [$filters['from'], $filters['to']])
                    ->when($filters['payment_method'], fn (Builder $query, string $method) => $query->where('sale.payment_method', $method))
                    ->select([
                        'sale.number as sale_number', 'sale.sold_at', 'sale.status', 'sale.payment_method',
                        'sale.subtotal', 'sale.discount_total', 'sale.total as sale_total',
                        'sale.amount_paid', 'sale.change_due', 'cashier.name as cashier_name',
                        'item.product_name', 'item.barcode', 'item.product_type', 'item.unit',
                        'item.quantity', 'item.unit_price', 'item.purchase_price', 'item.line_total',
                    ])
                    ->orderBy('sale.sold_at')
                    ->orderBy('sale.id')
                    ->orderBy('item.id')
                    ->cursor();

                $lastSaleNumber = null;
                foreach ($rows as $row) {
                    $quantity = (float) $row->quantity;
                    $grossProfit = (int) $row->line_total - (int) round((int) $row->purchase_price * $quantity);
                    $isFirstItem = $row->sale_number !== $lastSaleNumber;

                    $this->writeCsvRow($stream, [
                        $row->sale_number,
                        $this->localDateTime($row->sold_at),
                        $row->cashier_name ?: 'Akun tidak aktif',
                        'Selesai',
                        $this->paymentLabel($row->payment_method),
                        $row->product_name,
                        $row->barcode,
                        $row->product_type,
                        $row->unit,
                        $quantity,
                        (int) $row->unit_price,
                        (int) $row->purchase_price,
                        (int) $row->line_total,
                        $grossProfit,
                        $isFirstItem ? (int) $row->subtotal : '',
                        $isFirstItem ? (int) $row->discount_total : '',
                        $isFirstItem ? (int) $row->sale_total : '',
                        $isFirstItem ? (int) $row->amount_paid : '',
                        $isFirstItem ? (int) $row->change_due : '',
                    ]);
                    $lastSaleNumber = $row->sale_number;
                }
            },
        );
    }

    public function stockMovements(Request $request): StreamedResponse
    {
        $filters = $this->dateRange($request, [
            'movement_type' => ['nullable', Rule::in([
                'opening', 'stock_in', 'sale', 'sale_return', 'service_part', 'adjustment',
            ])],
        ]);
        $filters['movement_type'] = $filters['movement_type'] ?? null;
        $movementLabels = [
            'opening' => 'Stok awal',
            'stock_in' => 'Barang masuk',
            'sale' => 'Penjualan',
            'sale_return' => 'Retur pembatalan',
            'service_part' => 'Sparepart service',
            'adjustment' => 'Penyesuaian',
        ];

        return $this->csvDownload(
            "laporan-mutasi-stok-{$filters['fromDate']}-{$filters['toDate']}.csv",
            function ($stream) use ($filters, $movementLabels): void {
                $this->writeCsvRow($stream, [
                    'ID mutasi', 'Tanggal', 'Barcode', 'Produk', 'Satuan', 'Jenis mutasi',
                    'Arah', 'Jumlah', 'Stok sebelum', 'Stok sesudah', 'Dicatat oleh',
                    'Referensi', 'Catatan',
                ]);

                $rows = DB::table('stock_movements as movement')
                    ->join('products as product', 'product.id', '=', 'movement.product_id')
                    ->leftJoin('users as actor', 'actor.id', '=', 'movement.user_id')
                    ->whereBetween('movement.created_at', [$filters['from'], $filters['to']])
                    ->when($filters['movement_type'], fn (Builder $query, string $type) => $query->where('movement.movement_type', $type))
                    ->select([
                        'movement.id', 'movement.created_at', 'product.barcode', 'product.name as product_name',
                        'product.unit', 'movement.movement_type', 'movement.direction', 'movement.quantity',
                        'movement.stock_before', 'movement.stock_after', 'actor.name as actor_name',
                        'movement.reference_type', 'movement.reference_id', 'movement.note',
                    ])
                    ->orderBy('movement.created_at')
                    ->orderBy('movement.id')
                    ->cursor();

                foreach ($rows as $row) {
                    $reference = $row->reference_type
                        ? class_basename($row->reference_type).' #'.$row->reference_id
                        : '';

                    $this->writeCsvRow($stream, [
                        (int) $row->id,
                        $this->localDateTime($row->created_at),
                        $row->barcode,
                        $row->product_name,
                        $row->unit,
                        $movementLabels[$row->movement_type] ?? $row->movement_type,
                        $row->direction === 'in' ? 'Masuk' : 'Keluar',
                        (float) $row->quantity,
                        (float) $row->stock_before,
                        (float) $row->stock_after,
                        $row->actor_name ?: 'Akun tidak aktif',
                        $reference,
                        $row->note,
                    ]);
                }
            },
        );
    }

    public function services(Request $request): StreamedResponse
    {
        $filters = $this->dateRange($request, [
            'status' => ['nullable', Rule::in(Service::STATUSES)],
        ]);
        $filters['status'] = $filters['status'] ?? null;

        return $this->csvDownload(
            "laporan-service-{$filters['fromDate']}-{$filters['toDate']}.csv",
            function ($stream) use ($filters): void {
                $this->writeCsvRow($stream, [
                    'Nomor service', 'Tanggal diterima', 'Pelanggan', 'Telepon pelanggan',
                    'Jenis perangkat', 'Merek/model', 'Nomor seri', 'Status', 'Diterima oleh',
                    'Estimasi awal', 'Total biaya', 'Sudah dibayar', 'Sisa tagihan',
                ]);

                $itemTotals = DB::table('service_items')
                    ->select('service_id', DB::raw('SUM(line_total) as items_total'))
                    ->groupBy('service_id');

                $rows = DB::table('services as service')
                    ->leftJoin('users as receiver', 'receiver.id', '=', 'service.received_by')
                    ->leftJoinSub($itemTotals, 'item_totals', fn ($join) => $join->on('item_totals.service_id', '=', 'service.id'))
                    ->whereBetween('service.received_at', [$filters['from'], $filters['to']])
                    ->when($filters['status'], fn (Builder $query, string $status) => $query->where('service.status', $status))
                    ->select([
                        'service.number', 'service.received_at', 'service.customer_name', 'service.customer_phone',
                        'service.device_type', 'service.brand_model', 'service.serial_number', 'service.status',
                        'receiver.name as receiver_name', 'service.estimated_cost', 'service.paid_total',
                        'item_totals.items_total',
                    ])
                    ->orderBy('service.received_at')
                    ->orderBy('service.id')
                    ->cursor();

                foreach ($rows as $row) {
                    $totalCost = $row->items_total === null
                        ? (int) $row->estimated_cost
                        : (int) $row->items_total;
                    $paid = (int) $row->paid_total;

                    $this->writeCsvRow($stream, [
                        $row->number,
                        $this->localDateTime($row->received_at),
                        $row->customer_name,
                        $row->customer_phone,
                        $row->device_type,
                        $row->brand_model,
                        $row->serial_number,
                        $row->status,
                        $row->receiver_name ?: 'Akun tidak aktif',
                        (int) $row->estimated_cost,
                        $totalCost,
                        $paid,
                        max(0, $totalCost - $paid),
                    ]);
                }
            },
        );
    }

    /** @param array<string, array<int, string>|\Illuminate\Validation\Rules\In> $extraRules
     *  @return array{fromDate: string, toDate: string, from: Carbon, to: Carbon, payment_method?: string|null, movement_type?: string|null, status?: string|null}
     */
    private function dateRange(Request $request, array $extraRules = []): array
    {
        $validated = $request->validate(array_merge([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ], $extraRules));

        $fromDate = $validated['from'] ?? today(config('app.timezone'))->toDateString();
        $toDate = $validated['to'] ?? $fromDate;

        if ($fromDate > $toDate) {
            throw ValidationException::withMessages(['from' => 'Tanggal awal tidak boleh melewati tanggal akhir.']);
        }

        return array_merge($validated, [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'from' => Carbon::parse($fromDate, config('app.timezone'))->startOfDay(),
            'to' => Carbon::parse($toDate, config('app.timezone'))->endOfDay(),
        ]);
    }

    /** @param callable(resource): void $write */
    private function csvDownload(string $filename, callable $write): StreamedResponse
    {
        return response()->streamDownload(function () use ($write): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                return;
            }

            fwrite($stream, "\xEF\xBB\xBF");
            $write($stream);
            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    /** @param resource $stream
     *  @param array<int, mixed> $values
     */
    private function writeCsvRow($stream, array $values): void
    {
        $safeValues = array_map(static function ($value) {
            if ($value === null) {
                return '';
            }

            if (is_string($value) && preg_match('/^[\x00-\x20]*[=+\-@]/', $value) === 1) {
                return "'".$value;
            }

            return $value;
        }, $values);

        fputcsv($stream, $safeValues, ',', '"', '');
    }

    private function paymentLabel(string $method): string
    {
        return ['tunai' => 'Tunai', 'transfer' => 'Transfer', 'qris' => 'QRIS'][$method] ?? ucfirst($method);
    }

    private function localDateTime(string $value): string
    {
        return Carbon::parse($value, config('app.timezone'))
            ->format('Y-m-d H:i:s');
    }
}
