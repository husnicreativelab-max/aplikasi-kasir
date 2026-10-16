@extends('layouts.auth')
@section('title', 'Ganti kata sandi')
@section('content')
<x-card title="Ganti kata sandi" icon="settings" tone="warning">
    <p class="mb-5 text-sm font-semibold">Kata sandi sementara harus diganti sebelum Anda melanjutkan.</p>
    <form method="POST" action="{{ route('password.change.update') }}" class="space-y-4">
        @csrf @method('PUT')
        <x-field label="Kata sandi saat ini" name="current_password" required><input id="current_password" name="current_password" type="password" class="brutal-input" autocomplete="current-password" required autofocus></x-field>
        <x-field label="Kata sandi baru" name="password" required hint="Minimal 10 karakter dan harus berisi huruf serta angka."><input id="password" name="password" type="password" class="brutal-input" autocomplete="new-password" required></x-field>
        <x-field label="Ulangi kata sandi baru" name="password_confirmation" required><input id="password_confirmation" name="password_confirmation" type="password" class="brutal-input" autocomplete="new-password" required></x-field>
        <x-button variant="primary" type="submit" size="lg" class="w-full">Simpan kata sandi</x-button>
    </form>
</x-card>
@endsection
