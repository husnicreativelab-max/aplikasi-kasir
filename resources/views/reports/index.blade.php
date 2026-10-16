@extends('layouts.app')
@section('title', 'Laporan penjualan')
@section('content')
<div class="mb-5"><p class="text-sm font-bold text-ink/60">Khusus Admin / Pemilik</p><h1 class="text-3xl font-extrabold">Laporan penjualan</h1><p class="mt-1 text-sm text-ink/70">Ringkasan transaksi menurut tanggal, metode pembayaran, kasir, dan produk.</p></div>
<form method="GET" action="{{ route('reports.index') }}" class="brutal-card mb-5 grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_auto] xl:items-end">
    <x-field label="Dari tanggal" name="from" required><input id="from" name="from" type="date" value="{{ $fromDate }}" class="brutal-input" required></x-field>
    <x-field label="Sampai tanggal" name="to" required><input id="to" name="to" type="date" value="{{ $toDate }}" class="brutal-input" required></x-field>
    <x-field label="Metode pembayaran" name="payment_method"><select id="payment_method" name="payment_method" class="brutal-input"><option value="">Semua metode</option><option value="tunai" @selected($paymentMethod === 'tunai')>Tunai</option><option value="transfer" @selected($paymentMethod === 'transfer')>Transfer</option><option value="qris" @selected($paymentMethod === 'qris')>QRIS</option></select></x-field>
    <x-button variant="primary" type="submit"><x-icon name="search" class="h-5 w-5"/>Tampilkan</x-button>
</form>
<section class="brutal-card mb-5 p-4 sm:p-5">
    <div class="mb-4"><h2 class="text-xl font-extrabold">Ekspor laporan CSV</h2><p class="text-sm text-ink/70">Semua unduhan mengikuti rentang tanggal di atas. File memakai UTF-8 dan dapat dibuka di spreadsheet.</p></div>
    <div class="grid gap-3 lg:grid-cols-3">
        <form method="GET" action="{{ route('reports.export.sales') }}" class="rounded-card border-2 border-ink bg-primary/40 p-3">
            <input type="hidden" name="from" value="{{ $fromDate }}"><input type="hidden" name="to" value="{{ $toDate }}">
            <label for="export_payment_method" class="text-sm font-extrabold">Penjualan</label>
            <select id="export_payment_method" name="payment_method" class="brutal-input mt-1"><option value="">Semua metode</option><option value="tunai" @selected($paymentMethod === 'tunai')>Tunai</option><option value="transfer" @selected($paymentMethod === 'transfer')>Transfer</option><option value="qris" @selected($paymentMethod === 'qris')>QRIS</option></select>
            <p class="my-2 text-xs">Rincian transaksi/item; total transaksi tampil satu kali, termasuk modal dan laba kotor sebelum diskon.</p>
            <x-button variant="primary" type="submit" class="w-full">Unduh penjualan</x-button>
        </form>
        <form method="GET" action="{{ route('reports.export.stock') }}" class="rounded-card border-2 border-ink bg-info/50 p-3">
            <input type="hidden" name="from" value="{{ $fromDate }}"><input type="hidden" name="to" value="{{ $toDate }}">
            <label for="movement_type" class="text-sm font-extrabold">Mutasi stok</label>
            <select id="movement_type" name="movement_type" class="brutal-input mt-1"><option value="">Semua jenis mutasi</option><option value="opening">Stok awal</option><option value="stock_in">Barang masuk</option><option value="sale">Penjualan</option><option value="sale_return">Retur pembatalan</option><option value="service_part">Sparepart service</option><option value="adjustment">Penyesuaian</option></select>
            <p class="my-2 text-xs">Ledger stok dengan jumlah dan saldo sebelum/sesudah mutasi.</p>
            <x-button variant="neutral" type="submit" class="w-full">Unduh mutasi stok</x-button>
        </form>
        <form method="GET" action="{{ route('reports.export.services') }}" class="rounded-card border-2 border-ink bg-warning/60 p-3">
            <input type="hidden" name="from" value="{{ $fromDate }}"><input type="hidden" name="to" value="{{ $toDate }}">
            <label for="service_status" class="text-sm font-extrabold">Service</label>
            <select id="service_status" name="status" class="brutal-input mt-1"><option value="">Semua status</option>@foreach(\App\Models\Service::STATUSES as $serviceStatus)<option value="{{ $serviceStatus }}">{{ $serviceStatus }}</option>@endforeach</select>
            <p class="my-2 text-xs">Ringkasan perangkat, status dan pembayaran. Tidak menyertakan PIN atau kode pelacakan.</p>
            <x-button variant="warning" type="submit" class="w-full">Unduh laporan service</x-button>
        </form>
    </div>
