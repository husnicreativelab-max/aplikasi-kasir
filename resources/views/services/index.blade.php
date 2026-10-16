@extends('layouts.app')
@section('title', 'Service komputer')
@section('content')
@php
    $statusVariants = ['Diterima' => 'info', 'Dicek' => 'sky', 'Menunggu Konfirmasi' => 'warning', 'Dikerjakan' => 'primary', 'Selesai' => 'success', 'Diambil' => 'muted', 'Batal' => 'danger'];
@endphp
<div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
    <div><p class="text-sm font-bold text-ink/60">Penerimaan dan pelacakan perbaikan</p><h1 class="text-3xl font-extrabold">Service komputer</h1><p class="mt-1 text-sm text-ink/70">Kelola keluhan, perangkat, status, sparepart, jasa, dan pembayaran.</p></div>
    <x-button as="a" href="{{ route('services.create') }}" variant="primary"><x-icon name="plus" class="h-5 w-5"/>Terima perangkat</x-button>
</div>

<form method="GET" action="{{ route('services.index') }}" class="brutal-card mb-5 grid gap-3 p-4 sm:grid-cols-[minmax(0,1fr)_220px_auto] sm:items-end">
    <x-field label="Cari nomor / pelanggan / perangkat" name="q"><input id="q" name="q" type="search" value="{{ request('q') }}" class="brutal-input" placeholder="SRV-..., nama, telepon, merek / tipe"></x-field>
    <x-field label="Status" name="status"><select id="status" name="status" class="brutal-input"><option value="">Semua status</option>@foreach(\App\Models\Service::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></x-field>
    <x-button variant="neutral" type="submit"><x-icon name="search" class="h-5 w-5"/>Cari service</x-button>
</form>

<div class="hidden overflow-hidden rounded-card border-2 border-ink bg-white shadow-brutal md:block">
    <div class="overflow-x-auto"><table class="w-full min-w-[850px] text-sm">
        <thead class="table-head"><tr><th class="px-4 py-3">Nomor / tanggal</th><th class="px-4 py-3">Pelanggan</th><th class="px-4 py-3">Perangkat / keluhan</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Estimasi / DP</th><th class="px-4 py-3">Diterima oleh</th><th class="px-4 py-3"></th></tr></thead>
        <tbody>
        @forelse($services as $service)
            <tr class="border-t border-ink/20 align-top hover:bg-cream/60">
                <td class="px-4 py-3"><p class="font-extrabold">{{ $service->number }}</p><p class="text-xs text-ink/60">{{ $service->received_at->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }} WIB</p></td>
                <td class="px-4 py-3"><p class="font-bold">{{ $service->customer_name }}</p><p class="text-xs text-ink/60">{{ $service->customer_phone }}</p></td>
                <td class="max-w-xs px-4 py-3"><p class="font-bold">{{ $service->brand_model }}</p><p class="line-clamp-2 text-xs text-ink/60">{{ $service->complaint }}</p></td>
                <td class="px-4 py-3"><x-badge :variant="$statusVariants[$service->status] ?? 'neutral'">{{ $service->status }}</x-badge></td>
                <td class="px-4 py-3"><p>Estimasi Rp {{ number_format($service->estimated_cost, 0, ',', '.') }}</p><p class="text-xs text-ink/65">DP Rp {{ number_format($service->deposit_amount, 0, ',', '.') }}</p></td>
                <td class="px-4 py-3">{{ $service->receivedBy?->name ?? 'Akun dihapus' }}</td>
                <td class="px-4 py-3"><x-button as="a" href="{{ route('services.show', $service) }}" variant="info" size="sm">Buka</x-button></td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-4 py-12 text-center"><x-icon name="tool" class="mx-auto h-8 w-8"/><p class="mt-2 font-extrabold">Belum ada data service</p><p class="text-sm text-ink/65">Terima perangkat pertama untuk mulai mencatat service.</p></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($services->hasPages())<div class="border-t-2 border-ink bg-cream p-3">{{ $services->links() }}</div>@endif
</div>
<div class="space-y-3 md:hidden">
    @forelse($services as $service)
        <article class="brutal-card p-4">
            <div class="flex items-start justify-between gap-3"><div><p class="font-extrabold">{{ $service->number }}</p><p class="text-xs text-ink/60">{{ $service->received_at->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }} WIB</p></div><x-badge :variant="$statusVariants[$service->status] ?? 'neutral'">{{ $service->status }}</x-badge></div>
            <div class="mt-3 border-t border-ink/15 pt-3"><p class="font-extrabold">{{ $service->customer_name }} <span class="font-semibold text-ink/65">· {{ $service->customer_phone }}</span></p><p class="mt-1 text-sm font-bold">{{ $service->brand_model }}</p><p class="mt-1 line-clamp-2 text-sm text-ink/70">{{ $service->complaint }}</p></div>
            <div class="mt-3 flex items-center justify-between gap-2"><p class="text-xs text-ink/65">Estimasi Rp {{ number_format($service->estimated_cost, 0, ',', '.') }} · DP Rp {{ number_format($service->deposit_amount, 0, ',', '.') }}</p><x-button as="a" href="{{ route('services.show', $service) }}" variant="info" size="sm">Buka</x-button></div>
        </article>
    @empty
        <div class="brutal-card p-8 text-center"><x-icon name="tool" class="mx-auto h-8 w-8"/><p class="mt-2 font-extrabold">Belum ada data service</p><x-button as="a" href="{{ route('services.create') }}" variant="primary" class="mt-3">Terima perangkat</x-button></div>
    @endforelse
    @if($services->hasPages())<div class="rounded-card border-2 border-ink bg-white p-3">{{ $services->links() }}</div>@endif
</div>
@endsection
