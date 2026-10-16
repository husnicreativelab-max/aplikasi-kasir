@extends('layouts.app')
@section('title', 'Pengaturan toko')
@section('content')
<div class="mb-5"><p class="text-sm font-bold text-ink/60">Identitas, struk, dan keamanan stok</p><h1 class="text-3xl font-extrabold">Pengaturan toko</h1><p class="mt-1 text-sm text-ink/70">Nama dan identitas toko diambil dari pengaturan ini, bukan dari tampilan yang ditulis tetap.</p></div>
<div class="mb-5 grid gap-3 sm:grid-cols-3">
    <a href="{{ route('users.index') }}" class="flex min-h-16 items-center gap-3 rounded-card border-2 border-ink bg-info p-3 font-extrabold shadow-brutal-sm"><span class="grid h-10 w-10 place-items-center rounded-button border-2 border-ink bg-white"><x-icon name="user" class="h-5 w-5"/></span>Kelola pengguna</a>
    <a href="{{ route('categories.index') }}" class="flex min-h-16 items-center gap-3 rounded-card border-2 border-ink bg-success p-3 font-extrabold shadow-brutal-sm"><span class="grid h-10 w-10 place-items-center rounded-button border-2 border-ink bg-white"><x-icon name="box" class="h-5 w-5"/></span>Kategori produk</a>
    <a href="{{ route('reports.index') }}" class="flex min-h-16 items-center gap-3 rounded-card border-2 border-ink bg-primary p-3 font-extrabold shadow-brutal-sm"><span class="grid h-10 w-10 place-items-center rounded-button border-2 border-ink bg-white"><x-icon name="chart" class="h-5 w-5"/></span>Laporan penjualan</a>
