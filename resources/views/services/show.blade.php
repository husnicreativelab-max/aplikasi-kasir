@extends('layouts.app')
@section('title', 'Service '.$service->number)
@section('content')
@php
    $statusVariants = ['Diterima' => 'info', 'Dicek' => 'sky', 'Menunggu Konfirmasi' => 'warning', 'Dikerjakan' => 'primary', 'Selesai' => 'success', 'Diambil' => 'muted', 'Batal' => 'danger'];
    $nextStatuses = match ($service->status) {
        'Diterima' => ['Dicek', 'Menunggu Konfirmasi', 'Dikerjakan'],
        'Dicek' => ['Menunggu Konfirmasi', 'Dikerjakan'],
        'Menunggu Konfirmasi' => ['Dicek', 'Dikerjakan'],
        'Dikerjakan' => ['Menunggu Konfirmasi', 'Selesai'],
        'Selesai' => ['Dikerjakan', 'Diambil'],
        default => [],
    };
    $isTerminal = in_array($service->status, ['Diambil', 'Batal'], true);
    $canWork = ! in_array($service->status, ['Selesai', 'Diambil', 'Batal'], true);
    $canPay = ! $isTerminal && $service->balanceDue() > 0;
    $finalTotal = $service->totalCost();
    $balanceDue = $service->balanceDue();
    if ($balanceDue > 0) {
        $nextStatuses = array_values(array_diff($nextStatuses, ['Diambil']));
    }
@endphp
<div class="mb-5">
    <a href="{{ route('services.index') }}" class="inline-flex min-h-10 items-center gap-2 text-sm font-bold underline decoration-2 underline-offset-4">← Kembali ke daftar service</a>
    <div class="mt-2 flex flex-col justify-between gap-3 md:flex-row md:items-end">
        <div><p class="text-sm font-bold text-ink/60">Detail penerimaan dan pengerjaan</p><h1 class="text-3xl font-extrabold">{{ $service->number }}</h1><p class="mt-1 text-sm text-ink/70">Diterima {{ $service->received_at->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }} WIB</p></div>
        <div class="flex flex-wrap gap-2"><x-badge :variant="$statusVariants[$service->status] ?? 'neutral'">{{ $service->status }}</x-badge><x-button as="a" href="{{ route('services.receipt.intake', $service) }}" variant="neutral" size="sm"><x-icon name="print" class="h-4 w-4"/>Nota penerimaan</x-button>@if($service->status === 'Diambil')<x-button as="a" href="{{ route('services.receipt.release', $service) }}" variant="primary" size="sm"><x-icon name="print" class="h-4 w-4"/>Nota pengambilan</x-button>@endif</div>
    </div>
</div>

@if($service->status === 'Batal')
    <div class="mb-5 rounded-card border-2 border-ink bg-danger/30 p-4"><p class="font-extrabold">Service dibatalkan {{ $service->cancelled_at?->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }} oleh {{ $service->cancelledBy?->name ?? 'Admin' }}.</p><p class="mt-1 text-sm">Alasan: {{ $service->cancellation_reason }}</p>@if($service->paid_total > 0)<p class="mt-2 text-sm font-bold">Pembayaran Rp {{ number_format($service->paid_total, 0, ',', '.') }} tetap tercatat. Pengembalian uang tidak diproses otomatis.</p>@endif</div>
@endif

