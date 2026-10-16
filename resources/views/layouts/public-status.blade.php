<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ $store['theme_color'] ?? '#FFD93D' }}">
    <title>@yield('title', 'Cek status service') · {{ $store['short_name'] ?? 'Toko' }}</title>
    <style>:root { --color-primary: {{ $store['theme_color'] ?? '#FFD93D' }}; }</style>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-cream text-ink">
    <header class="border-b-[3px] border-ink bg-white">
        <div class="mx-auto flex max-w-4xl items-center justify-between gap-3 px-4 py-4 sm:px-6">
            <a href="{{ route('public.services.index') }}" class="flex min-w-0 items-center gap-3">
                @if(!empty($store['logo_path']))<img src="{{ asset('storage/'.$store['logo_path']) }}" alt="Logo {{ $store['short_name'] ?? '' }}" class="h-11 w-11 rounded-button border-2 border-ink object-contain">@else<span class="grid h-11 w-11 place-items-center rounded-button border-2 border-ink bg-primary font-extrabold">{{ strtoupper(\Illuminate\Support\Str::substr($store['short_name'] ?? 'Toko', 0, 2)) }}</span>@endif
                <span class="min-w-0"><span class="block truncate font-extrabold">{{ $store['store_name'] ?? 'Toko' }}</span><span class="block text-xs text-ink/65">Cek status service</span></span>
            </a>
            @if(!empty($store['phone']))<a class="text-right text-sm font-bold underline" href="tel:{{ preg_replace('/[^0-9+]/', '', $store['phone']) }}">Hubungi toko<br><span class="font-normal">{{ $store['phone'] }}</span></a>@endif
        </div>
    </header>
    <main class="mx-auto min-h-[calc(100vh-80px)] w-full max-w-4xl px-4 py-6 sm:px-6 sm:py-10">
        @yield('content')
    </main>
    <footer class="border-t-2 border-ink/20 px-4 py-5 text-center text-xs text-ink/60">{{ $store['short_name'] ?? 'Toko' }} · Informasi service ini hanya dapat dilihat menggunakan kode pada nota.</footer>
</body>
</html>
