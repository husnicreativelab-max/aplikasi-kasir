<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicServiceStatusController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/cek-service', [PublicServiceStatusController::class, 'index'])->name('public.services.index');
Route::post('/cek-service', [PublicServiceStatusController::class, 'check'])->middleware('throttle:10,1')->name('public.services.check');

Route::middleware('guest')->group(function (): void {
    Route::get('/masuk', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/masuk', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware(['auth', 'force.password.change'])->group(function (): void {
    Route::post('/keluar', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/ganti-kata-sandi', [PasswordController::class, 'edit'])->name('password.change.edit');
    Route::put('/ganti-kata-sandi', [PasswordController::class, 'update'])->name('password.change.update');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/kasir', [PosController::class, 'index'])->name('pos.index');
    Route::get('/kasir/cari', [PosController::class, 'search'])->name('pos.search');
    Route::get('/transaksi/riwayat', [SaleController::class, 'index'])->name('sales.index');
    Route::post('/transaksi', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/transaksi/{sale}/struk', [SaleController::class, 'receipt'])->name('sales.receipt');
    Route::post('/transaksi/{sale}/batalkan', [SaleController::class, 'cancel'])->middleware('role:admin')->name('sales.cancel');

    Route::get('/produk', [ProductController::class, 'index'])->name('products.index');
    Route::middleware('role:admin')->group(function (): void {
        Route::get('/produk/buat-kode', [ProductController::class, 'generateCode'])->name('products.generate-code');
        Route::get('/produk/cetak-label', [ProductController::class, 'labels'])->name('products.labels');
        Route::get('/produk/tambah', [ProductController::class, 'create'])->name('products.create');
        Route::post('/produk', [ProductController::class, 'store'])->name('products.store');
        Route::get('/produk/{product}/ubah', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/produk/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/produk/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::get('/kategori', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/kategori', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/kategori/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/kategori/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/ekspor/penjualan', [ReportExportController::class, 'sales'])->name('reports.export.sales');
        Route::get('/laporan/ekspor/stok', [ReportExportController::class, 'stockMovements'])->name('reports.export.stock');
        Route::get('/laporan/ekspor/service', [ReportExportController::class, 'services'])->name('reports.export.services');
        Route::get('/pengaturan', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/pengaturan', [SettingsController::class, 'update'])->name('settings.update');

        Route::get('/pengaturan/pengguna', [UserController::class, 'index'])->name('users.index');
        Route::get('/pengaturan/pengguna/tambah', [UserController::class, 'create'])->name('users.create');
        Route::post('/pengaturan/pengguna', [UserController::class, 'store'])->name('users.store');
        Route::get('/pengaturan/pengguna/{user}/ubah', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/pengaturan/pengguna/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/pengaturan/pengguna/{user}/status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    });

    Route::middleware('role:admin')->group(function (): void {
        Route::get('/barang-masuk', [StockInController::class, 'index'])->name('goods-in.index');
        Route::post('/barang-masuk', [StockInController::class, 'store'])->name('goods-in.store');
    });

    Route::middleware('role:admin,kasir')->group(function (): void {
        Route::get('/service', [ServiceController::class, 'index'])->name('services.index');
        Route::get('/service/tambah', [ServiceController::class, 'create'])->name('services.create');
        Route::post('/service', [ServiceController::class, 'store'])->name('services.store');
        Route::get('/service/{service}', [ServiceController::class, 'show'])->name('services.show');
        Route::get('/service/{service}/nota/terima', [ServiceController::class, 'intakeReceipt'])->name('services.receipt.intake');
        Route::get('/service/{service}/nota/ambil', [ServiceController::class, 'releaseReceipt'])->name('services.receipt.release');
        Route::post('/service/{service}/sparepart', [ServiceController::class, 'addPart'])->name('services.parts.store');
        Route::post('/service/{service}/jasa', [ServiceController::class, 'addLabor'])->name('services.labor.store');
        Route::patch('/service/{service}/status', [ServiceController::class, 'updateStatus'])->name('services.status.update');
        Route::patch('/service/{service}/estimasi', [ServiceController::class, 'estimate'])->name('services.estimate.update');
        Route::post('/service/{service}/pembayaran', [ServiceController::class, 'payment'])->name('services.payment.store');
        Route::post('/service/{service}/batalkan', [ServiceController::class, 'cancel'])->middleware('role:admin')->name('services.cancel');
    });
});