<div class="grid gap-4 lg:grid-cols-[minmax(0,1.3fr)_minmax(300px,0.7fr)]">
    <div class="space-y-4">
        <section class="brutal-card overflow-hidden"><header class="border-b-2 border-ink bg-info px-4 py-3"><h2 class="text-lg font-extrabold">Pelanggan dan perangkat</h2></header><div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5">
            <div><p class="text-xs font-bold uppercase tracking-wide text-ink/60">Pelanggan</p><p class="mt-1 text-lg font-extrabold">{{ $service->customer_name }}</p><p class="text-sm">{{ $service->customer_phone }}</p></div>
            <div><p class="text-xs font-bold uppercase tracking-wide text-ink/60">Perangkat</p><p class="mt-1 text-lg font-extrabold">{{ $service->device_type }} · {{ $service->brand_model }}</p><p class="text-sm">Nomor seri: {{ $service->serial_number ?: '—' }}</p></div>
            <div class="sm:col-span-2"><p class="text-xs font-bold uppercase tracking-wide text-ink/60">Keluhan pelanggan</p><p class="mt-1 whitespace-pre-line">{{ $service->complaint }}</p></div>
            <div><p class="text-xs font-bold uppercase tracking-wide text-ink/60">Aksesori / kondisi</p><p class="mt-1 whitespace-pre-line">{{ $service->accessories ?: 'Tidak ada catatan aksesori.' }}</p></div>
            <div><p class="text-xs font-bold uppercase tracking-wide text-ink/60">Kata sandi / PIN perangkat</p><p class="mt-1 rounded-button border border-ink/20 bg-cream p-2 font-mono">{{ $service->device_password_for_display ?: 'Tidak dicatat' }}</p><p class="mt-1 text-xs text-ink/60">Informasi rahasia staf. Tidak dicetak pada nota pelanggan.</p></div>
        </div></section>

        <section class="brutal-card overflow-hidden"><header class="flex items-center justify-between gap-2 border-b-2 border-ink bg-primary px-4 py-3"><div><h2 class="text-lg font-extrabold">Sparepart dan jasa</h2><p class="text-xs font-semibold">Total akhir mengikuti baris biaya di bawah (jika ada); jika belum ada, memakai estimasi.</p></div><x-badge variant="neutral">{{ $service->items->count() }} item</x-badge></header>
            <div class="overflow-x-auto"><table class="w-full min-w-[550px] text-sm"><thead class="table-head"><tr><th class="px-4 py-3">Jenis / keterangan</th><th class="px-4 py-3">Jumlah</th><th class="px-4 py-3">Harga</th><th class="px-4 py-3">Subtotal</th></tr></thead><tbody>
                @forelse($service->items as $item)
                    <tr class="border-t border-ink/20 align-top"><td class="px-4 py-3"><x-badge :variant="$item->item_type === 'sparepart' ? 'info' : 'warning'">{{ $item->item_type === 'sparepart' ? 'Sparepart' : 'Jasa' }}</x-badge><p class="mt-1 font-bold">{{ $item->description }}</p></td><td class="px-4 py-3">{{ rtrim(rtrim(number_format($item->quantity, 3, ',', '.'), '0'), ',') }} {{ $item->unit }}</td><td class="px-4 py-3">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td><td class="px-4 py-3 font-extrabold">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td></tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-ink/65">Belum ada sparepart atau biaya jasa yang dicatat.</td></tr>
                @endforelse
            </tbody><tfoot class="border-t-2 border-ink bg-cream"><tr><th colspan="3" class="px-4 py-3 text-right">Total biaya saat ini</th><td class="px-4 py-3 text-lg font-extrabold">Rp {{ number_format($finalTotal, 0, ',', '.') }}</td></tr></tfoot></table></div>
        </section>

        @if($canWork)
            <div class="grid gap-4 xl:grid-cols-2">
                <section class="brutal-card overflow-hidden"><header class="border-b-2 border-ink bg-sky px-4 py-3"><h2 class="font-extrabold">Tambah sparepart</h2><p class="text-xs">Stok barang akan berkurang dan mutasi tercatat.</p></header><form method="POST" action="{{ route('services.parts.store', $service) }}" class="space-y-3 p-4">@csrf
                    @if($products->isEmpty())<p class="rounded-button border border-ink/20 bg-cream p-3 text-sm">Belum ada produk barang aktif untuk sparepart.</p>@else
                        <x-field label="Pilih produk barang" name="product_id" required><select id="part_product_id" name="product_id" class="brutal-input" required><option value="">Pilih sparepart...</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>{{ $product->name }} · Rp {{ number_format($product->selling_price, 0, ',', '.') }} · stok {{ rtrim(rtrim(number_format($product->stock, 3, ',', '.'), '0'), ',') }} {{ $product->unit }}</option>@endforeach</select></x-field>
                        <div class="grid gap-3 sm:grid-cols-2"><x-field label="Jumlah" name="quantity" required><input id="part_quantity" name="quantity" type="number" min="0.001" max="999999" step="0.001" value="{{ old('quantity', 1) }}" class="brutal-input" required></x-field><x-field label="Catatan item (opsional)" name="note"><input id="part_note" name="note" value="{{ old('note') }}" maxlength="500" class="brutal-input" placeholder="Contoh: kapasitas 512 GB"></x-field></div>
                        <x-button variant="info" type="submit" class="w-full"><x-icon name="plus" class="h-5 w-5"/>Tambah sparepart</x-button>
                    @endif
                </form></section>
                <section class="brutal-card overflow-hidden"><header class="border-b-2 border-ink bg-warning px-4 py-3"><h2 class="font-extrabold">Tambah biaya jasa</h2><p class="text-xs">Jasa tidak mengurangi stok produk.</p></header><form method="POST" action="{{ route('services.labor.store', $service) }}" class="space-y-3 p-4">@csrf
                    <x-field label="Keterangan jasa" name="description" required><input id="description" name="description" value="{{ old('description') }}" maxlength="180" class="brutal-input" placeholder="Contoh: instal ulang sistem operasi" required></x-field>
                    <div class="grid gap-3 sm:grid-cols-3"><x-field label="Satuan" name="unit" required><input id="unit" name="unit" value="{{ old('unit', 'jasa') }}" maxlength="30" class="brutal-input" required></x-field><x-field label="Jumlah" name="quantity" required><input id="labor_quantity" name="quantity" type="number" min="0.001" max="999999" step="0.001" value="{{ old('quantity', 1) }}" class="brutal-input" required></x-field><x-field label="Harga (Rp)" name="unit_price" required><input id="unit_price" name="unit_price" type="number" min="0" max="999999999999" step="1" value="{{ old('unit_price') }}" class="brutal-input" required></x-field></div>
                    <x-button variant="warning" type="submit" class="w-full"><x-icon name="plus" class="h-5 w-5"/>Tambah jasa</x-button>
                </form></section>
            </div>
        @endif

        <section class="brutal-card overflow-hidden"><header class="border-b-2 border-ink bg-cream px-4 py-3"><h2 class="text-lg font-extrabold">Riwayat pembayaran</h2></header>
            <div class="divide-y divide-ink/15">
                @forelse($service->payments as $payment)
                    <div class="flex flex-col justify-between gap-2 p-4 sm:flex-row sm:items-center"><div><div class="flex flex-wrap items-center gap-2"><x-badge :variant="$payment->payment_type === 'dp' ? 'info' : 'success'">{{ $payment->payment_type === 'dp' ? 'Uang muka' : ($payment->payment_type === 'pelunasan' ? 'Pelunasan' : 'Pembayaran') }}</x-badge><span class="font-extrabold">{{ ucfirst($payment->payment_method) }}</span></div><p class="mt-1 text-xs text-ink/65">{{ $payment->paid_at->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }} · {{ $payment->user?->name ?? 'Akun dihapus' }}{{ $payment->note ? ' · '.$payment->note : '' }}</p></div><div class="text-left sm:text-right"><p class="text-lg font-extrabold">Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>@if($payment->change_due > 0)<p class="text-xs text-ink/65">Kembalian Rp {{ number_format($payment->change_due, 0, ',', '.') }}</p>@endif</div></div>
                @empty
                    <p class="p-4 text-sm text-ink/65">Belum ada uang muka atau pembayaran.</p>
                @endforelse
            </div>
        </section>
    </div>

    <aside class="space-y-4">
        <section class="brutal-card overflow-hidden"><header class="border-b-2 border-ink bg-success px-4 py-3"><h2 class="text-lg font-extrabold">Ringkasan biaya</h2></header><div class="space-y-3 p-4">
            <div class="flex justify-between gap-3 text-sm"><span>Estimasi awal</span><strong>Rp {{ number_format($service->estimated_cost, 0, ',', '.') }}</strong></div>
            <div class="flex justify-between gap-3 text-sm"><span>Uang muka</span><strong>Rp {{ number_format($service->deposit_amount, 0, ',', '.') }}</strong></div>
            <div class="flex justify-between gap-3 text-sm"><span>Total pembayaran tercatat</span><strong>Rp {{ number_format($service->paid_total, 0, ',', '.') }}</strong></div>
            <div class="border-t-2 border-ink pt-3"><div class="flex justify-between gap-3"><span class="font-bold">Total biaya</span><strong class="text-lg">Rp {{ number_format($finalTotal, 0, ',', '.') }}</strong></div><div class="mt-2 flex justify-between gap-3 rounded-button border-2 border-ink bg-primary p-3"><span class="font-extrabold">Sisa tagihan</span><strong class="text-lg">Rp {{ number_format($balanceDue, 0, ',', '.') }}</strong></div></div>
        </div></section>

        @if($canWork)
            <section class="brutal-card overflow-hidden"><header class="border-b-2 border-ink bg-cream px-4 py-3"><h2 class="font-extrabold">Perbarui estimasi</h2></header><form method="POST" action="{{ route('services.estimate.update', $service) }}" class="space-y-3 p-4">@csrf @method('PATCH')
                <x-field label="Estimasi biaya terbaru (Rp)" name="estimated_cost" required><input id="estimated_cost" name="estimated_cost" type="number" min="0" max="999999999999" step="1" value="{{ old('estimated_cost', $service->estimated_cost) }}" class="brutal-input" required></x-field>
                <x-field label="Catatan diagnosa (opsional)" name="note"><textarea id="estimate_note" name="note" rows="2" maxlength="1000" class="brutal-input">{{ old('note') }}</textarea></x-field>
                <x-button variant="neutral" type="submit" class="w-full"><x-icon name="check" class="h-5 w-5"/>Simpan estimasi</x-button>
            </form></section>
        @endif

        @if(!empty($nextStatuses))
            <section class="brutal-card overflow-hidden"><header class="border-b-2 border-ink bg-info px-4 py-3"><h2 class="font-extrabold">Perbarui status</h2></header><form method="POST" action="{{ route('services.status.update', $service) }}" class="space-y-3 p-4">@csrf @method('PATCH')
                <x-field label="Status berikutnya" name="status" required><select id="status" name="status" class="brutal-input" required><option value="">Pilih status...</option>@foreach($nextStatuses as $status)<option value="{{ $status }}">{{ $status }}</option>@endforeach</select></x-field>
                <x-field label="Catatan status (opsional)" name="note"><textarea id="status_note" name="note" rows="2" maxlength="1000" class="brutal-input" placeholder="Contoh: hasil diagnosa telah dikonfirmasi">{{ old('note') }}</textarea></x-field>
                @if(in_array('Diambil', $nextStatuses, true) && $balanceDue > 0)<p class="rounded-button border-2 border-ink bg-warning/50 p-3 text-xs font-semibold">Perangkat hanya dapat ditandai Diambil setelah sisa tagihan lunas.</p>@endif
                <x-button variant="info" type="submit" class="w-full"><x-icon name="check" class="h-5 w-5"/>Simpan status</x-button>
            </form></section>
        @endif

        @if($canPay)
            <section class="brutal-card overflow-hidden"><header class="border-b-2 border-ink bg-warning px-4 py-3"><h2 class="font-extrabold">Catat pembayaran / pelunasan</h2></header><form method="POST" action="{{ route('services.payment.store', $service) }}" class="space-y-3 p-4">@csrf
                <x-field label="Jumlah dibayarkan (Rp)" name="amount" required hint="Maksimal sisa tagihan Rp {{ number_format($balanceDue, 0, ',', '.') }}."><input id="amount" name="amount" type="number" min="1" max="{{ $balanceDue }}" step="1" value="{{ old('amount', $balanceDue) }}" class="brutal-input" required></x-field>
                <x-field label="Metode pembayaran" name="payment_method" required><select id="payment_method" name="payment_method" class="brutal-input" required><option value="tunai" @selected(old('payment_method', 'tunai') === 'tunai')>Tunai</option><option value="transfer" @selected(old('payment_method') === 'transfer')>Transfer</option><option value="qris" @selected(old('payment_method') === 'qris')>QRIS (manual)</option></select></x-field>
                <x-field label="Uang tunai diterima (Rp)" name="amount_received" hint="Boleh melebihi pembayaran untuk mencatat kembalian."><input id="amount_received" name="amount_received" type="number" min="0" max="999999999999" step="1" value="{{ old('amount_received', $balanceDue) }}" class="brutal-input"></x-field>
                <x-field label="Catatan (opsional)" name="note"><input id="payment_note" name="note" value="{{ old('note') }}" maxlength="500" class="brutal-input" placeholder="Contoh: pelunasan saat perangkat diambil"></x-field>
                <x-button variant="warning" type="submit" class="w-full"><x-icon name="check" class="h-5 w-5"/>Simpan pembayaran</x-button>
            </form></section>
        @endif

        <section class="brutal-card overflow-hidden"><header class="border-b-2 border-ink bg-cream px-4 py-3"><h2 class="font-extrabold">Riwayat status</h2></header><ol class="space-y-0 p-4">
            @forelse($service->statusLogs as $log)
                <li class="relative border-l-2 border-ink/20 pb-4 pl-4 last:border-l-0 last:pb-0"><span class="absolute -left-[7px] top-1 h-3 w-3 rounded-full border-2 border-ink bg-primary"></span><p class="font-extrabold">{{ $log->status }}</p><p class="text-xs text-ink/65">{{ $log->created_at->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }} · {{ $log->user?->name ?? 'Akun dihapus' }}</p>@if($log->note)<p class="mt-1 whitespace-pre-line text-sm">{{ $log->note }}</p>@endif</li>
            @empty
                <li class="text-sm text-ink/65">Belum ada riwayat status.</li>
            @endforelse
        </ol></section>

        @if(auth()->user()->isAdmin() && ! $isTerminal)
            <section class="rounded-card border-2 border-ink bg-danger/20 p-4"><h2 class="font-extrabold">Batalkan service</h2><p class="mt-1 text-xs">Pembatalan tidak menghapus histori pembayaran atau memproses pengembalian uang otomatis.</p><form method="POST" action="{{ route('services.cancel', $service) }}" class="mt-3 space-y-3" onsubmit="return window.confirm('Yakin ingin membatalkan service ini?')">@csrf
                <x-field label="Alasan pembatalan" name="reason" required><textarea id="cancel_reason" name="reason" rows="2" minlength="3" maxlength="1000" class="brutal-input" required>{{ old('reason') }}</textarea></x-field>
                <x-button variant="danger" type="submit" class="w-full"><x-icon name="close" class="h-5 w-5"/>Batalkan service</x-button>
            </form></section>
        @endif
        <p class="px-1 text-xs text-ink/60">Diterima oleh {{ $service->receivedBy?->name ?? 'akun yang dihapus' }}.</p>
    </aside>
</div>
@endsection
