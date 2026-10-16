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

## Fase 2 yang tersedia

- **Barang masuk** (Admin): catat produk, jumlah, harga beli terbaru, total pembelian, petugas, waktu, dan catatan. Stok ditambah melalui ledger mutasi dalam transaksi database.
- **Service komputer**: catat pelanggan dan perangkat, nomor seri, keluhan, aksesori/kondisi awal, kata sandi/PIN perangkat opsional (disimpan terenkripsi), estimasi dan DP; gunakan status Diterima, Dicek, Menunggu Konfirmasi, Dikerjakan, Selesai, dan Diambil.
- Tambahkan sparepart (mengurangi stok dan tercatat di ledger) serta biaya jasa; catat pembayaran bertahap/pelunasan, riwayat status, nota penerimaan, dan nota pengambilan thermal hitam-putih.
- **Riwayat transaksi** untuk mencari struk; hanya Admin dapat membatalkan/meretur seluruh transaksi. Barang fisik dikembalikan ke stok, transaksi dikeluarkan dari laporan, alasan dan pelaku dicatat. Pengembalian uang tidak diproses otomatis.
- Service yang sudah diambil tidak dapat diubah; perangkat hanya bisa ditandai Diambil setelah sisa tagihan lunas.

## Fase 3 yang tersedia

- **PWA installable** dengan manifest dinamis mengikuti nama/warna toko, ikon aplikasi, prompt pemasangan, service worker, cache terbatas untuk shell/aset statis, dan halaman fallback saat offline. Halaman dinamis, transaksi, data pelanggan, dan API tidak di-cache; perubahan data tetap memerlukan koneksi.
- **Laporan Admin** dapat difilter menurut rentang tanggal dan metode pembayaran. Unduh CSV terpisah untuk penjualan/item (termasuk modal dan laba kotor sebelum diskon), mutasi ledger stok, dan ringkasan service; masing-masing punya filter relevan. Ekspor memakai UTF-8, melindungi sel dari formula CSV, dan endpoint dibatasi untuk Admin.
- **Cek status service publik** meminta nomor service serta kode acak 24 karakter pada nota. Kode disimpan dalam bentuk hash untuk pencarian dan terenkripsi untuk pencetakan ulang. Halaman publik hanya menampilkan perangkat/model umum, status, tanggal, dan riwayat status publik—bukan nama/telepon, nomor seri, keluhan, PIN, catatan internal, atau kode rahasia. Endpoint cek status dibatasi laju permintaannya.
- Kode cek status tercetak pada nota penerimaan dan pengambilan, serta tersedia pada detail service internal. **WhatsApp memakai click-to-chat** dengan nomor yang dinormalisasi ke format internasional; tidak ada pengiriman otomatis atau integrasi API.

## Fase 4 yang tersedia

- GitHub Actions menjalankan tes PHP (SQLite), smoke test migrasi/seeder MySQL 8.4, route cache, build Vite, audit npm, dan build image produksi.
- Rute beranda dapat di-cache; filter tanggal transaksi memakai rentang timestamp yang memanfaatkan indeks, dan laporan memiliki indeks gabungan untuk query historis.
- Docker Compose produksi menyediakan PHP-FPM, Nginx, Caddy TLS otomatis, serta MySQL 8.4 di jaringan internal. Database dan berkas toko memakai named volume; debug mati, cookie sesi terenkripsi/secure, dan seeder tidak berjalan otomatis.
- Panduan deploy, upgrade, backup, dan pemulihan ada di bawah. Stack Compose ini untuk satu server; bukan konfigurasi high-availability.

## Deploy produksi dengan Docker Compose

Panduan ini menyiapkan satu server Linux dengan Docker Compose v2, DNS domain publik, dan port TCP 80/443 terbuka. Caddy menerbitkan dan memperbarui sertifikat TLS otomatis; jangan pasang TLS kedua kali pada Nginx. MySQL tidak dipublikasikan ke host, dan PHP-FPM hanya dapat diakses melalui jaringan internal.