</section>
<div class="mb-5 grid gap-4 sm:grid-cols-3">
    <section class="metric-card bg-primary"><p class="text-sm font-bold">Total penjualan</p><p class="mt-2 text-2xl font-extrabold">Rp {{ number_format($totalSales, 0, ',', '.') }}</p></section>
    <section class="metric-card bg-success"><p class="text-sm font-bold">Jumlah transaksi</p><p class="mt-2 text-3xl font-extrabold">{{ number_format($transactionCount, 0, ',', '.') }}</p></section>
    <section class="metric-card bg-info"><p class="text-sm font-bold">Rata-rata transaksi</p><p class="mt-2 text-2xl font-extrabold">Rp {{ number_format($averageSale, 0, ',', '.') }}</p></section>
</div>
<div class="grid gap-5 xl:grid-cols-2">
    <x-card title="Metode pembayaran" icon="wallet" tone="success">
        <div class="space-y-2">
            @forelse($paymentTotals as $row)
                @php($methodLabel = ['tunai' => 'Tunai', 'transfer' => 'Transfer', 'qris' => 'QRIS'][$row->payment_method] ?? ucfirst($row->payment_method))
                <div class="flex items-center justify-between gap-3 rounded-button border-2 border-ink bg-white p-3"><div><p class="font-extrabold">{{ $methodLabel }}</p><p class="text-xs text-ink/65">{{ number_format($row->transaction_count, 0, ',', '.') }} transaksi</p></div><p class="text-right font-extrabold">Rp {{ number_format($row->amount_total, 0, ',', '.') }}</p></div>
            @empty<p class="text-center text-sm text-ink/65">Belum ada transaksi di rentang tanggal ini.</p>@endforelse
        </div>
    </x-card>
    <x-card title="Penjualan per kasir" icon="user" tone="info">
        <div class="space-y-2">
            @forelse($cashierTotals as $row)
                <div class="flex items-center justify-between gap-3 rounded-button border-2 border-ink bg-white p-3"><div><p class="font-extrabold">{{ $row->user?->name ?? 'Akun tidak aktif' }}</p><p class="text-xs text-ink/65">{{ number_format($row->transaction_count, 0, ',', '.') }} transaksi</p></div><p class="text-right font-extrabold">Rp {{ number_format($row->amount_total, 0, ',', '.') }}</p></div>
            @empty<p class="text-center text-sm text-ink/65">Belum ada transaksi di rentang tanggal ini.</p>@endforelse
        </div>
    </x-card>
    <x-card title="Penjualan harian" icon="chart" tone="primary">
        <div class="space-y-2">
            @forelse($dailyTotals as $row)
                <div class="flex items-center justify-between gap-3 rounded-button border-2 border-ink bg-white p-3"><div><p class="font-extrabold">{{ \Carbon\Carbon::parse($row->sale_date)->translatedFormat('d M Y') }}</p><p class="text-xs text-ink/65">{{ number_format($row->transaction_count, 0, ',', '.') }} transaksi</p></div><p class="text-right font-extrabold">Rp {{ number_format($row->amount_total, 0, ',', '.') }}</p></div>
            @empty<p class="text-center text-sm text-ink/65">Belum ada transaksi di rentang tanggal ini.</p>@endforelse
        </div>
    </x-card>
    <x-card title="Produk terlaris" icon="star" tone="warning">
        <div class="space-y-2">
            @forelse($topProducts as $index => $product)
                <div class="flex items-center gap-3 rounded-button border-2 border-ink bg-white p-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-button border-2 border-ink bg-primary font-extrabold">{{ $index + 1 }}</span><div class="min-w-0 flex-1"><p class="truncate font-extrabold">{{ $product->product_name }}</p><p class="text-xs text-ink/65">Terjual {{ number_format($product->quantity_sold, 0, ',', '.') }}</p></div><p class="text-right text-sm font-extrabold">Rp {{ number_format($product->sales_total, 0, ',', '.') }}</p></div>
            @empty<p class="text-center text-sm text-ink/65">Belum ada produk terjual di rentang tanggal ini.</p>@endforelse
        </div>
    </x-card>
</div>
@endsection
