<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Label Barcode · {{ $store['short_name'] ?? 'Toko' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .label-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:6mm; }
        .label-item { break-inside:avoid; min-height:32mm; border:1px dashed #1A1A1A; padding:3mm; text-align:center; display:flex; flex-direction:column; justify-content:center; align-items:center; }
        .label-item svg { width:100%; max-width:48mm; height:17mm; }
        @page { size:A4; margin:10mm; }
        @media print { .no-print { display:none !important; } body { background:#fff !important; color:#000 !important; } .label-item { border-color:#888; } }
    </style>
</head>
<body class="bg-cream p-4 text-ink sm:p-8">
    <div class="no-print mx-auto mb-5 flex max-w-5xl flex-wrap items-center justify-between gap-3"><div><h1 class="text-2xl font-extrabold">Label barcode Code128</h1><p class="text-sm">Atur skala cetak 100% agar ukuran label konsisten.</p></div><div class="flex gap-2"><button onclick="window.print()" class="brutal-btn bg-primary"><x-icon name="print" class="h-5 w-5"/>Cetak label</button><a href="{{ route('products.index') }}" class="brutal-btn bg-white">Kembali</a></div></div>
    <main class="label-grid mx-auto max-w-5xl">
        @foreach($products as $product)
            @if($product->barcode)
                <article class="label-item"><strong class="line-clamp-1 text-sm font-extrabold">{{ $product->name }}</strong><svg data-barcode="{{ $product->barcode }}" role="img" aria-label="Barcode {{ $product->barcode }}"></svg><span class="font-mono text-[10px]">{{ $product->barcode }}</span></article>
            @endif
        @endforeach
    </main>
</body>
</html>
