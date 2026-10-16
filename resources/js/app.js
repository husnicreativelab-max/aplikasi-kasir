import Alpine from 'alpinejs';
import JsBarcode from 'jsbarcode';

window.Alpine = Alpine;

window.formatRupiah = (value) => {
    const number = Number(value || 0);
    return `Rp ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number.isFinite(number) ? number : 0)}`;
};

window.formatQuantity = (value) => new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 3,
}).format(Number(value || 0));

window.posApp = (config) => ({
    searchUrl: config.searchUrl,
    allowNegativeStock: Boolean(config.allowNegativeStock),
    barcode: '',
    searchQuery: '',
    searchResults: [],
    cart: [],
    scanMessage: '',
    scanState: 'info',
    discountType: 'nominal',
    discountValue: 0,
    paymentMethod: 'tunai',
    amountPaid: 0,
    paymentOpen: false,
    busy: false,
    requestController: null,
    searchLoading: false,
    messageTimeout: null,

    get subtotal() {
        return this.cart.reduce((sum, item) => sum + Math.round(item.price * item.quantity), 0);
    },
    get discountAmount() {
        if (this.discountType === 'none') return 0;
        const value = Math.max(0, Number(this.discountValue || 0));
        return this.discountType === 'persen'
            ? Math.min(this.subtotal, Math.round(this.subtotal * Math.min(value, 100) / 100))
            : Math.min(this.subtotal, Math.round(value));
    },
    get total() {
        return Math.max(0, this.subtotal - this.discountAmount);
    },
    get change() {
        return Math.max(0, Number(this.amountPaid || 0) - this.total);
    },
    get quickAmounts() {
        const total = this.total;
        const nextFive = Math.ceil(total / 5000) * 5000;
        const candidates = [total, nextFive, Math.ceil(total / 10000) * 10000, 50000, 100000];
        return [...new Set(candidates.filter((amount) => amount >= total && amount > 0))].slice(0, 5);
    },

    init() {
        this.$nextTick(() => this.focusBarcode());
        window.addEventListener('pageshow', () => this.focusBarcode());
    },

    focusBarcode() {
        const input = this.$refs.barcodeInput;
        if (!input || this.paymentOpen) return;
        input.focus({ preventScroll: true });
        input.select();
    },

    showScanMessage(message, state = 'success') {
        this.scanMessage = message;
        this.scanState = state;
        window.clearTimeout(this.messageTimeout);
        this.messageTimeout = window.setTimeout(() => { this.scanMessage = ''; }, 3200);
    },

    beep() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const context = new AudioContext();
            const oscillator = context.createOscillator();
            const gain = context.createGain();
            oscillator.type = 'square';
            oscillator.frequency.value = 440;
            gain.gain.value = 0.12;
            oscillator.connect(gain);
            gain.connect(context.destination);
            oscillator.start();
            oscillator.stop(context.currentTime + 0.16);
            oscillator.onended = () => context.close();
        } catch (_) {
            // Bunyi bersifat tambahan; transaksi tetap berjalan jika browser memblokir audio.
        }
    },

    async scanBarcode() {
        const code = this.barcode.trim();
        if (!code) return;
        this.barcode = '';

        try {
            const response = await fetch(`${this.searchUrl}?barcode=${encodeURIComponent(code)}`, {
                headers: { Accept: 'application/json' },
            });
            const data = await response.json();
            if (!response.ok || !data.found || !data.product) {
                this.showScanMessage(`Kode “${code}” tidak ditemukan. Periksa barcode atau cari nama produk.`, 'danger');
                this.beep();
                this.focusBarcode();
                return;
            }
            this.addProduct(data.product, true);
        } catch (_) {
            this.showScanMessage('Produk belum dapat dicari. Periksa koneksi lalu coba lagi.', 'danger');
            this.beep();
            this.focusBarcode();
        }
    },

    async searchProducts() {
        const term = this.searchQuery.trim();
        if (term.length < 2) {
            this.requestController?.abort();
            this.requestController = null;
            this.searchResults = [];
            this.searchLoading = false;
            return;
        }

        if (this.requestController) this.requestController.abort();
        this.requestController = new AbortController();
        this.searchLoading = true;

        try {
            const response = await fetch(`${this.searchUrl}?q=${encodeURIComponent(term)}`, {
                headers: { Accept: 'application/json' },
                signal: this.requestController.signal,
            });
            const data = await response.json();
            this.searchResults = response.ok ? (data.products || []) : [];
            this.searchLoading = false;
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.searchResults = [];
                this.searchLoading = false;
            }
        }
    },

    addProduct(product, fromScanner = false) {
        const existing = this.cart.find((item) => item.id === product.id);
        const nextQuantity = (existing?.quantity || 0) + 1;
        if (product.type === 'barang' && !this.allowNegativeStock && nextQuantity > Number(product.stock)) {
            this.showScanMessage(`Stok ${product.name} tidak mencukupi. Tersedia ${window.formatQuantity(product.stock)} ${product.unit}.`, 'danger');
            if (fromScanner) this.beep();
            this.focusBarcode();
            return;
        }

        if (existing) {
            existing.quantity += 1;
        } else {
            this.cart.unshift({ ...product, quantity: 1 });
        }
        this.showScanMessage(`${product.name} ditambahkan ke keranjang.`, 'success');
        this.requestController?.abort();
        this.requestController = null;
        this.searchLoading = false;
        this.searchResults = [];
        this.searchQuery = '';
        this.focusBarcode();
    },

    changeQuantity(item, difference) {
        const next = Number(item.quantity) + difference;
        if (next <= 0) {
            this.removeProduct(item.id);
            return;
        }
        if (item.type === 'barang' && !this.allowNegativeStock && next > Number(item.stock)) {
            this.showScanMessage(`Stok ${item.name} hanya ${window.formatQuantity(item.stock)} ${item.unit}.`, 'danger');
            this.focusBarcode();
            return;
        }
        item.quantity = Math.round(next * 1000) / 1000;
        this.focusBarcode();
    },

    setQuantity(item, value) {
        const quantity = Math.max(0, Number(value || 0));
        if (quantity <= 0) {
            this.removeProduct(item.id);
            return;
        }
        if (item.type === 'barang' && !this.allowNegativeStock && quantity > Number(item.stock)) {
            this.showScanMessage(`Stok ${item.name} hanya ${window.formatQuantity(item.stock)} ${item.unit}.`, 'danger');
            item.quantity = Math.max(1, Number(item.stock));
        } else {
            item.quantity = Math.round(quantity * 1000) / 1000;
        }
        this.focusBarcode();
    },

    removeProduct(id) {
        this.cart = this.cart.filter((item) => item.id !== id);
        this.showScanMessage('Produk dihapus dari keranjang.', 'info');
        this.focusBarcode();
    },

    openPayment() {
        if (this.cart.length === 0) {
            this.showScanMessage('Tambahkan produk terlebih dahulu sebelum membayar.', 'warning');
            this.focusBarcode();
            return;
        }
        this.paymentOpen = true;
        this.amountPaid = this.total;
        this.$nextTick(() => this.$refs.amountInput?.focus({ preventScroll: true }));
    },

    closePayment() {
        if (this.busy) return;
        this.paymentOpen = false;
        this.focusBarcode();
    },

    choosePayment(method) {
        this.paymentMethod = method;
        if (method !== 'tunai') this.amountPaid = this.total;
    },

    async submitSale() {
        if (this.busy || this.cart.length === 0) return;
        if (this.paymentMethod === 'tunai' && Number(this.amountPaid) < this.total) {
            this.showScanMessage('Uang yang diterima masih kurang dari total.', 'danger');
            return;
        }

        this.busy = true;
        const form = new FormData(this.$refs.paymentForm);
        this.cart.forEach((item, index) => {
            form.append(`items[${index}][product_id]`, String(item.id));
            form.append(`items[${index}][quantity]`, String(item.quantity));
        });
        form.set('discount_type', this.discountType);
        form.set('discount_value', String(this.discountValue || 0));
        form.set('payment_method', this.paymentMethod);
        form.set('amount_paid', String(this.paymentMethod === 'tunai' ? this.amountPaid : this.total));

        try {
            const response = await fetch(this.$refs.paymentForm.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: form,
            });
            const data = await response.json();
            if (!response.ok) {
                const firstError = data.errors ? Object.values(data.errors).flat()[0] : data.message;
                this.showScanMessage(firstError || 'Transaksi gagal disimpan. Periksa kembali data.', 'danger');
                this.busy = false;
                return;
            }
            window.location.assign(data.redirect);
        } catch (_) {
            this.showScanMessage('Transaksi belum tersimpan. Periksa koneksi sebelum mencoba kembali.', 'danger');
            this.busy = false;
        }
    },
});