1. Pastikan record DNS `A`/`AAAA` domain toko mengarah ke server. Salin konfigurasi contoh, lalu isi **domain yang sama** pada `APP_DOMAIN` dan `APP_URL`:

   ```bash
   cp .env.docker.example .env
   chmod 600 .env
   ```

   Buat nilai rahasia berbeda untuk `APP_KEY`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`, dan `ADMIN_PASSWORD`. Contoh membuat APP_KEY Laravel dari shell:

   ```bash
   KEY="base64:$(openssl rand -base64 32 | tr -d '\n')"
   sed -i "s|^APP_KEY=.*|APP_KEY=${KEY}|" .env
   ```

   Untuk tiga password, gunakan password manager atau `openssl rand -hex 32`; jangan memakai placeholder dari file contoh. Simpan `.env` dan salinan APP_KEY di secret manager/backup terenkripsi, bukan di Git. Kehilangan APP_KEY membuat PIN perangkat serta kode pelacakan terenkripsi tidak dapat dibaca.

2. Bangun dan jalankan stack. Migrasi dijalankan otomatis saat container aplikasi mulai; seeder **tidak** dijalankan otomatis.

   ```bash
   docker compose up -d --build
   docker compose ps
   docker compose logs -f app nginx caddy db
   ```

   Tunggu hingga layanan `db`, `app`, dan `nginx` sehat. Caddy memerlukan DNS publik yang benar dan akses masuk port 80/443 agar sertifikat dapat diterbitkan.

3. Pada pemasangan database baru, jalankan seeder satu kali untuk membuat Admin serta data contoh:

   ```bash
   docker compose exec app php artisan db:seed --force
   ```

   Seeder membaca `ADMIN_EMAIL` dan `ADMIN_PASSWORD` dari `.env`; ganti kata sandi Admin segera saat login pertama. Seeder tidak mengubah kata sandi akun yang sudah ada, dan menambahkan contoh kategori/produk bila belum dibuat.

4. Buka `https://<APP_DOMAIN>`. Cek endpoint kesehatan dengan `https://<APP_DOMAIN>/up`. Untuk memeriksa worker PWA, buka aplikasi sekali saat online lalu periksa `/manifest.webmanifest`; halaman/transaksi tetap membutuhkan koneksi.

### Upgrade dan pemeliharaan

- Sebelum upgrade, ambil backup database **dan** file toko, lalu checkout tag/rilis yang akan digunakan dan jalankan `docker compose up -d --build`. Migrasi baru berjalan sebelum PHP-FPM menerima trafik. Pada satu server bisa ada jeda layanan; tinjau migration terlebih dahulu.
- `docker compose logs -f app` menampilkan log Laravel ke stderr. Status container: `docker compose ps`. Jalankan Artisan dengan `docker compose exec app php artisan <perintah>`.
- `RUN_MIGRATIONS=0` dapat diatur di `.env` jika migrasi ingin dijalankan manual sebagai langkah rilis; setelah itu jalankan `docker compose exec app php artisan migrate --force`.
- Database dan storage adalah named volume Docker. Jangan menjalankan `docker compose down -v` kecuali memang ingin menghapus database, sertifikat, dan file toko.
- Compose ini adalah fondasi satu server—belum menyediakan replikasi database, autoscaling, atau pemulihan bencana otomatis. Simpan backup di host/layanan lain yang terenkripsi dan uji pemulihannya.

### Backup dan pemulihan

Contoh backup database dan file logo/unggahan pada server:

```bash
mkdir -p backups
STAMP="$(date +%F-%H%M%S)"
docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysqldump --single-transaction --no-tablespaces -u "$MYSQL_USER" "$MYSQL_DATABASE"' > "backups/db-${STAMP}.sql"
mkdir -p "backups/storage-${STAMP}"
docker compose cp app:/var/www/html/storage/app/public "backups/storage-${STAMP}/"
```

Simpan backup `.env`/APP_KEY secara terpisah dan terenkripsi. Pemulihan contoh (gunakan nama file yang benar, hentikan trafik aplikasi, dan pastikan database tujuan memang yang akan ditimpa):

```bash
STAMP=2026-10-16-120000
docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot "$MYSQL_DATABASE"' < "backups/db-${STAMP}.sql"
docker compose cp "backups/storage-${STAMP}/public" app:/var/www/html/storage/app/
docker compose up -d
```

Direktori storage dipakai bersama oleh PHP dan Nginx. Jangan memulihkan backup ke database produksi aktif sebelum memverifikasi nama database dan waktu snapshot.

> Repositori saat ini belum menyertakan `composer.lock`. Docker akan menyelesaikan versi yang memenuhi constraint Composer saat image dibangun. Untuk rilis yang dapat direproduksi, buat dan commit `composer.lock` menggunakan PHP 8.3+ dan Composer 2 setelah tes berhasil; Dockerfile otomatis menggunakannya jika tersedia.

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

Tes mencakup checkout dan pengurangan stok, penolakan stok tidak cukup tanpa transaksi parsial, pencatatan stok awal/barang masuk, batas akses Admin, pembatalan penjualan dan pengembalian stok, penerimaan service dengan enkripsi data akses perangkat, pengurangan stok sparepart, pencatatan jasa/DP/pembayaran, alur status, kewajiban ganti kata sandi, privasi lookup service publik, akses ekspor Admin, CSV, manifest PWA, serta normalisasi tautan WhatsApp. Jalankan tes setelah migrasi di lingkungan PHP/MySQL/SQLite yang sesuai.

## Catatan pengaturan toko

Nama toko, nama pendek, logo, warna tema, alamat, telepon/WhatsApp, footer struk, ukuran kertas, dan aturan stok disimpan di tabel `settings` dan dapat diubah dari menu **Pengaturan**. Logo tersimpan di disk `public`; jalankan `php artisan storage:link` agar dapat ditampilkan.
