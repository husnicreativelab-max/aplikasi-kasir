@extends('layouts.public-status')
@section('title', 'Status '.$service->number)
@section('content')
@php
    $statusVariants = ['Diterima' => 'info', 'Dicek' => 'sky', 'Menunggu Konfirmasi' => 'warning', 'Dikerjakan' => 'primary', 'Selesai' => 'success', 'Diambil' => 'muted', 'Batal' => 'danger'];
    $lastUpdate = $history->last()?->created_at ?? $service->updated_at;
    $whatsappUrl = \App\Support\WhatsAppLink::url($store['whatsapp'] ?? null, 'Halo, saya ingin menanyakan service '.$service->number.'.');
@endphp
<div class="mx-auto max-w-2xl">
    <a href="{{ route('public.services.index') }}" class="inline-flex min-h-10 items-center gap-2 text-sm font-bold underline decoration-2 underline-offset-4">← Cek service lain</a>
    <section class="brutal-card mt-3 overflow-hidden">
        <header class="border-b-2 border-ink bg-info p-5 sm:p-6"><p class="text-sm font-bold text-ink/70">Nomor service</p><h1 class="mt-1 text-2xl font-extrabold sm:text-3xl">{{ $service->number }}</h1><p class="mt-1 text-sm">{{ $service->device_type }} · {{ $service->brand_model }}</p></header>
        <div class="space-y-5 p-5 sm:p-6">
            <div class="rounded-card border-2 border-ink bg-white p-4 text-center shadow-brutal-sm"><p class="text-xs font-extrabold uppercase tracking-widest text-ink/60">Status saat ini</p><div class="mt-3"><x-badge :variant="$statusVariants[$service->status] ?? 'neutral'" class="text-base">{{ $service->status }}</x-badge></div><p class="mt-2 text-xs text-ink/60">Pembaruan {{ $lastUpdate->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }} WIB</p></div>
            <div class="grid gap-3 sm:grid-cols-2"><div class="rounded-button border-2 border-ink bg-cream p-3"><p class="text-xs font-bold text-ink/60">Tanggal diterima</p><p class="mt-1 font-extrabold">{{ $service->received_at->timezone(config('app.timezone'))->translatedFormat('d M Y') }}</p></div><div class="rounded-button border-2 border-ink bg-cream p-3"><p class="text-xs font-bold text-ink/60">Perkiraan selesai</p><p class="mt-1 font-extrabold">{{ $service->completed_at?->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') ?? 'Belum selesai' }}</p></div></div>
            <div><h2 class="font-extrabold">Riwayat status</h2><ol class="mt-3 space-y-0 rounded-button border-2 border-ink bg-white p-4">
                @forelse($history as $log)
                    <li class="relative border-l-2 border-ink/20 pb-4 pl-5 last:border-l-0 last:pb-0"><span class="absolute -left-[7px] top-1 h-3 w-3 rounded-full border-2 border-ink bg-primary"></span><p class="font-extrabold">{{ $log->status }}</p><p class="text-xs text-ink/60">{{ $log->created_at->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }} WIB</p></li>
                @empty
                    <li class="text-sm text-ink/60">Belum ada pembaruan status.</li>
                @endforelse
            </ol></div>
            @if($service->status === 'Batal')<div class="rounded-button border-2 border-ink bg-danger/20 p-3 text-sm font-semibold">Service ini telah dibatalkan. Silakan hubungi toko untuk informasi lebih lanjut.</div>@elseif($service->status === 'Diambil')<div class="rounded-button border-2 border-ink bg-success/30 p-3 text-sm font-semibold">Perangkat telah diserahkan. Terima kasih telah menggunakan layanan kami.</div>@endif
            @if($whatsappUrl)<a class="brutal-btn w-full bg-success sm:w-auto" href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"><x-icon name="message" class="h-5 w-5"/>Hubungi toko via WhatsApp</a>@endif
        </div>
    </section>
</div>
@endsection
