@extends('layouts.app')
@section('title', 'Kasir')
@section('content')
<div x-data="posApp({ searchUrl: '{{ route('pos.search', [], false) }}', allowNegativeStock: @js($allowNegativeStock) })" x-init="init()">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div><p class="text-sm font-bold text-ink/60">Transaksi baru</p><h1 class="text-3xl font-extrabold">Kasir</h1></div>
        <span class="rounded-pill border-2 border-ink bg-white px-3 py-1.5 text-xs font-bold"><span class="inline-block h-2.5 w-2.5 rounded-full border border-ink bg-success"></span> Scanner siap · ketik barcode lalu Enter</span>
    </div>

    <div class="grid items-start gap-5 xl:grid-cols-[minmax(300px,0.85fr)_minmax(0,1.4fr)]">
        <section class="space-y-4" aria-label="Cari produk">
            <div class="rounded-card border-[3px] border-ink bg-primary p-4 shadow-brutal">
                <label for="barcode-input" class="mb-2 flex items-center gap-2 text-base font-extrabold"><span class="grid h-9 w-9 place-items-center rounded-button border-2 border-ink bg-white"><x-icon name="barcode" class="h-5 w-5"/></span>Kode / Barcode</label>
                <div class="relative">
                    <input id="barcode-input" x-ref="barcodeInput" x-model="barcode" @keydown.enter.prevent="scanBarcode()" type="text" autocomplete="off" autocapitalize="off" spellcheck="false" autofocus class="min-h-14 w-full rounded-button border-[3px] border-ink bg-white py-3 pl-4 pr-12 text-lg font-bold tracking-wide placeholder:text-ink/50 focus:outline-none focus:ring-2 focus:ring-white" placeholder="Scan barcode, lalu tekan Enter" aria-describedby="scan-hint">
                    <x-icon name="barcode" class="pointer-events-none absolute right-4 top-1/2 h-6 w-6 -translate-y-1/2"/>
                </div>
                <p id="scan-hint" class="mt-2 text-xs font-semibold">Scanner fisik (mode keyboard/HID) akan mengisi kolom ini.</p>
                <div x-show="scanMessage" x-cloak x-transition class="mt-3 flex items-start gap-2 rounded-button border-2 border-ink p-3 text-sm font-bold" :class="{ 'bg-success': scanState === 'success', 'bg-danger': scanState === 'danger', 'bg-warning': scanState === 'warning', 'bg-white': scanState === 'info' }" role="status" aria-live="polite">
                    <x-icon x-show="scanState === 'success'" name="check" class="mt-0.5 h-5 w-5"/>
                    <x-icon x-show="scanState === 'danger'" name="alert" class="mt-0.5 h-5 w-5"/>
                    <span x-text="scanMessage"></span>
                </div>
            </div>

            <div class="brutal-card p-4">
                <label for="product-search" class="brutal-label">Cari produk berdasarkan nama</label>
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-ink/60"/>
                    <input id="product-search" x-model="searchQuery" @input.debounce.250ms="searchProducts()" class="brutal-input pl-10" type="search" placeholder="Contoh: mouse, SSD..." autocomplete="off">
                </div>
                <div x-show="searchResults.length || searchQuery.length >= 2" x-cloak class="mt-3 max-h-80 space-y-2 overflow-y-auto" aria-label="Hasil pencarian">
                    <template x-for="product in searchResults" :key="product.id">
                        <button type="button" @click="addProduct(product)" class="flex min-h-16 w-full items-center justify-between gap-3 rounded-button border-2 border-ink bg-white p-3 text-left shadow-brutal-sm transition hover:-translate-y-0.5 hover:shadow-brutal">
                            <span class="min-w-0"><span class="block truncate font-extrabold" x-text="product.name"></span><span class="block truncate text-xs text-ink/60" x-text="product.barcode || 'Tanpa barcode'"></span></span>
                            <span class="shrink-0 text-right"><span class="block font-extrabold" x-text="formatRupiah(product.price)"></span><span class="block text-xs" x-text="product.type === 'jasa' ? 'Jasa' : `Stok ${formatQuantity(product.stock)} ${product.unit}`"></span></span>
                        </button>
                    </template>
                    <p x-show="searchLoading" class="rounded-button border-2 border-dashed border-ink/40 p-3 text-center text-sm font-semibold">Mencari produk...</p>
                    <p x-show="!searchLoading && searchQuery.length >= 2 && searchResults.length === 0" class="rounded-button border-2 border-dashed border-ink/40 p-3 text-center text-sm font-semibold">Produk tidak ditemukan.</p>
                </div>
            </div>
        </section>

        <section class="brutal-card overflow-hidden" aria-label="Keranjang belanja">
            <div class="flex items-center justify-between gap-3 border-b-2 border-ink bg-cream px-4 py-3">
                <div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-button border-2 border-ink bg-info"><x-icon name="receipt" class="h-5 w-5"/></span><div><h2 class="text-lg font-extrabold">Keranjang</h2><p class="text-xs font-semibold text-ink/60"><span x-text="cart.length"></span> jenis produk</p></div></div>
                <button x-show="cart.length" type="button" @click="cart = []; showScanMessage('Keranjang dikosongkan.', 'info'); focusBarcode()" class="inline-flex min-h-10 items-center gap-1 rounded-button border-2 border-ink bg-white px-3 text-sm font-bold"><x-icon name="trash" class="h-4 w-4"/>Kosongkan</button>
            </div>
            <div class="p-3 sm:p-4">
                <template x-if="cart.length === 0">
                    <div class="rounded-button border-2 border-dashed border-ink/40 bg-cream px-4 py-12 text-center">
                        <span class="mx-auto grid h-14 w-14 place-items-center rounded-button border-2 border-ink bg-white"><x-icon name="barcode" class="h-7 w-7"/></span>
                        <p class="mt-3 font-extrabold">Keranjang masih kosong</p><p class="text-sm text-ink/70">Scan barcode atau cari nama produk untuk mulai.</p>
                    </div>
                </template>
                <div class="space-y-2">
                    <template x-for="item in cart" :key="item.id">
                        <article class="rounded-button border-2 border-ink bg-white p-3 shadow-brutal-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1"><h3 class="truncate font-extrabold" x-text="item.name"></h3><p class="mt-0.5 text-sm font-semibold text-ink/65"><span x-text="formatRupiah(item.price)"></span> / <span x-text="item.unit"></span></p></div>
                                <button type="button" @click="removeProduct(item.id)" class="grid h-10 w-10 shrink-0 place-items-center rounded-button border-2 border-ink bg-danger" :aria-label="`Hapus ${item.name}`"><x-icon name="trash" class="h-5 w-5"/></button>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="changeQuantity(item, -1)" class="grid h-11 w-11 place-items-center rounded-button border-2 border-ink bg-cream" :aria-label="`Kurangi ${item.name}`"><x-icon name="minus" class="h-5 w-5"/></button>
                                    <input type="number" min="0.001" step="0.001" :value="item.quantity" @change="setQuantity(item, $event.target.value)" class="h-11 w-20 rounded-button border-2 border-ink bg-white text-center font-extrabold focus:outline-none focus:ring-2 focus:ring-primary" :aria-label="`Jumlah ${item.name}`">
                                    <button type="button" @click="changeQuantity(item, 1)" class="grid h-11 w-11 place-items-center rounded-button border-2 border-ink bg-success" :aria-label="`Tambah ${item.name}`"><x-icon name="plus" class="h-5 w-5"/></button>
                                </div>
                                <p class="text-right text-base font-extrabold" x-text="formatRupiah(Math.round(item.price * item.quantity))"></p>
                            </div>
                            <p x-show="item.type === 'barang' && !allowNegativeStock" class="mt-2 text-xs font-semibold text-ink/60">Stok tersedia: <span x-text="formatQuantity(item.stock)"></span> <span x-text="item.unit"></span></p>
                        </article>
                    </template>
                </div>
            </div>
        </section>
    </div>

    <section class="sticky bottom-[78px] z-20 mt-5 rounded-card border-[3px] border-ink bg-white p-4 shadow-brutal-lg sm:p-5 lg:bottom-4" aria-label="Ringkasan pembayaran">
        <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_minmax(240px,0.65fr)] md:items-end">
            <div class="grid grid-cols-2 gap-3 sm:max-w-xl">
                <div><label for="discount-type" class="brutal-label">Diskon</label><select id="discount-type" x-model="discountType" class="brutal-input min-h-11 py-2 text-sm"><option value="nominal">Nominal (Rp)</option><option value="persen">Persen (%)</option><option value="none">Tanpa diskon</option></select></div>
                <div><label for="discount-value" class="brutal-label">Nilai diskon</label><input id="discount-value" x-model.number="discountValue" :disabled="discountType === 'none'" class="brutal-input min-h-11 py-2 text-sm disabled:bg-gray-100" type="number" min="0" :max="discountType === 'persen' ? 100 : subtotal" step="0.01" inputmode="decimal"></div>
            </div>
            <div class="flex items-end justify-between gap-4 md:justify-end">
                <div class="min-w-0 text-right"><p class="text-xs font-bold text-ink/60">TOTAL BAYAR</p><p class="truncate text-3xl font-extrabold sm:text-4xl" x-text="formatRupiah(total)">Rp 0</p><p x-show="discountAmount > 0" class="text-xs font-semibold">Diskon: <span x-text="formatRupiah(discountAmount)"></span></p></div>
                <x-button variant="primary" size="lg" class="min-w-[150px] flex-1 md:flex-none" @click="openPayment()"><x-icon name="wallet" class="h-5 w-5"/>BAYAR</x-button>
            </div>
        </div>
    </section>

    <div x-show="paymentOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[70] flex items-end justify-center bg-ink/60 p-0 sm:items-center sm:p-4" @click.self="closePayment()" @keydown.escape.window="closePayment()">
        <section x-show="paymentOpen" x-transition class="max-h-[95vh] w-full overflow-y-auto rounded-t-card border-[3px] border-ink bg-cream p-4 shadow-brutal-lg sm:max-w-xl sm:rounded-card sm:p-6" role="dialog" aria-modal="true" aria-labelledby="payment-title">
            <form x-ref="paymentForm" method="POST" action="{{ route('sales.store', [], false) }}" @submit.prevent="submitSale()">
                @csrf
                <div class="mb-5 flex items-start justify-between gap-3">
                    <div><p class="text-sm font-bold text-ink/60">Pastikan total dan pembayaran benar</p><h2 id="payment-title" class="text-2xl font-extrabold">Pembayaran</h2></div>
                    <button type="button" @click="closePayment()" class="grid h-11 w-11 place-items-center rounded-button border-2 border-ink bg-white" aria-label="Tutup pembayaran"><x-icon name="close" class="h-5 w-5"/></button>
                </div>
                <div class="rounded-button border-2 border-ink bg-primary p-4 text-center shadow-brutal-sm"><p class="text-sm font-bold">Total yang harus dibayar</p><p class="mt-1 text-4xl font-extrabold" x-text="formatRupiah(total)"></p></div>
                <div class="mt-5">
                    <p class="brutal-label">Pilih metode pembayaran</p>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" @click="choosePayment('tunai')" :class="paymentMethod === 'tunai' ? 'bg-primary' : 'bg-white'" class="min-h-[58px] rounded-button border-2 border-ink px-2 text-sm font-extrabold shadow-brutal-sm"><x-icon name="wallet" class="mx-auto mb-1 h-5 w-5"/>Tunai</button>
                        <button type="button" @click="choosePayment('transfer')" :class="paymentMethod === 'transfer' ? 'bg-primary' : 'bg-white'" class="min-h-[58px] rounded-button border-2 border-ink px-2 text-sm font-extrabold shadow-brutal-sm"><x-icon name="receipt" class="mx-auto mb-1 h-5 w-5"/>Transfer</button>
                        <button type="button" @click="choosePayment('qris')" :class="paymentMethod === 'qris' ? 'bg-primary' : 'bg-white'" class="min-h-[58px] rounded-button border-2 border-ink px-2 text-sm font-extrabold shadow-brutal-sm"><span class="mx-auto mb-1 grid h-5 w-5 place-items-center rounded border-2 border-ink text-[9px]">QR</span>QRIS</button>
                    </div>
                    <p x-show="paymentMethod !== 'tunai'" class="mt-2 rounded-button border-2 border-ink bg-white p-2 text-xs font-semibold">{{-- Pencatatan manual, tidak ada pemrosesan pembayaran otomatis. --}}Transfer/QRIS dicatat manual. Pastikan pembayaran sudah diterima.</p>
                </div>
                <div class="mt-4">
                    <label for="amount-paid" class="brutal-label">Uang diterima <span x-show="paymentMethod !== 'tunai'" class="font-normal">(otomatis sesuai total)</span></label>
                    <input id="amount-paid" x-ref="amountInput" x-model.number="amountPaid" :disabled="paymentMethod !== 'tunai'" class="brutal-input text-xl font-extrabold disabled:bg-gray-100" type="number" min="0" step="1000" inputmode="numeric" autocomplete="off">
                    <div x-show="paymentMethod === 'tunai'" class="mt-3 flex flex-wrap gap-2">
                        <button type="button" @click="amountPaid = total" class="min-h-10 rounded-pill border-2 border-ink bg-white px-3 text-sm font-bold shadow-brutal-sm">Uang pas</button>
                        <template x-for="amount in quickAmounts" :key="amount"><button type="button" @click="amountPaid = amount" class="min-h-10 rounded-pill border-2 border-ink bg-warning px-3 text-sm font-bold shadow-brutal-sm" x-text="formatRupiah(amount)"></button></template>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-button border-2 border-ink bg-white p-3"><p class="text-xs font-bold text-ink/60">TOTAL</p><p class="mt-1 font-extrabold" x-text="formatRupiah(total)"></p></div>
                    <div class="rounded-button border-2 border-ink bg-success p-3"><p class="text-xs font-bold">KEMBALIAN</p><p class="mt-1 text-lg font-extrabold" x-text="formatRupiah(paymentMethod === 'tunai' ? change : 0)"></p></div>
                </div>
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <x-button variant="neutral" type="button" @click="closePayment()">Kembali</x-button>
                    <x-button variant="primary" type="submit" size="lg" ::disabled="busy"><span x-show="!busy">Simpan &amp; cetak struk</span><span x-show="busy">Menyimpan...</span></x-button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