function renderBarcodes() {
    document.querySelectorAll('[data-barcode]').forEach((element) => {
        const value = element.dataset.barcode;
        if (!value) return;
        try {
            JsBarcode(element, value, {
                format: 'CODE128',
                displayValue: true,
                font: 'Space Grotesk',
                fontSize: 11,
                height: 46,
                margin: 4,
                width: 1.5,
                background: '#ffffff',
                lineColor: '#1A1A1A',
            });
        } catch (_) {
            element.setAttribute('aria-label', `Barcode ${value} tidak dapat ditampilkan`);
        }
    });
}

document.addEventListener('DOMContentLoaded', renderBarcodes);
window.addEventListener('beforeprint', renderBarcodes);
Alpine.start();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {});
    });
}

const installBanner = document.getElementById('pwa-install-banner');
const installButton = document.getElementById('pwa-install-button');
const dismissInstallButton = document.getElementById('pwa-dismiss-button');
let pendingInstallPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    pendingInstallPrompt = event;
    let dismissed = false;
    try {
        dismissed = window.localStorage.getItem('rpcom-pwa-install-dismissed') === '1';
    } catch (_) {
        // Penyimpanan browser opsional.
    }
    if (installBanner && !dismissed) installBanner.hidden = false;
});

installButton?.addEventListener('click', async () => {
    if (!pendingInstallPrompt) return;
    pendingInstallPrompt.prompt();
    await pendingInstallPrompt.userChoice;
    pendingInstallPrompt = null;
    if (installBanner) installBanner.hidden = true;
});

dismissInstallButton?.addEventListener('click', () => {
    if (installBanner) installBanner.hidden = true;
    try {
        window.localStorage.setItem('rpcom-pwa-install-dismissed', '1');
    } catch (_) {
        // Pemberitahuan hanya disembunyikan untuk halaman saat ini.
    }
});

window.addEventListener('appinstalled', () => {
    if (installBanner) installBanner.hidden = true;
    pendingInstallPrompt = null;
});
