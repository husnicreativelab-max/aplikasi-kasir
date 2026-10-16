@extends('layouts.app')
@section('title', 'Kelola pengguna')
@section('content')
<div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-end"><div><a href="{{ route('settings.edit') }}" class="inline-flex min-h-9 items-center gap-2 text-sm font-bold underline decoration-2 underline-offset-4">← Pengaturan</a><h1 class="mt-2 text-3xl font-extrabold">Kelola pengguna</h1><p class="mt-1 text-sm text-ink/70">Tambah Admin atau Kasir, nonaktifkan akun, dan reset kata sandi.</p></div><x-button as="a" href="{{ route('users.create') }}" variant="primary"><x-icon name="plus" class="h-5 w-5"/>Tambah pengguna</x-button></div>
<div class="space-y-3">
    @forelse($users as $user)
        <article class="brutal-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-center gap-3"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-button border-2 border-ink {{ $user->role === 'admin' ? 'bg-info' : 'bg-success' }} font-extrabold">{{ strtoupper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}</span><div class="min-w-0"><h2 class="truncate font-extrabold">{{ $user->name }} @if($user->is(auth()->user()))<span class="text-xs font-semibold">(Anda)</span>@endif</h2><p class="truncate text-sm text-ink/65">{{ $user->email }}</p><div class="mt-1 flex flex-wrap gap-2"><x-badge :variant="$user->role === 'admin' ? 'info' : 'success'">{{ $user->role === 'admin' ? 'Admin' : 'Kasir' }}</x-badge><x-badge :variant="$user->is_active ? 'success' : 'danger'">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>@if($user->must_change_password)<x-badge variant="warning">Wajib ganti sandi</x-badge>@endif</div></div></div>
            <div class="flex flex-wrap gap-2"><x-button as="a" href="{{ route('users.edit', $user) }}" variant="info" size="sm">Ubah / reset sandi</x-button>@if(!$user->is(auth()->user()))<form method="POST" action="{{ route('users.toggle-status', $user) }}">@csrf @method('PATCH')<x-button variant="{{ $user->is_active ? 'danger' : 'success' }}" type="submit" size="sm">{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</x-button></form>@endif</div>
        </article>
    @empty
        <x-card><p class="text-center font-bold">Belum ada pengguna.</p></x-card>
    @endforelse
    @if($users->hasPages())<div class="rounded-card border-2 border-ink bg-white p-3">{{ $users->links() }}</div>@endif
</div>
@endsection
