<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class PwaController extends Controller
{
    public function manifest(): JsonResponse
    {
        $settings = Setting::allAsArray();
        $storeName = $settings['store_name'] ?? config('app.name', 'Kasir Komputer');
        $shortName = $settings['short_name'] ?? 'Kasir';
        $themeColor = $settings['theme_color'] ?? '#FFD93D';

        return response()->json([
            'id' => '/dashboard',
            'name' => $storeName.' — Kasir',
            'short_name' => mb_substr($shortName, 0, 12),
            'description' => 'Aplikasi kasir dan layanan toko.',
            'lang' => 'id-ID',
            'start_url' => '/dashboard',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#FFF9E8',
            'theme_color' => $themeColor,
            'categories' => ['business', 'productivity'],
            'icons' => [
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
        ])->header('Content-Type', 'application/manifest+json; charset=UTF-8')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}
