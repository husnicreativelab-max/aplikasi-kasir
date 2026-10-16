<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockInRequest;
use App\Models\Product;
use App\Models\StockIn;
use App\Services\StockInService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockInController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);
        if (! empty($validated['from']) && ! empty($validated['to']) && $validated['from'] > $validated['to']) {
            throw ValidationException::withMessages(['from' => 'Tanggal awal tidak boleh melewati tanggal akhir.']);
        }
        $from = ! empty($validated['from'])
            ? Carbon::parse($validated['from'], config('app.timezone'))->startOfDay()
            : null;
        $to = ! empty($validated['to'])
            ? Carbon::parse($validated['to'], config('app.timezone'))->endOfDay()
            : null;

        $stockIns = StockIn::query()
            ->with(['product:id,name,barcode,unit', 'user:id,name'])
            ->when($request->filled('q'), function ($query) use ($validated): void {
                $term = trim($validated['q']);
                $query->whereHas('product', fn ($productQuery) => $productQuery
                    ->where('name', 'like', '%'.$term.'%')
                    ->orWhere('barcode', 'like', '%'.$term.'%'));
            })
            ->when($from, fn ($query) => $query->where('received_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('received_at', '<=', $to))
            ->orderByDesc('received_at')
            ->paginate(20)
            ->withQueryString();

        $products = Product::query()
            ->available()
            ->goods()
            ->orderBy('name')
            ->get(['id', 'name', 'barcode', 'stock', 'unit', 'purchase_price']);

        return view('goods-in.index', compact('stockIns', 'products'));
    }

    public function store(StoreStockInRequest $request, StockInService $stockInService): RedirectResponse
    {
        $stockIn = $stockInService->receive($request->validated(), $request->user());

        return redirect()->route('goods-in.index')->with(
            'success',
            'Barang masuk '.$stockIn->product->name.' berhasil dicatat dan stok diperbarui.'
        );
    }
}
