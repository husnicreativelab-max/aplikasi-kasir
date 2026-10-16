@extends('layouts.app')
@section('title', 'Barang masuk')
@section('content')
<div class="mb-5">
    <p class="text-sm font-bold text-ink/60">Pembelian stok dan mutasi masuk</p>
    <h1 class="text-3xl font-extrabold">Barang masuk</h1>
    <p class="mt-1 max-w-3xl text-sm text-ink/70">Catat jumlah barang dan harga beli. Stok serta harga beli terbaru produk akan diperbarui dalam satu transaksi.</p>
</div>

<div class="grid gap-5 xl:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
    <section class="brutal-card h-fit overflow-hidden">
        <header class="flex items-center gap-3 border-b-2 border-ink bg-warning px-4 py-3">
            <span class="grid h-10 w-10 place-items-center rounded-button border-2 border-ink bg-white shadow-brutal-sm"><x-icon name="plus" class="h-5 w-5"/></span>
            <div><h2 class="text-lg font-extrabold">Catat pembelian stok</h2><p class="text-xs font-semibold">Semua kolom bertanda * wajib diisi.</p></div>
        </header>
        <form method="POST" action="{{ route('goods-in.store') }}" class="space-y-4 p-4 sm:p-5" x-data="{ quantity: '{{ old('quantity', '1') }}', purchasePrice: '{{ old('purchase_price', '') }}' }">
            @csrf
            @if($products->isEmpty())
                <div class="rounded-button border-2 border-ink bg-warning/40 p-3 text-sm font-semibold">Belum ada produk barang aktif. Tambahkan produk terlebih dahulu.</div>
            @endif
            <x-field label="Produk barang" name="product_id" required>
                <select id="product_id" name="product_id" class="brutal-input" required>
                    <option value="">Pilih produk...</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>{{ $product->name }}{{ $product->barcode ? ' · '.$product->barcode : '' }} — stok {{ rtrim(rtrim(number_format($product->stock, 3, ',', '.'), '0'), ',') }} {{ $product->unit }}</option>
                    @endforeach
                </select>
            </x-field>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Jumlah masuk" name="quantity" required hint="Maksimal tiga angka di belakang koma."><input id="quantity" name="quantity" type="number" inputmode="decimal" min="0.001" max="999999" step="0.001" value="{{ old('quantity', '1') }}" class="brutal-input" x-model="quantity" required></x-field>
                <x-field label="Harga beli per unit (Rp)" name="purchase_price" required><input id="purchase_price" name="purchase_price" type="number" inputmode="numeric" min="0" max="999999999999" step="1" value="{{ old('purchase_price') }}" class="brutal-input" x-model="purchasePrice" required></x-field>
            </div>
            <div class="rounded-button border-2 border-ink bg-cream p-3"><p class="text-xs font-bold uppercase tracking-wide text-ink/65">Perkiraan total pembelian</p><p class="mt-1 text-xl font-extrabold" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round((Number(quantity) || 0) * (Number(purchasePrice) || 0)))">Rp 0</p></div>
            <x-field label="Catatan / referensi pembelian" name="note"><textarea id="note" name="note" rows="3" maxlength="1000" class="brutal-input" placeholder="Contoh: pembelian stok dari toko grosir">{{ old('note') }}</textarea></x-field>
            <x-button variant="primary" type="submit" size="lg" class="w-full" :disabled="$products->isEmpty()"><x-icon name="check" class="h-5 w-5"/>Simpan barang masuk</x-button>
        </form>
    </section>

    <section class="brutal-card overflow-hidden">
        <header class="flex flex-col gap-3 border-b-2 border-ink bg-cream px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div><h2 class="text-lg font-extrabold">Riwayat barang masuk</h2><p class="text-xs font-semibold text-ink/65">Termasuk nama petugas, jumlah, harga beli, dan catatan.</p></div>
            <x-badge variant="info">{{ number_format($stockIns->total(), 0, ',', '.') }} catatan</x-badge>
        </header>
        <form method="GET" action="{{ route('goods-in.index') }}" class="grid gap-3 border-b border-ink/20 p-4 sm:grid-cols-2 xl:grid-cols-4 xl:items-end">
            <x-field label="Cari produk / barcode" name="q"><input id="q" name="q" type="search" value="{{ request('q') }}" class="brutal-input" placeholder="Nama atau barcode"></x-field>
            <x-field label="Dari tanggal" name="from"><input id="from" name="from" type="date" value="{{ request('from') }}" class="brutal-input"></x-field>
            <x-field label="Sampai tanggal" name="to"><input id="to" name="to" type="date" value="{{ request('to') }}" class="brutal-input"></x-field>
            <x-button variant="neutral" type="submit"><x-icon name="search" class="h-5 w-5"/>Terapkan</x-button>
        </form>

        <div class="hidden overflow-x-auto md:block">
            <table class="w-full min-w-[740px] text-sm">
                <thead class="table-head"><tr><th class="px-4 py-3">Waktu / produk</th><th class="px-4 py-3">Jumlah</th><th class="px-4 py-3">Harga beli</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Petugas / catatan</th></tr></thead>
                <tbody>
                @forelse($stockIns as $row)
                    <tr class="border-t border-ink/20 align-top hover:bg-cream/60">
                        <td class="px-4 py-3"><p class="font-extrabold">{{ $row->product?->name ?? 'Produk dihapus' }}</p><p class="text-xs text-ink/60">{{ $row->received_at->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }} WIB</p></td>
                        <td class="px-4 py-3 font-bold">{{ rtrim(rtrim(number_format($row->quantity, 3, ',', '.'), '0'), ',') }} {{ $row->product?->unit ?? '' }}</td>
                        <td class="px-4 py-3">Rp {{ number_format($row->purchase_price, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 font-extrabold">Rp {{ number_format($row->total_cost, 0, ',', '.') }}</td>
                        <td class="px-4 py-3"><p>{{ $row->user?->name ?? 'Akun dihapus' }}</p><p class="max-w-xs whitespace-pre-line text-xs text-ink/65">{{ $row->note ?: '—' }}</p></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center"><x-icon name="box" class="mx-auto h-8 w-8"/><p class="mt-2 font-extrabold">Belum ada riwayat barang masuk</p><p class="text-sm text-ink/65">Catatan pembelian yang disimpan akan tampil di sini.</p></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="space-y-3 p-3 md:hidden">
            @forelse($stockIns as $row)
                <article class="rounded-button border-2 border-ink bg-white p-3 shadow-brutal-sm">
                    <div class="flex items-start justify-between gap-2"><div><h3 class="font-extrabold">{{ $row->product?->name ?? 'Produk dihapus' }}</h3><p class="text-xs text-ink/65">{{ $row->received_at->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }} WIB</p></div><x-badge variant="success">+{{ rtrim(rtrim(number_format($row->quantity, 3, ',', '.'), '0'), ',') }}</x-badge></div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm"><div class="rounded-button bg-cream p-2"><span class="block text-xs text-ink/65">Harga / unit</span><strong>Rp {{ number_format($row->purchase_price, 0, ',', '.') }}</strong></div><div class="rounded-button bg-cream p-2"><span class="block text-xs text-ink/65">Total</span><strong>Rp {{ number_format($row->total_cost, 0, ',', '.') }}</strong></div></div>
                    <p class="mt-2 text-xs text-ink/65">Petugas: {{ $row->user?->name ?? 'Akun dihapus' }}{{ $row->note ? ' · '.$row->note : '' }}</p>
                </article>
            @empty
                <p class="rounded-button border border-ink/20 p-5 text-center text-sm text-ink/65">Belum ada riwayat barang masuk.</p>
            @endforelse
        </div>
        @if($stockIns->hasPages())<div class="border-t-2 border-ink bg-cream p-3">{{ $stockIns->links() }}</div>@endif
    </section>
</div>
@endsection
