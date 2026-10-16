<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $store['theme_color'] ?? '#FFD93D' }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    <title>@yield('title', 'Dashboard') · {{ $store['short_name'] ?? 'Kasir' }}</title>
    <style>:root { --color-primary: {{ $store['theme_color'] ?? '#FFD93D' }}; }</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-cream text-ink">
<div class="min-h-screen lg:grid lg:grid-cols-[264px_minmax(0,1fr)]">
    <aside class="sticky top-0 hidden h-screen flex-col border-r-[3px] border-ink bg-cream px-4 py-5 lg:flex">
        <a href="{{ route('dashboard') }}" class="mb-7 flex items-center gap-3 rounded-card border-2 border-ink bg-white p-3 shadow-brutal-sm">
            @if(!empty($store['logo_path']))
                <img src="{{ asset('storage/'.$store['logo_path']) }}" alt="Logo {{ $store['short_name'] }}" class="h-12 w-12 rounded-button border-2 border-ink object-contain">
            @else
                <span class="grid h-12 w-12 place-items-center rounded-button border-2 border-ink bg-primary text-sm font-extrabold shadow-brutal-sm">{{ strtoupper(\Illuminate\Support\Str::substr($store['short_name'] ?? 'Toko', 0, 2)) }}</span>
            @endif
            <span class="min-w-0"><span class="block truncate text-lg font-extrabold leading-tight">{{ $store['short_name'] ?? 'Toko' }}</span><span class="block truncate text-xs text-ink/70">{{ $store['tagline'] ?? '' }}</span></span>
        </a>
        @include('partials.sidebar-nav')
        <div class="mt-auto border-t-2 border-ink pt-4">
            <div class="mb-3 flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-full border-2 border-ink bg-info font-extrabold">{{ strtoupper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}</span>
                <div class="min-w-0"><p class="truncate text-sm font-extrabold">{{ auth()->user()->name }}</p><p class="text-xs font-semibold text-ink/70">{{ auth()->user()->isAdmin() ? 'Admin / Pemilik' : 'Kasir' }}</p></div>
            </div>
            <form method="POST" action="{{ route('logout') }}">@csrf <button class="nav-link w-full" type="submit"><x-icon name="logout" class="h-5 w-5"/>Keluar</button></form>
        </div>
    </aside>

    <div class="min-w-0">
        <header class="sticky top-0 z-30 flex min-h-[68px] items-center justify-between border-b-2 border-ink bg-cream/95 px-4 py-2 backdrop-blur sm:px-6 lg:hidden">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2">
                @if(!empty($store['logo_path']))
                    <img src="{{ asset('storage/'.$store['logo_path']) }}" alt="Logo {{ $store['short_name'] }}" class="h-10 w-10 rounded-button border-2 border-ink object-contain">
                @else
                    <span class="grid h-10 w-10 place-items-center rounded-button border-2 border-ink bg-primary text-sm font-extrabold">{{ strtoupper(\Illuminate\Support\Str::substr($store['short_name'] ?? 'Toko', 0, 2)) }}</span>
                @endif
                <span class="truncate font-extrabold">{{ $store['short_name'] ?? 'Toko' }}</span>
            </a>
            <div class="flex items-center gap-2">
                <span class="hidden max-w-32 truncate text-xs font-semibold sm:inline">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf <button class="grid h-11 w-11 place-items-center rounded-button border-2 border-ink bg-white" type="submit" aria-label="Keluar"><x-icon name="logout" class="h-5 w-5"/></button></form>
            </div>
        </header>
        <main class="mx-auto min-h-[calc(100vh-68px)] w-full max-w-[1600px] px-4 pb-32 pt-5 sm:px-6 sm:pt-7 lg:px-8 lg:pb-10">
            <div id="pwa-install-banner" hidden class="mb-5 flex flex-col gap-3 rounded-card border-2 border-ink bg-info p-4 shadow-brutal-sm sm:flex-row sm:items-center sm:justify-between">
                <div><p class="font-extrabold">Pasang aplikasi kasir</p><p class="text-sm">Buka aplikasi kasir dari layar utama perangkat Anda.</p></div>
                <div class="flex gap-2"><button id="pwa-install-button" type="button" class="brutal-btn bg-primary">Pasang</button><button id="pwa-dismiss-button" type="button" class="brutal-btn bg-white" aria-label="Tutup pemberitahuan">Nanti</button></div>
            </div>
            @yield('content')
        </main>
    </div>
</div>
@include('partials.mobile-nav')
<x-toast-stack />
@stack('scripts')
</body>
</html>
