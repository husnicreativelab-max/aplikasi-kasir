<?php

namespace App\Providers;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('id');

        View::composer(['layouts.*', 'sales.receipt', 'services.receipt', 'products.labels'], function ($view): void {
            $store = [];

            if (Schema::hasTable('settings')) {
                $store = Setting::allAsArray();
            }

            $view->with('store', array_merge([
                'store_name' => 'Nama Toko',
                'short_name' => 'Toko',
                'tagline' => 'Solusi komputer, layanan bersahabat.',
                'theme_color' => '#FFD93D',
            ], $store));
        });
    }
}
