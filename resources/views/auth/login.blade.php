@extends('layouts.auth')
@section('title', 'Masuk')
@section('content')
<x-card title="Masuk ke aplikasi" icon="user" tone="primary" class="w-full">
    <p class="mb-5 text-sm text-ink/70">Gunakan akun yang sudah dibuat oleh pemilik toko.</p>
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <x-field label="Email" name="email" required>
            <input id="email" name="email" type="email" value="{{ old('email') }}" class="brutal-input" autocomplete="username" inputmode="email" required autofocus>
        </x-field>
        <x-field label="Kata sandi" name="password" required>
            <input id="password" name="password" type="password" class="brutal-input" autocomplete="current-password" required>
        </x-field>
        <label class="flex min-h-11 items-center gap-3 text-sm font-semibold"><input type="checkbox" name="remember" value="1" class="h-5 w-5 rounded border-2 border-ink text-success focus:ring-primary"> Ingat saya di perangkat ini</label>
        <x-button variant="primary" type="submit" size="lg" class="w-full"><x-icon name="check" class="h-5 w-5"/>Masuk</x-button>
    </form>
</x-card>
@endsection
