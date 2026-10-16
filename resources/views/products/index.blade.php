@extends('layouts.app')
@section('title', 'Produk')
@section('content')
@php($isAdmin = auth()->user()->isAdmin())
<div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
    <div><p class="text-sm font-bold text-ink/60">Katalog dan persediaan</p><h1 class="text-3xl font-extrabold">Produk</h1><p class="mt-1 text-sm text-ink/70">Cari barang atau jasa, cek ketersediaan, dan kelola harga.</p></div>
    @if($isAdmin)<div class="flex flex-wrap gap-2"><x-button as="a" href="{{ route('categories.index') }}" variant="neutral"><x-icon name="box" class="h-5 w-5"/>Kategori</x-button><x-button as="a" href="{{ route('products.create') }}" variant="primary"><x-icon name="plus" class="h-5 w-5"/>Tambah produk</x-button></div>@endif
</div>

<form method="GET" action="{{ route('products.index') }}" class="brutal-card mb-5 grid gap-3 p-4 sm:grid-cols-[minmax(0,1fr)_200px_160px_auto] sm:items-end">
    <x-field label="Cari nama / barcode" name="q"><input id="q" type="search" name="q" value="{{ request('q') }}" class="brutal-input" placeholder="Contoh: SSD, PRD-..." autocomplete="off"></x-field>
    <x-field label="Kategori" name="category"><select id="category" name="category" class="brutal-input"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></x-field>
    <x-field label="Status" name="status"><select id="status" name="status" class="brutal-input"><option value="">Semua</option><option value="aktif" @selected(request('status')==='aktif')>Aktif</option><option value="nonaktif" @selected(request('status')==='nonaktif')>Nonaktif</option></select></x-field>
    <x-button variant="primary" type="submit"><x-icon name="search" class="h-5 w-5"/>Cari</x-button>
</form>

@if($isAdmin)
    <form id="barcode-label-form" method="GET" action="{{ route('products.labels') }}" class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-button border-2 border-ink bg-white p-3 shadow-brutal-sm">
        <p class="text-sm font-semibold">Pilih produk yang memiliki barcode untuk mencetak label Code128.</p>
        <x-button variant="info" type="submit" size="sm"><x-icon name="print" class="h-4 w-4"/>Cetak label terpilih</x-button>
    </form>
@endif

<div class="hidden overflow-hidden rounded-card border-2 border-ink bg-white shadow-brutal md:block">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[850px] border-collapse text-sm">
            <thead class="table-head"><tr><th class="px-4 py-3">Produk</th><th class="px-4 py-3">Kategori</th><th class="px-4 py-3">Harga jual</th>@if($isAdmin)<th class="px-4 py-3">Harga beli</th>@endif<th class="px-4 py-3">Stok</th><th class="px-4 py-3">Status</th>@if($isAdmin)<th class="px-4 py-3">Aksi</th>@endif</tr></thead>
            <tbody>
            @forelse($products as $product)
                <tr class="border-t border-ink/20 align-top hover:bg-cream/60">
                    <td class="px-4 py-3"><div class="flex items-start gap-2">@if($isAdmin)<input type="checkbox" name="ids[]" value="{{ $product->id }}" form="barcode-label-form" class="mt-1 h-5 w-5 rounded border-2 border-ink text-success focus:ring-primary" aria-label="Pilih {{ $product->name }} untuk label">@endif<div><p class="font-extrabold">{{ $product->name }}</p><p class="text-xs text-ink/60">{{ $product->barcode ?: 'Tanpa barcode' }} · {{ $product->type === 'barang' ? 'Barang' : 'Jasa' }}</p></div></div></td>
                    <td class="px-4 py-3">{{ $product->category?->name ?? '—' }}</td>
                    <td class="px-4 py-3 font-extrabold">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</td>
                    @if($isAdmin)<td class="px-4 py-3">Rp {{ number_format($product->purchase_price, 0, ',', '.') }}</td>@endif
                    <td class="px-4 py-3">@if($product->type === 'barang')<x-progress-stock :stock="$product->stock" :minimum="$product->minimum_stock" :unit="$product->unit" class="w-36"/>@else<span class="text-sm">Tidak terbatas</span>@endif</td>
                    <td class="px-4 py-3"><x-badge :variant="$product->is_active ? 'success' : 'muted'">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></td>
                    @if($isAdmin)<td class="px-4 py-3"><div class="flex flex-wrap gap-2"><x-button as="a" href="{{ route('products.edit', $product) }}" variant="info" size="sm">Ubah</x-button><x-confirm-delete :action="route('products.destroy', $product)" message="Produk ini akan dikeluarkan dari katalog. Riwayat transaksi tetap tersimpan." /></div></td>@endif
                </tr>
            @empty
                <tr><td colspan="{{ $isAdmin ? 7 : 5 }}" class="px-4 py-12 text-center"><span class="mx-auto grid h-12 w-12 place-items-center rounded-button border-2 border-ink bg-cream"><x-icon name="search" class="h-6 w-6"/></span><p class="mt-3 font-extrabold">Produk belum ditemukan</p><p class="text-sm text-ink/65">Coba ubah kata kunci atau filter.</p></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($products->hasPages())<div class="border-t-2 border-ink bg-cream p-3">{{ $products->links() }}</div>@endif
</div>

<div class="space-y-3 md:hidden">
    @forelse($products as $product)
        <article class="brutal-card p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0"><div class="flex items-start gap-2">@if($isAdmin)<input type="checkbox" name="ids[]" value="{{ $product->id }}" form="barcode-label-form" class="mt-1 h-5 w-5 rounded border-2 border-ink text-success focus:ring-primary" aria-label="Pilih {{ $product->name }} untuk label">@endif<div class="min-w-0"><h2 class="truncate font-extrabold">{{ $product->name }}</h2><p class="text-xs text-ink/60">{{ $product->barcode ?: 'Tanpa barcode' }}</p></div></div></div>
                <x-badge :variant="$product->is_active ? 'success' : 'muted'">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2 text-sm"><div class="rounded-button border border-ink/30 bg-cream p-2"><span class="block text-xs font-semibold text-ink/60">Harga jual</span><span class="font-extrabold">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span></div>@if($isAdmin)<div class="rounded-button border border-ink/30 bg-cream p-2"><span class="block text-xs font-semibold text-ink/60">Harga beli</span><span class="font-bold">Rp {{ number_format($product->purchase_price, 0, ',', '.') }}</span></div>@endif</div>
            <p class="mt-2 text-xs text-ink/65">{{ $product->category?->name ?? 'Tanpa kategori' }} · {{ $product->type === 'barang' ? 'Barang' : 'Jasa' }}</p>
            @if($product->type === 'barang')<x-progress-stock :stock="$product->stock" :minimum="$product->minimum_stock" :unit="$product->unit" class="mt-3"/>@endif
            @if($isAdmin)<div class="mt-3 flex gap-2"><x-button as="a" href="{{ route('products.edit', $product) }}" variant="info" size="sm" class="flex-1">Ubah</x-button><x-confirm-delete :action="route('products.destroy', $product)" message="Produk ini akan dikeluarkan dari katalog. Riwayat transaksi tetap tersimpan." /></div>@endif
        </article>
    @empty
        <div class="brutal-card p-8 text-center"><x-icon name="search" class="mx-auto h-8 w-8"/><p class="mt-2 font-extrabold">Produk belum ditemukan</p></div>
    @endforelse
    @if($products->hasPages())<div class="rounded-card border-2 border-ink bg-white p-3">{{ $products->links() }}</div>@endif
</div>
@endsection
