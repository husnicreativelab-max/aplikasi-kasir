@extends('layouts.app')
@section('title', 'Kategori')
@section('content')
<div class="mb-5"><a href="{{ route('products.index') }}" class="inline-flex min-h-10 items-center gap-2 text-sm font-bold underline decoration-2 underline-offset-4">← Kembali ke produk</a><h1 class="mt-2 text-3xl font-extrabold">Kategori produk</h1><p class="mt-1 text-sm text-ink/70">Kelompokkan produk agar lebih mudah dicari saat transaksi.</p></div>
<div class="grid gap-5 xl:grid-cols-[minmax(280px,0.7fr)_minmax(0,1.3fr)]">
    <x-card :title="$editing->exists ? 'Ubah kategori' : 'Tambah kategori'" icon="box" tone="info">
        <form method="POST" action="{{ $editing->exists ? route('categories.update', $editing) : route('categories.store') }}" class="space-y-4">
            @csrf @if($editing->exists) @method('PUT') @endif
            <x-field label="Nama kategori" name="name" required><input id="name" name="name" value="{{ old('name', $editing->name) }}" class="brutal-input" maxlength="100" required autofocus></x-field>
            <x-field label="Keterangan" name="description"><textarea id="description" name="description" rows="3" class="brutal-input min-h-24" maxlength="255">{{ old('description', $editing->description) }}</textarea></x-field>
            <x-field label="Status" name="is_active"><select id="is_active" name="is_active" class="brutal-input"><option value="1" @selected((string) old('is_active', $editing->is_active ?? true) === '1')>Aktif</option><option value="0" @selected((string) old('is_active', $editing->is_active ?? true) === '0')>Nonaktif</option></select></x-field>
            <div class="flex flex-wrap gap-2"><x-button variant="primary" type="submit"><x-icon name="check" class="h-5 w-5"/>{{ $editing->exists ? 'Simpan perubahan' : 'Tambah kategori' }}</x-button>@if($editing->exists)<x-button as="a" href="{{ route('categories.index') }}" variant="neutral">Batal</x-button>@endif</div>
        </form>
    </x-card>
    <x-card title="Daftar kategori" icon="box" tone="success">
        <div class="space-y-3">
            @forelse($categories as $category)
                <article class="rounded-button border-2 border-ink bg-white p-3 shadow-brutal-sm sm:flex sm:items-center sm:justify-between sm:gap-4">
                    <div><div class="flex flex-wrap items-center gap-2"><h2 class="font-extrabold">{{ $category->name }}</h2><x-badge :variant="$category->is_active ? 'success' : 'muted'">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></div><p class="mt-1 text-sm text-ink/65">{{ $category->description ?: 'Tidak ada keterangan' }} · {{ $category->products_count }} produk</p></div>
                    <div class="mt-3 flex gap-2 sm:mt-0"><x-button as="a" href="{{ route('categories.index', ['edit' => $category->id]) }}" variant="info" size="sm">Ubah</x-button>@if($category->products_count === 0)<x-confirm-delete :action="route('categories.destroy', $category)" message="Kategori ini akan dihapus permanen." />@endif</div>
                </article>
            @empty
                <p class="rounded-button border-2 border-dashed border-ink/40 p-8 text-center font-semibold">Belum ada kategori.</p>
            @endforelse
        </div>
        @if($categories->hasPages())<div class="mt-4">{{ $categories->links() }}</div>@endif
    </x-card>
</div>
@endsection
