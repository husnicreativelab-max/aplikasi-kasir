<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelSaleRequest;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Sale;
use App\Services\SaleCancellationService;
use App\Services\SaleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
        if (! empty($validated['from']) && ! empty($validated['to']) && $validated['from'] > $validated['to']) {
            throw ValidationException::withMessages(['from' => 'Tanggal awal tidak boleh melewati tanggal akhir.']);
        }
        $from = ! empty($validated['from'])
            ? Carbon::parse($validated['from'], config('app.timezone'))->startOfDay()
            : null;
        $to = ! empty($validated['to'])
            ? Carbon::parse($validated['to'], config('app.timezone'))->endOfDay()
            : null;

        $sales = Sale::query()
            ->with('user:id,name')
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->when(! empty($validated['q']), fn ($query) => $query->where('number', 'like', '%'.trim($validated['q']).'%'))
            ->when($from, fn ($query) => $query->where('sold_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('sold_at', '<=', $to))
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
