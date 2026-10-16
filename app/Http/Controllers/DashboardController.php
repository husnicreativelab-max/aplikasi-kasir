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
        $today = today(config('app.timezone'));
        $salesToday = Sale::query()->completed()->whereBetween('sold_at', [
            $today->copy()->startOfDay(),
            $today->copy()->endOfDay(),
        ]);

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
