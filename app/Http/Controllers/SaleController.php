<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SaleController extends Controller
{
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

    public function receipt(Sale $sale): View
    {
        abort_unless(request()->user()->isAdmin() || $sale->user_id === request()->user()->id, 403);

        return view('sales.receipt', [
            'sale' => $sale->load(['items', 'user']),
        ]);
    }
}
