<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $store['theme_color'] ?? '#FFD93D' }}">
    <title>@yield('title', 'Masuk') · {{ $store['short_name'] ?? 'Kasir' }}</title>
    <style>:root { --color-primary: {{ $store['theme_color'] ?? '#FFD93D' }}; }</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-cream px-4 py-8 text-ink">
    <div class="mx-auto flex min-h-[85vh] w-full max-w-md flex-col justify-center">
        <a href="{{ route('login') }}" class="mb-6 flex items-center justify-center gap-3">
            @if(!empty($store['logo_path']))
                <img src="{{ asset('storage/'.$store['logo_path']) }}" alt="Logo {{ $store['short_name'] }}" class="h-14 w-14 rounded-button border-2 border-ink bg-white object-contain shadow-brutal-sm">
            @else
                <span class="grid h-14 w-14 place-items-center rounded-button border-2 border-ink bg-primary text-lg font-extrabold shadow-brutal-sm">{{ strtoupper(\Illuminate\Support\Str::substr($store['short_name'] ?? 'Toko', 0, 2)) }}</span>
            @endif
            <span class="text-left"><span class="block text-xl font-extrabold">{{ $store['store_name'] ?? 'Nama Toko' }}</span><span class="block text-sm font-semibold text-ink/70">{{ $store['tagline'] ?? '' }}</span></span>
        </a>
        @yield('content')
        <p class="mt-6 text-center text-xs font-semibold text-ink/60">Aplikasi Kasir · zona waktu Asia/Jakarta</p>
    </div>
    <x-toast-stack />
</body>
</html>
