@extends('layouts.app')
@section('title', $product->exists ? 'Ubah produk' : 'Tambah produk')
@section('content')
@php
    $isEdit = $product->exists;
    $initialType = old('type', $product->type ?? 'barang');
@endphp
<div class="mb-5"><a href="{{ route('products.index') }}" class="inline-flex min-h-10 items-center gap-2 text-sm font-bold underline decoration-2 underline-offset-4">← Kembali ke produk</a><h1 class="mt-2 text-3xl font-extrabold">{{ $isEdit ? 'Ubah produk' : 'Tambah produk' }}</h1><p class="mt-1 text-sm text-ink/70">Isi informasi barang dengan teliti. Harga dan stok dapat diubah oleh Admin.</p></div>
<div class="max-w-4xl" x-data="{
    type: @js($initialType),
    barcode: @js(old('barcode', $product->barcode ?? '')),
    generating: false,
    async generateCode() {
        this.generating = true;
        try {
            const response = await fetch('{{ route('products.generate-code', [], false) }}', { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (response.ok && data.barcode) this.barcode = data.barcode;
        } catch (_) { alert('Kode otomatis belum dapat dibuat. Coba lagi.'); }
        this.generating = false;
    }
}">
    <form method="POST" action="{{ $isEdit ? route('products.update', $product) : route('products.store') }}" class="brutal-card space-y-5 p-4 sm:p-6">
        @csrf @if($isEdit) @method('PUT') @endif
        <div class="grid gap-4 md:grid-cols-2">
            <x-field label="Nama produk" name="name" required><input id="name" name="name" value="{{ old('name', $product->name) }}" class="brutal-input" maxlength="160" required autofocus></x-field>
            <x-field label="Kategori" name="category_id"><select id="category_id" name="category_id" class="brutal-input"><option value="">Pilih kategori (opsional)</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></x-field>
        </div>
        <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
            <x-field label="Kode / Barcode" name="barcode" hint="Boleh dikosongkan jika produk tidak memiliki kode.">
                <input id="barcode" name="barcode" x-model="barcode" value="{{ old('barcode', $product->barcode) }}" class="brutal-input font-mono" maxlength="80" autocomplete="off">
            </x-field>
            <x-button variant="info" type="button" @click="generateCode()" ::disabled="generating"><x-icon name="barcode" class="h-5 w-5"/><span x-text="generating ? 'Membuat...' : 'Buat kode otomatis'"></span></x-button>
        </div>
        <div class="grid gap-4 md:grid-cols-3">
            <x-field label="Tipe produk" name="type" required><select id="type" name="type" x-model="type" class="brutal-input" required><option value="barang">Barang (memiliki stok)</option><option value="jasa">Jasa (tanpa stok)</option></select></x-field>
            <x-field label="Satuan" name="unit" required><input id="unit" name="unit" value="{{ old('unit', $product->unit ?: 'pcs') }}" class="brutal-input" maxlength="30" placeholder="pcs, unit, jam" required></x-field>
            <x-field label="Status" name="is_active"><select id="is_active" name="is_active" class="brutal-input"><option value="1" @selected((string) old('is_active', $product->is_active ?? true) === '1')>Aktif</option><option value="0" @selected((string) old('is_active', $product->is_active ?? true) === '0')>Nonaktif</option></select></x-field>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <x-field label="Harga beli (Rp)" name="purchase_price" required hint="Hanya terlihat oleh Admin."><input id="purchase_price" name="purchase_price" type="number" min="0" step="1" value="{{ old('purchase_price', $product->purchase_price ?? 0) }}" class="brutal-input" inputmode="numeric" required></x-field>
            <x-field label="Harga jual (Rp)" name="selling_price" required><input id="selling_price" name="selling_price" type="number" min="0" step="1" value="{{ old('selling_price', $product->selling_price ?? 0) }}" class="brutal-input" inputmode="numeric" required></x-field>
        </div>
        <div x-show="type === 'barang'" x-cloak class="grid gap-4 rounded-button border-2 border-ink bg-cream p-4 md:grid-cols-2">
            <x-field label="Jumlah stok" name="stock" required hint="Perubahan stok akan tercatat di riwayat mutasi."><input id="stock" name="stock" type="number" min="0" step="0.001" value="{{ old('stock', $product->stock ?? 0) }}" class="brutal-input" inputmode="decimal" :required="type === 'barang'" :disabled="type !== 'barang'"></x-field>
            <x-field label="Batas stok minimum" name="minimum_stock" required><input id="minimum_stock" name="minimum_stock" type="number" min="0" step="0.001" value="{{ old('minimum_stock', $product->minimum_stock ?? 0) }}" class="brutal-input" inputmode="decimal" required></x-field>
        </div>
        <template x-if="type === 'jasa'"><input type="hidden" name="stock" value="0"></template>
        <div class="flex flex-col-reverse justify-end gap-3 border-t-2 border-ink/15 pt-4 sm:flex-row"><x-button as="a" href="{{ route('products.index') }}" variant="neutral">Batal</x-button><x-button variant="primary" type="submit" size="lg"><x-icon name="check" class="h-5 w-5"/>{{ $isEdit ? 'Simpan perubahan' : 'Simpan produk' }}</x-button></div>
    </form>
</div>
@endsection
