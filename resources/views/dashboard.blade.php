@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
    <div>
        <p class="text-sm font-bold text-ink/60">{{ now(config('app.timezone'))->translatedFormat('l, d F Y') }}</p>
        <h1 class="mt-1 text-3xl font-extrabold sm:text-4xl">Halo, {{ auth()->user()->name }} 👋</h1>
        <p class="mt-1 text-ink/70">Ringkasan aktivitas {{ $store['short_name'] ?? 'toko' }} hari ini.</p>
    </div>
    <x-button as="a" href="{{ route('pos.index') }}" variant="primary" size="lg"><x-icon name="cashier" class="h-5 w-5"/>Buka Kasir</x-button>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <section class="metric-card bg-primary">
        <div class="flex items-start justify-between gap-3"><span class="grid h-11 w-11 place-items-center rounded-button border-2 border-ink bg-white"><x-icon name="wallet" class="h-6 w-6"/></span><span class="rounded-pill border-2 border-ink bg-white px-2 py-1 text-[11px] font-extrabold">HARI INI</span></div>
        <p class="mt-5 text-sm font-bold">Omzet hari ini</p><p class="mt-1 text-2xl font-extrabold" x-data x-text="formatRupiah({{ (int) $salesTotal }})">Rp {{ number_format($salesTotal, 0, ',', '.') }}</p>
    </section>
    <section class="metric-card bg-success">
        <div class="flex items-start justify-between gap-3"><span class="grid h-11 w-11 place-items-center rounded-button border-2 border-ink bg-white"><x-icon name="receipt" class="h-6 w-6"/></span><span class="rounded-pill border-2 border-ink bg-white px-2 py-1 text-[11px] font-extrabold">TRANSAKSI</span></div>
        <p class="mt-5 text-sm font-bold">Jumlah transaksi</p><p class="mt-1 text-3xl font-extrabold">{{ number_format($transactionCount, 0, ',', '.') }} <span class="text-base">transaksi</span></p>
    </section>
    <section class="metric-card bg-info">
        <div class="flex items-start justify-between gap-3"><span class="grid h-11 w-11 place-items-center rounded-button border-2 border-ink bg-white"><x-icon name="tool" class="h-6 w-6"/></span><span class="rounded-pill border-2 border-ink bg-white px-2 py-1 text-[11px] font-extrabold">SERVICE</span></div>
        <p class="mt-5 text-sm font-bold">Service aktif</p><p class="mt-1 text-3xl font-extrabold">{{ number_format($activeServices, 0, ',', '.') }} <span class="text-base">unit</span></p>
    </section>
    <section class="metric-card bg-warning">
        <div class="flex items-start justify-between gap-3"><span class="grid h-11 w-11 place-items-center rounded-button border-2 border-ink bg-white"><x-icon name="box" class="h-6 w-6"/></span><span class="rounded-pill border-2 border-ink bg-white px-2 py-1 text-[11px] font-extrabold">PERLU CEK</span></div>
        <p class="mt-5 text-sm font-bold">Stok menipis</p><p class="mt-1 text-3xl font-extrabold">{{ number_format($lowStockCount, 0, ',', '.') }} <span class="text-base">produk</span></p>
    </section>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(280px,0.8fr)]">
    <x-card title="Perlu restok" icon="alert" tone="warning">
        @if($lowStock->isEmpty())
            <div class="rounded-button border-2 border-dashed border-ink/40 bg-cream p-6 text-center">
                <span class="mx-auto grid h-12 w-12 place-items-center rounded-full border-2 border-ink bg-success"><x-icon name="check" class="h-6 w-6"/></span>
                <p class="mt-3 font-extrabold">Semua stok aman</p><p class="text-sm text-ink/70">Produk dengan stok menipis akan muncul di sini.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($lowStock as $product)
                    <div class="rounded-button border-2 border-ink bg-white p-3 shadow-brutal-sm sm:flex sm:items-center sm:justify-between sm:gap-5">
                        <div class="mb-3 min-w-0 sm:mb-0"><p class="truncate font-extrabold">{{ $product->name }}</p><p class="text-xs text-ink/65">{{ $product->barcode ?: 'Tanpa barcode' }} · {{ $product->category?->name ?? 'Tanpa kategori' }}</p></div>
                        <x-progress-stock :stock="$product->stock" :minimum="$product->minimum_stock" :unit="$product->unit" class="sm:w-48 sm:shrink-0" />
                    </div>
                @endforeach
            </div>
        @endif
        <div class="mt-4 flex flex-wrap gap-2">
            <x-button as="a" href="{{ route('products.index', ['status' => 'aktif']) }}" variant="neutral" size="sm"><x-icon name="box" class="h-4 w-4"/>Lihat produk</x-button>
            @if(auth()->user()->isAdmin())<x-button as="a" href="{{ route('goods-in.index') }}" variant="warning" size="sm"><x-icon name="plus" class="h-4 w-4"/>Barang masuk</x-button>@endif
        </div>
    </x-card>

    <x-card title="Akses cepat" icon="star" tone="primary">
        <div class="grid gap-3">
            <a href="{{ route('pos.index') }}" class="flex min-h-16 items-center gap-3 rounded-button border-2 border-ink bg-primary p-3 font-extrabold shadow-brutal-sm transition hover:-translate-y-0.5 hover:shadow-brutal"><span class="grid h-10 w-10 place-items-center rounded-button border-2 border-ink bg-white"><x-icon name="cashier" class="h-5 w-5"/></span><span>Mulai transaksi baru<small class="block text-xs font-semibold">Scan barcode atau cari produk</small></span></a>
            <a href="{{ route('products.index') }}" class="flex min-h-16 items-center gap-3 rounded-button border-2 border-ink bg-white p-3 font-extrabold shadow-brutal-sm transition hover:-translate-y-0.5 hover:shadow-brutal"><span class="grid h-10 w-10 place-items-center rounded-button border-2 border-ink bg-success"><x-icon name="box" class="h-5 w-5"/></span><span>Kelola produk<small class="block text-xs font-semibold">Cari harga dan ketersediaan</small></span></a>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('reports.index') }}" class="flex min-h-16 items-center gap-3 rounded-button border-2 border-ink bg-white p-3 font-extrabold shadow-brutal-sm transition hover:-translate-y-0.5 hover:shadow-brutal"><span class="grid h-10 w-10 place-items-center rounded-button border-2 border-ink bg-info"><x-icon name="chart" class="h-5 w-5"/></span><span>Lihat laporan<small class="block text-xs font-semibold">Ringkasan penjualan</small></span></a>
            @endif
        </div>
    </x-card>
</div>
@endsection
