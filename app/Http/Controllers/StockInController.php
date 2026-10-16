<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockInRequest;
use App\Models\Product;
use App\Models\StockIn;
use App\Services\StockInService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $stockIns = StockIn::query()
            ->with(['product:id,name,barcode,unit', 'user:id,name'])
            ->when($request->filled('q'), function ($query) use ($validated): void {
                $term = trim($validated['q']);
                $query->whereHas('product', fn ($productQuery) => $productQuery
                    ->where('name', 'like', '%'.$term.'%')
                    ->orWhere('barcode', 'like', '%'.$term.'%'));
            })
            ->when(! empty($validated['from']), fn ($query) => $query->whereDate('received_at', '>=', $validated['from']))
            ->when(! empty($validated['to']), fn ($query) => $query->whereDate('received_at', '<=', $validated['to']))
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
