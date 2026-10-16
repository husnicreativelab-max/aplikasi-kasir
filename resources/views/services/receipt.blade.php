@php
    $paperWidth = in_array((string) ($store['receipt_width_mm'] ?? '58'), ['58', '80'], true) ? $store['receipt_width_mm'] : '58';
    $isRelease = $mode === 'ambil';
    $totalCost = $service->totalCost();
    $balanceDue = $service->balanceDue();
    $paymentLabels = ['tunai' => 'Tunai', 'transfer' => 'Transfer', 'qris' => 'QRIS'];
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Nota {{ $isRelease ? 'pengambilan' : 'penerimaan' }} {{ $service->number }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: {{ $paperWidth }}mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; background: #eee; color: #000; font-family: 'Courier New', monospace; }
        .ticket { width: {{ $paperWidth }}mm; max-width: 100%; min-height: 100vh; margin: 0 auto; padding: 4mm; background: #fff; font-size: 11px; line-height: 1.38; }
        .center { text-align: center; } .right { text-align: right; } .bold { font-weight: bold; }
        .title { margin: 2mm 0; font-size: 15px; line-height: 1.2; text-transform: uppercase; }
        .subtitle { font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .rule { border: 0; border-top: 1px dashed #000; margin: 3mm 0; }
        .ticket-table { width: 100%; border-collapse: collapse; }
        .ticket-table th, .ticket-table td { padding: 1mm 0; vertical-align: top; }
        .ticket-table th { text-align: left; }
        .wrap { white-space: pre-line; overflow-wrap: anywhere; }
        .actions { width: min(calc(100% - 24px), 760px); margin: 12px auto; display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; font-family: system-ui, sans-serif; }
        .action { min-height: 42px; padding: 8px 14px; border: 1px solid #111; border-radius: 4px; background: white; color: #111; font-weight: 700; text-decoration: none; cursor: pointer; }
        .signature { height: 18mm; vertical-align: bottom !important; }
        @media print { body { background: white; } .ticket { min-height: 0; margin: 0; } .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="actions no-print">
        <button class="action" onclick="window.print()">Cetak nota</button>
        <a class="action" href="{{ route('services.show', $service) }}">Kembali ke service</a>
    </div>
    <main class="ticket">
        <header class="center">
            <h1 class="title">{{ $store['store_name'] ?? 'Nama Toko' }}</h1>
            @if(!empty($store['address']))<div>{{ $store['address'] }}</div>@endif
            @if(!empty($store['phone']))<div>Telp: {{ $store['phone'] }}</div>@endif
            <hr class="rule">
            <div class="subtitle">Nota {{ $isRelease ? 'pengambilan service' : 'penerimaan service' }}</div>
            <div class="bold">{{ $service->number }}</div>
        </header>
        <hr class="rule">
        <table class="ticket-table"><tbody>
            <tr><td>Tanggal terima</td><td class="right">{{ $service->received_at->timezone(config('app.timezone'))->translatedFormat('d/m/Y H:i') }}</td></tr>
            <tr><td>Pelanggan</td><td class="right bold">{{ $service->customer_name }}</td></tr>
            <tr><td>Telepon</td><td class="right">{{ $service->customer_phone }}</td></tr>
            <tr><td>Perangkat</td><td class="right">{{ $service->device_type }}</td></tr>
            <tr><td>Merek / tipe</td><td class="right bold">{{ $service->brand_model }}</td></tr>
            @if($service->serial_number)<tr><td>Nomor seri</td><td class="right">{{ $service->serial_number }}</td></tr>@endif
        </tbody></table>
        <hr class="rule">
        <div class="bold">Keluhan pelanggan</div><div class="wrap">{{ $service->complaint }}</div>
        <div class="bold" style="margin-top:2mm">Aksesori / kondisi awal</div><div class="wrap">{{ $service->accessories ?: 'Tidak ada aksesori yang dicatat.' }}</div>
        <hr class="rule">
        @if(!$isRelease)
            <table class="ticket-table"><tbody>
                <tr><td>Status</td><td class="right bold">{{ $service->status }}</td></tr>
                <tr><td>Estimasi awal</td><td class="right">Rp {{ number_format($service->estimated_cost, 0, ',', '.') }}</td></tr>
                <tr><td>Uang muka</td><td class="right">Rp {{ number_format($service->deposit_amount, 0, ',', '.') }}</td></tr>
                @if($service->deposit_amount > 0 && $service->payments->isNotEmpty())<tr><td>Metode DP</td><td class="right">{{ $paymentLabels[$service->payments->first()->payment_method] ?? ucfirst($service->payments->first()->payment_method) }}</td></tr>@endif
                <tr><td>Sisa estimasi</td><td class="right">Rp {{ number_format($balanceDue, 0, ',', '.') }}</td></tr>
            </tbody></table>
            <p class="wrap">Estimasi dapat berubah setelah pemeriksaan. Kami akan menghubungi pelanggan sebelum pengerjaan yang memerlukan persetujuan biaya tambahan.</p>
        @else
            <table class="ticket-table"><thead><tr><th colspan="2">Rincian biaya</th></tr></thead><tbody>
                @forelse($service->items as $item)
                    <tr><td colspan="2" class="bold">{{ $item->description }}</td></tr>
                    <tr><td>{{ rtrim(rtrim(number_format($item->quantity, 3, ',', '.'), '0'), ',') }} {{ $item->unit }} × Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td><td class="right">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td></tr>
                @empty
                    <tr><td>Biaya sesuai estimasi</td><td class="right">Rp {{ number_format($totalCost, 0, ',', '.') }}</td></tr>
                @endforelse
            </tbody><tfoot>
                <tr><td class="bold">Total biaya</td><td class="right bold">Rp {{ number_format($totalCost, 0, ',', '.') }}</td></tr>
                <tr><td>Total pembayaran</td><td class="right">Rp {{ number_format($service->paid_total, 0, ',', '.') }}</td></tr>
                <tr><td class="bold">Sisa tagihan</td><td class="right bold">Rp {{ number_format($balanceDue, 0, ',', '.') }}</td></tr>
            </tfoot></table>
            <p class="bold">Pembayaran</p>
            @forelse($service->payments as $payment)
                <div>{{ $payment->paid_at->timezone(config('app.timezone'))->translatedFormat('d/m/Y H:i') }} · {{ $paymentLabels[$payment->payment_method] ?? ucfirst($payment->payment_method) }}: Rp {{ number_format($payment->amount, 0, ',', '.') }}</div>
            @empty
                <div>Belum ada pembayaran.</div>
            @endforelse
            <p style="margin-top:2mm">Status serah terima: {{ $service->status }}{{ $service->taken_at ? ' · '.$service->taken_at->timezone(config('app.timezone'))->translatedFormat('d/m/Y H:i') : '' }}</p>
        @endif
        <hr class="rule">
        @if(!empty($store['service_terms']))<div class="wrap">{{ $store['service_terms'] }}</div><hr class="rule">@endif
        <p class="center">{{ $store['receipt_footer'] ?? 'Simpan nota ini sebagai bukti penerimaan service.' }}</p>
        <table class="ticket-table" style="margin-top:3mm"><tbody><tr><td class="center signature">Pelanggan<br><br><br>(________________)</td><td class="center signature">Petugas<br><br><br>({{ $service->receivedBy?->name ?? '________________' }})</td></tr></tbody></table>
    </main>
    <script>window.addEventListener('load', () => { if (new URLSearchParams(window.location.search).has('cetak')) window.print(); });</script>
</body>
</html>
