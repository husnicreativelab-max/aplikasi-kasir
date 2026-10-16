@extends('layouts.app')
@section('title', 'Service komputer')
@section('content')
<div class="mx-auto max-w-2xl py-8"><x-card title="Service komputer" icon="tool" tone="info"><div class="text-center"><span class="mx-auto grid h-16 w-16 place-items-center rounded-card border-2 border-ink bg-info shadow-brutal-sm"><x-icon name="tool" class="h-8 w-8"/></span><x-badge variant="warning" class="mt-4">DIRANCANG UNTUK FASE 2</x-badge><h1 class="mt-3 text-2xl font-extrabold">Penerimaan dan pelacakan service</h1><p class="mt-2 text-ink/70">Modul ini akan berisi data pelanggan, status pengerjaan, sparepart, pembayaran, nota, dan cek status publik.</p><x-button as="a" href="{{ route('dashboard') }}" variant="primary" class="mt-5">Kembali ke dashboard</x-button></div></x-card></div>
@endsection
