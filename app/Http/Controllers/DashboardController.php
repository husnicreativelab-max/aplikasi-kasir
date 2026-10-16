<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = request()->user();
        $salesToday = Sale::query()->completed()->whereDate('sold_at', today(config('app.timezone')));

        if (! $user->isAdmin()) {
            $salesToday->where('user_id', $user->id);
        }

        $salesTotal = (clone $salesToday)->sum('total');
        $transactionCount = (clone $salesToday)->count();
        $lowStock = Product::query()
            ->available()
            ->goods()
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->with('category:id,name')
            ->orderBy('stock')
            ->limit(6)
            ->get();
        $lowStockCount = Product::query()
            ->available()
            ->goods()
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->count();
        $activeServices = Schema::hasTable('services')
            ? (int) DB::table('services')->whereNotIn('status', ['Diambil', 'Batal'])->count()
            : 0;

        return view('dashboard', compact(
            'salesTotal', 'transactionCount', 'lowStock', 'lowStockCount', 'activeServices'
        ));
    }
}
