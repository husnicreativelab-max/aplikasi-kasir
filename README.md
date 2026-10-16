# RP COM — Aplikasi Kasir

Aplikasi POS untuk toko komputer kecil, dibuat dengan Laravel 13, Blade, Tailwind CSS 4, dan Alpine.js. Antarmuka berbahasa Indonesia, mobile-first, serta memakai gaya Neo Brutalism yang mengikuti referensi visual: latar krem, garis hitam tebal, warna flat cerah, dan bayangan keras tanpa blur.

## Fase 1 yang tersedia

- Login, logout, pembatasan role Admin/Kasir, pengelolaan akun pengguna, rate limit login, dan wajib ganti kata sandi sementara.
- Pengaturan identitas toko, warna tema, logo, format struk, footer, awalan nomor transaksi, serta opsi stok minus.
- CRUD kategori dan produk, soft delete produk, kode/barcode otomatis, cetak label Code128.
- Kasir satu layar: input barcode autofocus untuk scanner fisik HID, pencarian nama, keranjang, jumlah, diskon, pembayaran Tunai/Transfer/QRIS manual, validasi stok, dan kembalian.
- Checkout tersimpan dalam DB transaction, menghasilkan nomor `INV-YYYYMMDD-0001` (awalan bisa diubah), mengurangi stok, dan mencatat mutasi stok.
- Cetak struk thermal 58 mm/80 mm, ringkasan dashboard, dan laporan penjualan dasar.
- Data contoh kategori/produk dan pengaturan awal toko dari seeder.

Barang Masuk, service komputer, retur/pembatalan, PWA/offline, laporan laba/stok/service, dan ekspor akan dikerjakan pada fase berikutnya sesuai urutan kerja.

## Kebutuhan

- PHP **8.3+** dengan ekstensi Laravel umum (`pdo_mysql`, `pdo_sqlite` (untuk tes), `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `xml`, `ctype`, `json` disarankan).
- Composer 2, Node.js 20.19+ (disarankan Node 22) dan npm.
- MySQL 8+ atau MariaDB yang kompatibel.

Laravel 13 yang dipakai pada fase ini mensyaratkan PHP 8.3; versi ini tetap memenuhi permintaan PHP 8.2+.

## Menjalankan di lokal

1. Siapkan database MySQL kosong, misalnya `rpcom_kasir`.
2. Pasang dependensi PHP dan salin konfigurasi:

   ```bash
   composer install
   cp .env.example .env
   ```

3. Periksa `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env`. Sebelum seeding pertama, ubah `ADMIN_EMAIL` dan `ADMIN_PASSWORD` bila ingin memakai kredensial sendiri.
4. Buat application key, migrasikan database, dan masukkan data awal:

   ```bash
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   ```

5. Pasang aset frontend dan jalankan:

   ```bash
   npm install
   npm run build
   php artisan serve
   ```

   Buka `http://127.0.0.1:8000`. Untuk hot reload, jalankan `npm run dev` di terminal lain.

### Akun Admin awal

Jika tidak diubah di `.env` sebelum seeding pertama:

- Email: `admin@rpcom.local`
- Kata sandi sementara: `ChangeMe123!`

Admin **wajib mengganti kata sandi** saat pertama kali masuk. Seeder tidak mengatur ulang kata sandi bila akun tersebut sudah ada.

> Jangan gunakan kata sandi sementara ini pada server produksi. Pastikan kredensial Admin dan `APP_DEBUG=false` sudah diatur sebelum deploy.

## Penggunaan scanner

Hubungkan barcode scanner fisik dalam mode keyboard/HID. Kolom **Kode / Barcode** pada halaman Kasir langsung fokus; scan kode lalu tekan Enter (kebanyakan scanner mengirim Enter otomatis). Aplikasi tidak menggunakan kamera atau library scan kamera. Jika barcode tidak ditemukan, muncul pesan merah dan bunyi peringatan.

## Pengujian

Konfigurasi PHPUnit menggunakan SQLite in-memory, sehingga pengujian tidak mengubah database MySQL lokal:

```bash
php artisan test
```

Tes fase ini mencakup checkout dan pengurangan stok, penolakan stok tidak cukup tanpa transaksi parsial, pencatatan stok awal, akses berbasis role, serta kewajiban ganti kata sandi.

## Catatan pengaturan toko

Nama toko, nama pendek, logo, warna tema, alamat, telepon/WhatsApp, footer struk, ukuran kertas, dan aturan stok disimpan di tabel `settings` dan dapat diubah dari menu **Pengaturan**. Logo tersimpan di disk `public`; jalankan `php artisan storage:link` agar dapat ditampilkan.
