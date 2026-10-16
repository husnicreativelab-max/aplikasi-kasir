<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $fromDate = $validated['from'] ?? today(config('app.timezone'))->toDateString();
        $toDate = $validated['to'] ?? $fromDate;

        if ($fromDate > $toDate) {
            throw ValidationException::withMessages(['from' => 'Tanggal awal tidak boleh melewati tanggal akhir.']);
        }

        $from = Carbon::parse($fromDate, config('app.timezone'))->startOfDay();
        $to = Carbon::parse($toDate, config('app.timezone'))->endOfDay();
        $base = Sale::query()->completed()->whereBetween('sold_at', [$from, $to]);

        $totalSales = (clone $base)->sum('total');
        $transactionCount = (clone $base)->count();
        $averageSale = $transactionCount > 0 ? (int) round($totalSales / $transactionCount) : 0;

        $paymentTotals = (clone $base)
            ->select('payment_method', DB::raw('COUNT(*) as transaction_count'), DB::raw('SUM(total) as amount_total'))
            ->groupBy('payment_method')
            ->orderBy('payment_method')
            ->get();

        $cashierTotals = (clone $base)
            ->select('user_id', DB::raw('COUNT(*) as transaction_count'), DB::raw('SUM(total) as amount_total'))
            ->groupBy('user_id')
            ->with('user:id,name')
            ->orderByDesc('amount_total')
            ->get();

        $topProducts = SaleItem::query()
            ->select('product_name', DB::raw('SUM(quantity) as quantity_sold'), DB::raw('SUM(line_total) as sales_total'))
            ->whereHas('sale', fn ($query) => $query->completed()->whereBetween('sold_at', [$from, $to]))
            ->groupBy('product_name')
            ->orderByDesc('quantity_sold')
            ->limit(8)
            ->get();

        $dailyTotals = DB::table('sales')
            ->select(DB::raw('DATE(sold_at) as sale_date'), DB::raw('COUNT(*) as transaction_count'), DB::raw('SUM(total) as amount_total'))
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$from, $to])
            ->groupBy(DB::raw('DATE(sold_at)'))
            ->orderBy('sale_date')
            ->get();

        return view('reports.index', compact(
            'fromDate', 'toDate', 'totalSales', 'transactionCount', 'averageSale',
            'paymentTotals', 'cashierTotals', 'topProducts', 'dailyTotals',
        ));
    }
}
