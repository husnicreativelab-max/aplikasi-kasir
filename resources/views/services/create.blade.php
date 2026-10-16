@extends('layouts.app')
@section('title', 'Terima perangkat')
@section('content')
<div class="mb-5"><a href="{{ route('services.index') }}" class="inline-flex min-h-10 items-center gap-2 text-sm font-bold underline decoration-2 underline-offset-4">← Kembali ke daftar service</a><p class="mt-2 text-sm font-bold text-ink/60">Penerimaan pelanggan dan perangkat</p><h1 class="text-3xl font-extrabold">Terima perangkat</h1><p class="mt-1 text-sm text-ink/70">Simpan keluhan dan kondisi awal. Nomor service akan dibuat otomatis.</p></div>
<div class="max-w-5xl">
    <form method="POST" action="{{ route('services.store') }}" class="space-y-5" x-data="{ depositMethod: '{{ old('deposit_method', 'tunai') }}' }">
        @csrf
        <section class="brutal-card overflow-hidden"><header class="border-b-2 border-ink bg-info px-4 py-3"><h2 class="text-lg font-extrabold">Data pelanggan</h2><p class="text-sm">Data pelanggan dengan nomor telepon yang sama akan diperbarui dan digunakan kembali.</p></header><div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5">
            <x-field label="Nama pelanggan" name="customer_name" required><input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" maxlength="160" class="brutal-input" autocomplete="name" required autofocus></x-field>
            <x-field label="Nomor telepon / WhatsApp" name="customer_phone" required><input id="customer_phone" name="customer_phone" type="tel" value="{{ old('customer_phone') }}" maxlength="30" class="brutal-input" autocomplete="tel" required></x-field>
        </div></section>

        <section class="brutal-card overflow-hidden"><header class="border-b-2 border-ink bg-primary px-4 py-3"><h2 class="text-lg font-extrabold">Data perangkat dan keluhan</h2><p class="text-sm">Tuliskan aksesori yang dititipkan dan kondisi yang terlihat.</p></header><div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5">
            <x-field label="Jenis perangkat" name="device_type" required><select id="device_type" name="device_type" class="brutal-input" required><option value="">Pilih jenis perangkat...</option>@foreach(['Laptop', 'Komputer desktop', 'Printer', 'Monitor', 'Perangkat jaringan', 'Lainnya'] as $type)<option value="{{ $type }}" @selected(old('device_type') === $type)>{{ $type }}</option>@endforeach</select></x-field>
            <x-field label="Merek dan tipe / model" name="brand_model" required><input id="brand_model" name="brand_model" value="{{ old('brand_model') }}" maxlength="160" class="brutal-input" placeholder="Contoh: ASUS VivoBook 14 A1404" required></x-field>
            <x-field label="Nomor seri (opsional)" name="serial_number"><input id="serial_number" name="serial_number" value="{{ old('serial_number') }}" maxlength="120" class="brutal-input" placeholder="S/N perangkat"></x-field>
            <x-field label="Keluhan pelanggan" name="complaint" required><textarea id="complaint" name="complaint" rows="4" maxlength="5000" class="brutal-input" placeholder="Tuliskan gejala / permintaan pelanggan" required>{{ old('complaint') }}</textarea></x-field>
            <x-field label="Aksesori yang dititipkan" name="accessories" hint="Contoh: adaptor daya, tas, mouse, kabel."><textarea id="accessories" name="accessories" rows="3" maxlength="2000" class="brutal-input" placeholder="Tuliskan aksesori atau kondisi fisik yang tampak">{{ old('accessories') }}</textarea></x-field>
            <x-field label="Kata sandi / PIN perangkat (opsional)" name="device_password" hint="Disimpan terenkripsi dan hanya dapat dilihat staf yang masuk. Jangan tuliskan kata sandi akun penting."><input id="device_password" name="device_password" type="password" maxlength="255" class="brutal-input" autocomplete="off"></x-field>
        </div></section>

        <section class="brutal-card overflow-hidden"><header class="border-b-2 border-ink bg-success px-4 py-3"><h2 class="text-lg font-extrabold">Estimasi dan uang muka</h2><p class="text-sm">Estimasi dapat diperbarui setelah diagnosa. Biaya akhir mengikuti sparepart dan jasa yang dicatat.</p></header><div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5">
            <x-field label="Estimasi biaya (Rp)" name="estimated_cost" required><input id="estimated_cost" name="estimated_cost" type="number" inputmode="numeric" min="0" max="999999999999" step="1" value="{{ old('estimated_cost', 0) }}" class="brutal-input" required></x-field>
            <x-field label="Uang muka / DP (Rp)" name="deposit_amount" required><input id="deposit_amount" name="deposit_amount" type="number" inputmode="numeric" min="0" max="999999999999" step="1" value="{{ old('deposit_amount', 0) }}" class="brutal-input" required></x-field>
            <x-field label="Metode pembayaran DP" name="deposit_method" required><select id="deposit_method" name="deposit_method" x-model="depositMethod" class="brutal-input" required><option value="tunai">Tunai</option><option value="transfer">Transfer</option><option value="qris">QRIS (manual)</option></select></x-field>
            <div x-show="depositMethod === 'tunai'"><x-field label="Uang tunai diterima (Rp)" name="deposit_received" hint="Boleh sama dengan DP atau lebih untuk menghitung kembalian."><input id="deposit_received" name="deposit_received" type="number" inputmode="numeric" min="0" max="999999999999" step="1" value="{{ old('deposit_received', old('deposit_amount', 0)) }}" class="brutal-input"></x-field></div>
            <div class="sm:col-span-2"><x-field label="Catatan penerimaan internal" name="notes"><textarea id="notes" name="notes" rows="2" maxlength="1000" class="brutal-input" placeholder="Catatan singkat untuk staf (opsional)">{{ old('notes') }}</textarea></x-field></div>
        </div></section>
        <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row"><x-button as="a" href="{{ route('services.index') }}" variant="neutral">Batal</x-button><x-button variant="primary" type="submit" size="lg"><x-icon name="check" class="h-5 w-5"/>Simpan penerimaan service</x-button></div>
    </form>
</div>
@endsection
