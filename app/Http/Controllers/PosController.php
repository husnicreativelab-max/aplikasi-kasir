<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(): View
    {
        return view('pos.index', [
            'allowNegativeStock' => filter_var(Setting::valueOf('allow_negative_stock', '0'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'barcode' => ['nullable', 'string', 'max:80'],
        ]);

        $query = Product::query()->available()->select([
            'id', 'barcode', 'name', 'type', 'selling_price', 'stock', 'unit',
        ]);

        if (! empty($validated['barcode'])) {
            $product = (clone $query)->where('barcode', trim($validated['barcode']))->first();

            return response()->json([
                'found' => (bool) $product,
                'product' => $product ? $this->publicProduct($product) : null,
            ]);
        }

        $term = trim((string) ($validated['q'] ?? ''));
        if ($term === '') {
            return response()->json(['products' => []]);
        }

        $products = $query->where(function ($builder) use ($term): void {
            $builder->where('name', 'like', '%'.$term.'%')
                ->orWhere('barcode', 'like', '%'.$term.'%');
        })->orderBy('name')->limit(20)->get();

        return response()->json([
            'products' => $products->map(fn (Product $product) => $this->publicProduct($product))->values(),
        ]);
    }

    private function publicProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'type' => $product->type,
            'price' => (int) $product->selling_price,
            'stock' => (float) $product->stock,
            'unit' => $product->unit,
        ];
    }
}
