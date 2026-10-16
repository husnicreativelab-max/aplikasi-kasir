@extends('layouts.public-status')
@section('title', 'Cek status service')
@section('content')
<div class="mx-auto max-w-xl">
    <div class="mb-5 text-center"><span class="mx-auto grid h-16 w-16 place-items-center rounded-card border-2 border-ink bg-info shadow-brutal-sm"><x-icon name="tool" class="h-8 w-8"/></span><p class="mt-4 text-sm font-bold text-ink/60">Pembaruan perbaikan perangkat</p><h1 class="text-3xl font-extrabold">Cek status service</h1><p class="mt-2 text-sm text-ink/70">Masukkan nomor service dan kode rahasia yang tercetak pada nota penerimaan.</p></div>
    <form method="POST" action="{{ route('public.services.check') }}" class="brutal-card space-y-4 p-5 sm:p-6">
        @csrf
        @if($errors->has('lookup'))<div class="rounded-button border-2 border-ink bg-danger/30 p-3 text-sm font-bold" role="alert">{{ $errors->first('lookup') }}</div>@endif
        <x-field label="Nomor service" name="number" required><input id="number" name="number" value="{{ old('number') }}" maxlength="40" class="brutal-input" placeholder="Contoh: SRV-20261016-0001" autocomplete="off" required autofocus></x-field>
        <x-field label="Kode cek status" name="tracking_code" required hint="Kode 24 karakter pada nota penerimaan. Huruf besar/kecil tidak berpengaruh."><input id="tracking_code" name="tracking_code" type="text" minlength="24" maxlength="24" pattern="[A-Za-z0-9]{24}" class="brutal-input font-mono uppercase tracking-widest" placeholder="MASUKKAN KODE DARI NOTA" autocomplete="off" autocapitalize="characters" spellcheck="false" required></x-field>
        <x-button variant="primary" type="submit" size="lg" class="w-full"><x-icon name="search" class="h-5 w-5"/>Lihat status</x-button>
        <p class="text-center text-xs text-ink/60">Jangan bagikan kode cek status kepada orang lain. Informasi pelanggan dan keluhan tidak ditampilkan di halaman publik.</p>
    </form>
</div>
@endsection
