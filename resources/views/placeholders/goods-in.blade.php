@extends('layouts.app')
@section('title', 'Barang masuk')
@section('content')
<div class="mx-auto max-w-2xl py-8"><x-card title="Barang masuk" icon="box" tone="warning"><div class="text-center"><span class="mx-auto grid h-16 w-16 place-items-center rounded-card border-2 border-ink bg-warning shadow-brutal-sm"><x-icon name="plus" class="h-8 w-8"/></span><x-badge variant="warning" class="mt-4">DIRANCANG UNTUK FASE 2</x-badge><h1 class="mt-3 text-2xl font-extrabold">Tambah stok dari pembelian</h1><p class="mt-2 text-ink/70">Modul barang masuk akan mencatat jumlah, harga beli, catatan, dan mutasi stok tanpa perlu data supplier.</p><x-button as="a" href="{{ route('dashboard') }}" variant="primary" class="mt-5">Kembali ke dashboard</x-button></div></x-card></div>
@endsection
