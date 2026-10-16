@php
    $paperWidth = in_array((string) ($store['receipt_width_mm'] ?? '58'), ['58', '80'], true) ? $store['receipt_width_mm'] : '58';
    $paymentLabels = ['tunai' => 'Tunai', 'transfer' => 'Transfer', 'qris' => 'QRIS (manual)'];
    $whatsapp = preg_replace('/[^0-9]/', '', (string) ($store['whatsapp'] ?? ''));
    $whatsappText = rawurlencode('Terima kasih telah berbelanja di '.($store['store_name'] ?? 'toko kami').'. Nomor transaksi '.$sale->number.'. Total Rp '.number_format($sale->total, 0, ',', '.').'.');
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Struk {{ $sale->number }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: {{ $paperWidth }}mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body { margin:0; padding:0; background:#eee; color:#000; font-family:'Courier New',monospace; }
        .receipt { width:{{ $paperWidth }}mm; max-width:100%; min-height:100vh; margin:0 auto; padding:4mm; background:#fff; font-size:11px; line-height:1.35; }
        .center { text-align:center; } .right { text-align:right; } .bold { font-weight:bold; }
        .rule { border:0; border-top:1px dashed #000; margin:3mm 0; }
        .shop-logo { max-width:28mm; max-height:18mm; object-fit:contain; filter:grayscale(1); }
        .shop-name { margin:2mm 0 0; font-size:15px; line-height:1.2; font-weight:bold; text-transform:uppercase; }
        .receipt-table { width:100%; border-collapse:collapse; }
        .receipt-table th, .receipt-table td { padding:1mm 0; vertical-align:top; }
        .item-name { font-weight:bold; overflow-wrap:anywhere; }
        .totals td { padding:0.7mm 0; }
        .total-line { font-size:14px; font-weight:bold; }
        .receipt-footer { margin-top:3mm; white-space:pre-line; }
        .action-bar { width:min(calc(100% - 24px),760px); margin:12px auto; display:flex; flex-wrap:wrap; justify-content:center; gap:8px; }
        @media print { body { background:#fff; } .receipt { min-height:0; margin:0; } .no-print { display:none !important; } }
    </style>
</head>
<body>
    <div class="action-bar no-print">
        <button onclick="window.print()" class="brutal-btn bg-primary"><x-icon name="print" class="h-5 w-5"/>Cetak struk</button>
        @if($whatsapp && $sale->status === 'completed')<a target="_blank" rel="noopener" href="https://wa.me/{{ $whatsapp }}?text={{ $whatsappText }}" class="brutal-btn bg-success">Kirim WhatsApp</a>@endif
        <a href="{{ route('sales.index') }}" class="brutal-btn bg-white">Riwayat transaksi</a>
        <a href="{{ route('pos.index') }}" class="brutal-btn bg-white">Transaksi baru</a>
    </div>
    @if(auth()->user()->isAdmin() && $sale->status === 'completed')
        <form method="POST" action="{{ route('sales.cancel', $sale) }}" class="no-print mx-auto mb-3 w-full max-w-2xl rounded-card border-2 border-ink bg-danger/20 p-4" onsubmit="return window.confirm('Batalkan transaksi ini dan kembalikan stok barang?')">
            @csrf
            <h2 class="font-extrabold">Pembatalan / retur seluruh transaksi</h2>
            <p class="mt-1 text-sm">Hanya Admin. Stok barang akan dikembalikan dan transaksi dikeluarkan dari laporan. Pengembalian uang tidak diproses otomatis.</p>
            <label class="mt-3 block text-sm font-bold" for="cancellation-reason">Alasan pembatalan <span class="text-danger">*</span></label>
            <textarea id="cancellation-reason" name="reason" rows="2" minlength="3" maxlength="1000" class="brutal-input mt-1" required>{{ old('reason') }}</textarea>
            @error('reason')<p class="form-error">{{ $message }}</p>@enderror
            <button type="submit" class="brutal-btn mt-3 bg-danger">Batalkan transaksi &amp; kembalikan stok</button>
        </form>
    @endif
    <main class="receipt">
        <header class="center">
            @if(($store['show_receipt_logo'] ?? '1') && !empty($store['logo_path']))<img class="shop-logo" src="{{ asset('storage/'.$store['logo_path']) }}" alt="Logo {{ $store['short_name'] ?? '' }}">@endif
            <h1 class="shop-name">{{ $store['store_name'] ?? 'Nama Toko' }}</h1>
            @if(!empty($store['tagline']))<div>{{ $store['tagline'] }}</div>@endif
            @if(!empty($store['address']))<div>{{ $store['address'] }}</div>@endif
            @if(!empty($store['phone']))<div>Telp: {{ $store['phone'] }}</div>@endif
        </header>
        <hr class="rule">
        <table class="receipt-table"><tbody>
            <tr><td>No. transaksi</td><td class="right bold">{{ $sale->number }}</td></tr>
            <tr><td>Tanggal</td><td class="right">{{ $sale->sold_at->timezone(config('app.timezone'))->translatedFormat('d/m/Y H:i') }}</td></tr>
            <tr><td>Kasir</td><td class="right">{{ $sale->user?->name ?? 'Kasir' }}</td></tr>
        </tbody></table>
        @if($sale->status === 'cancelled')
            <hr class="rule">
            <div class="center bold">TRANSAKSI DIBATALKAN / DIRETUR</div>
            <div>Alasan: {{ $sale->cancellation_reason }}</div>
            <div>Oleh: {{ $sale->cancelledBy?->name ?? 'Admin' }} · {{ $sale->cancelled_at?->timezone(config('app.timezone'))->translatedFormat('d/m/Y H:i') }}</div>
        @endif
        <hr class="rule">
        <table class="receipt-table"><tbody>
            @foreach($sale->items as $item)
                <tr><td colspan="2" class="item-name">{{ $item->product_name }}</td></tr>
                <tr><td>{{ rtrim(rtrim(number_format($item->quantity, 3, ',', '.'), '0'), ',') }} {{ $item->unit }} × Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td><td class="right">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td></tr>
            @endforeach
        </tbody></table>
        <hr class="rule">
        <table class="receipt-table totals"><tbody>
            <tr><td>Subtotal</td><td class="right">Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</td></tr>
            @if($sale->discount_total > 0)<tr><td>Diskon @if($sale->discount_type === 'persen')({{ rtrim(rtrim(number_format($sale->discount_value, 2, ',', '.'), '0'), ',') }}%)@endif</td><td class="right">- Rp {{ number_format($sale->discount_total, 0, ',', '.') }}</td></tr>@endif
            <tr class="total-line"><td>TOTAL</td><td class="right">Rp {{ number_format($sale->total, 0, ',', '.') }}</td></tr>
            <tr><td>Metode bayar</td><td class="right">{{ $paymentLabels[$sale->payment_method] ?? ucfirst($sale->payment_method) }}</td></tr>
            <tr><td>Bayar</td><td class="right">Rp {{ number_format($sale->amount_paid, 0, ',', '.') }}</td></tr>
            @if($sale->payment_method === 'tunai')<tr class="bold"><td>Kembalian</td><td class="right">Rp {{ number_format($sale->change_due, 0, ',', '.') }}</td></tr>@endif
        </tbody></table>
        <hr class="rule">
        <footer class="center receipt-footer">{{ $store['receipt_footer'] ?? '' }}</footer>
    </main>
    <script>window.addEventListener('load', () => { if (new URLSearchParams(window.location.search).has('cetak')) window.print(); });</script>
</body>
</html>