</div>
<form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-5">
    @csrf @method('PUT')
    <x-card title="Identitas toko" icon="star" tone="primary">
        <div class="grid gap-4 md:grid-cols-2">
            <x-field label="Nama toko" name="store_name" required><input id="store_name" name="store_name" value="{{ old('store_name', $settings['store_name'] ?? '') }}" class="brutal-input" maxlength="160" required></x-field>
            <x-field label="Nama pendek" name="short_name" required><input id="short_name" name="short_name" value="{{ old('short_name', $settings['short_name'] ?? '') }}" class="brutal-input" maxlength="60" required></x-field>
            <x-field label="Tagline" name="tagline"><input id="tagline" name="tagline" value="{{ old('tagline', $settings['tagline'] ?? '') }}" class="brutal-input" maxlength="180"></x-field>
            <x-field label="Nomor telepon" name="phone"><input id="phone" name="phone" value="{{ old('phone', $settings['phone'] ?? '') }}" class="brutal-input" type="tel" maxlength="30" placeholder="08... "></x-field>
            <x-field label="WhatsApp" name="whatsapp"><input id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $settings['whatsapp'] ?? '') }}" class="brutal-input" type="tel" maxlength="30" placeholder="628..." hint="Gunakan kode negara, tanpa tanda +."></x-field>
            <x-field label="Alamat" name="address"><textarea id="address" name="address" rows="3" class="brutal-input min-h-24" maxlength="500">{{ old('address', $settings['address'] ?? '') }}</textarea></x-field>
        </div>
    </x-card>
    <x-card title="Logo dan warna tema" icon="star" tone="info">
        <div class="grid gap-5 md:grid-cols-[minmax(0,1fr)_220px]">
            <div class="space-y-4">
                <x-field label="Unggah logo" name="logo" hint="Format JPG, PNG, WEBP. Maksimal 2 MB."><input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" class="brutal-input h-auto min-h-12 py-2"></x-field>
                @if(!empty($settings['logo_path']))<label class="flex min-h-11 items-center gap-3 font-semibold"><input type="checkbox" name="remove_logo" value="1" class="h-5 w-5 rounded border-2 border-ink text-danger focus:ring-primary"> Hapus logo saat ini</label>@endif
                <x-field label="Warna utama aplikasi" name="theme_color" required hint="Teks tombol tetap hitam; pilih warna cukup terang agar kontras WCAG AA terjaga."><div class="flex gap-2"><input id="theme_color" name="theme_color" type="color" value="{{ old('theme_color', $settings['theme_color'] ?? '#FFD93D') }}" class="h-12 w-16 rounded-button border-2 border-ink bg-white p-1"><input type="text" value="{{ old('theme_color', $settings['theme_color'] ?? '#FFD93D') }}" class="brutal-input font-mono uppercase" maxlength="7" oninput="document.getElementById('theme_color').value = this.value"></div></x-field>
            </div>
            <div class="rounded-card border-2 border-ink bg-cream p-4 text-center"><p class="mb-2 text-sm font-extrabold">Pratinjau logo</p>@if(!empty($settings['logo_path']))<img src="{{ asset('storage/'.$settings['logo_path']) }}" alt="Logo toko saat ini" class="mx-auto h-32 w-32 rounded-card border-2 border-ink bg-white object-contain p-2">@else<span class="mx-auto grid h-32 w-32 place-items-center rounded-card border-2 border-ink bg-primary text-3xl font-extrabold shadow-brutal">{{ strtoupper(\Illuminate\Support\Str::substr($settings['short_name'] ?? 'Toko', 0, 2)) }}</span>@endif<p class="mt-2 text-xs text-ink/65">Logo tampil di header dan struk jika diaktifkan.</p></div>
        </div>
    </x-card>
    <x-card title="Pengaturan struk" icon="receipt" tone="success">
        <div class="grid gap-4 md:grid-cols-2">
            <x-field label="Ukuran kertas thermal" name="receipt_width_mm" required><select id="receipt_width_mm" name="receipt_width_mm" class="brutal-input"><option value="58" @selected(old('receipt_width_mm', $settings['receipt_width_mm'] ?? '58') === '58')>58 mm</option><option value="80" @selected(old('receipt_width_mm', $settings['receipt_width_mm'] ?? '58') === '80')>80 mm</option></select></x-field>
            <x-field label="Awalan nomor transaksi" name="invoice_prefix" required hint="Contoh: INV menghasilkan INV-YYYYMMDD-0001."><input id="invoice_prefix" name="invoice_prefix" value="{{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'INV') }}" class="brutal-input font-mono uppercase" maxlength="8" required></x-field>
            <div class="md:col-span-2"><x-field label="Teks footer struk" name="receipt_footer"><textarea id="receipt_footer" name="receipt_footer" rows="3" class="brutal-input min-h-24" maxlength="500">{{ old('receipt_footer', $settings['receipt_footer'] ?? '') }}</textarea></x-field></div>
            <label class="flex min-h-12 items-center gap-3 rounded-button border-2 border-ink bg-white px-3 font-bold"><input type="checkbox" name="show_receipt_logo" value="1" @checked(old('show_receipt_logo', $settings['show_receipt_logo'] ?? '1')) class="h-5 w-5 rounded border-2 border-ink text-success focus:ring-primary"> Tampilkan logo di struk</label>
        </div>
    </x-card>
    <x-card title="Syarat service" icon="tool" tone="info">
        <x-field label="Syarat dan garansi service" name="service_terms" hint="Teks ini dicetak pada nota penerimaan dan pengambilan service."><textarea id="service_terms" name="service_terms" rows="4" class="brutal-input min-h-28" maxlength="5000">{{ old('service_terms', $settings['service_terms'] ?? '') }}</textarea></x-field>
    </x-card>
    <x-card title="Aturan stok" icon="box" tone="warning">
        <label class="flex min-h-14 items-center gap-3 rounded-button border-2 border-ink bg-white px-3 font-bold"><input type="checkbox" name="allow_negative_stock" value="1" @checked(old('allow_negative_stock', $settings['allow_negative_stock'] ?? '0')) class="h-6 w-6 rounded border-2 border-ink text-success focus:ring-primary"> Izinkan stok minus saat transaksi <span class="ml-auto rounded-pill border-2 border-ink bg-warning px-2 py-1 text-xs">Opsional</span></label>
        <p class="mt-2 text-sm text-ink/70">Jika opsi ini mati, sistem menolak transaksi yang jumlahnya melebihi stok tersedia.</p>
    </x-card>
    <div class="sticky bottom-[78px] z-10 flex justify-end rounded-card border-2 border-ink bg-cream/95 p-3 shadow-brutal-sm backdrop-blur lg:bottom-4"><x-button variant="primary" type="submit" size="lg"><x-icon name="check" class="h-5 w-5"/>Simpan pengaturan</x-button></div>
</form>
@endsection
