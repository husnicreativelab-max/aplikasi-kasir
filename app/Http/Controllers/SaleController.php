<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelSaleRequest;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Sale;
use App\Services\SaleCancellationService;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'in:completed,cancelled'],
        ]);

        $sales = Sale::query()
            ->with('user:id,name')
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->when(! empty($validated['q']), fn ($query) => $query->where('number', 'like', '%'.trim($validated['q']).'%'))
            ->when(! empty($validated['from']), fn ($query) => $query->whereDate('sold_at', '>=', $validated['from']))
            ->when(! empty($validated['to']), fn ($query) => $query->whereDate('sold_at', '<=', $validated['to']))
            ->when(! empty($validated['status']), fn ($query) => $query->where('status', $validated['status']))
            ->orderByDesc('sold_at')
            ->paginate(20)
            ->withQueryString();

        return view('sales.index', compact('sales'));
    }

    public function store(StoreSaleRequest $request, SaleService $saleService): JsonResponse|RedirectResponse
    {
        $sale = $saleService->checkout($request->validated(), $request->user());
        $receiptUrl = route('sales.receipt', $sale);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Transaksi berhasil disimpan.',
                'redirect' => $receiptUrl,
                'number' => $sale->number,
            ], 201);
        }

        return redirect()->to($receiptUrl)->with('success', 'Transaksi berhasil disimpan.');
    }

    public function cancel(CancelSaleRequest $request, Sale $sale, SaleCancellationService $cancellation): RedirectResponse
    {
        $cancellation->cancel($sale, $request->validated('reason'), $request->user());

        return redirect()->route('sales.receipt', $sale)->with('success', 'Transaksi dibatalkan dan stok barang dikembalikan.');
    }

    public function receipt(Sale $sale): View
    {
        abort_unless(request()->user()->isAdmin() || $sale->user_id === request()->user()->id, 403);

        return view('sales.receipt', [
            'sale' => $sale->load(['items', 'user', 'cancelledBy']),
        ]);
    }
}
