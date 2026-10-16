<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PlaceholderController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

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
    Route::post('/transaksi', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/transaksi/{sale}/struk', [SaleController::class, 'receipt'])->name('sales.receipt');

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
        Route::get('/pengaturan', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/pengaturan', [SettingsController::class, 'update'])->name('settings.update');

        Route::get('/pengaturan/pengguna', [UserController::class, 'index'])->name('users.index');
        Route::get('/pengaturan/pengguna/tambah', [UserController::class, 'create'])->name('users.create');
        Route::post('/pengaturan/pengguna', [UserController::class, 'store'])->name('users.store');
        Route::get('/pengaturan/pengguna/{user}/ubah', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/pengaturan/pengguna/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/pengaturan/pengguna/{user}/status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    });

    Route::get('/barang-masuk', [PlaceholderController::class, 'goodsIn'])->middleware('role:admin')->name('goods-in.index');
    Route::get('/service', [PlaceholderController::class, 'service'])->name('services.index');
});
