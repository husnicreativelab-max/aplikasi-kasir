<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\StockLedger;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->when(! $request->user()->isAdmin(), fn (Builder $query) => $query->where('is_active', true))
            ->with('category:id,name')
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $term = trim((string) $request->input('q'));
                $query->where(fn (Builder $search) => $search
                    ->where('name', 'like', '%'.$term.'%')
                    ->orWhere('barcode', 'like', '%'.$term.'%'));
            })
            ->when($request->filled('category'), fn (Builder $query) => $query->where('category_id', $request->integer('category')))
            ->when($request->input('status') === 'aktif', fn (Builder $query) => $query->where('is_active', true))
            ->when($request->input('status') === 'nonaktif', fn (Builder $query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('products.form', [
            'product' => new Product(['type' => 'barang', 'unit' => 'pcs', 'stock' => 0, 'minimum_stock' => 0, 'is_active' => true]),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(SaveProductRequest $request, StockLedger $stockLedger): RedirectResponse
    {
        $data = $request->validated();
        $startingStock = $data['type'] === 'barang' ? (float) $data['stock'] : 0;
        unset($data['stock']);
        $data['is_active'] = $request->boolean('is_active');

        $product = DB::transaction(function () use ($data, $startingStock, $stockLedger): Product {
            $product = Product::query()->create($data + ['stock' => 0]);

            if ($startingStock > 0 && $product->type === 'barang') {
                $stockLedger->move(
                    $product->id, 'in', $startingStock, 'opening', Product::class, $product->id, 'Stok awal produk',
                );
            }

            ActivityLogger::log('product.created', Product::class, $product->id, "Produk {$product->name} ditambahkan.");

            return $product;
        });

        return redirect()->route('products.index')->with('success', "Produk {$product->name} berhasil ditambahkan.");
    }

    public function edit(Product $product): View
    {
        return view('products.form', [
            'product' => $product,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(SaveProductRequest $request, Product $product, StockLedger $stockLedger): RedirectResponse
    {
        $data = $request->validated();
        $desiredStock = $data['type'] === 'barang' ? (float) $data['stock'] : 0;
        unset($data['stock']);
        $data['is_active'] = $request->boolean('is_active');

        DB::transaction(function () use ($data, $desiredStock, $product, $stockLedger): void {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $oldPurchasePrice = (int) $locked->purchase_price;
            $oldSellingPrice = (int) $locked->selling_price;
            $oldStock = (float) $locked->stock;

            if ($locked->type !== $data['type'] && $oldStock > 0.0005) {
                throw ValidationException::withMessages([
                    'type' => 'Tipe barang/jasa tidak dapat diubah selama masih memiliki stok. Sesuaikan stok terlebih dahulu.',
                ]);
            }

            $locked->fill($data)->save();
            $delta = round($desiredStock - $oldStock, 3);

            if (abs($delta) > 0.0005 && $locked->type === 'barang') {
                $stockLedger->move(
                    $locked->id,
                    $delta > 0 ? 'in' : 'out',
                    abs($delta),
                    'adjustment',
                    Product::class,
                    $locked->id,
                    'Penyesuaian stok melalui edit produk',
                );
            }

            if ($oldPurchasePrice !== (int) $locked->purchase_price || $oldSellingPrice !== (int) $locked->selling_price) {
                ActivityLogger::log('product.price_changed', Product::class, $locked->id, "Harga {$locked->name} diperbarui.", [
                    'purchase_before' => $oldPurchasePrice,
                    'purchase_after' => (int) $locked->purchase_price,
                    'selling_before' => $oldSellingPrice,
                    'selling_after' => (int) $locked->selling_price,
                ]);
            }

            ActivityLogger::log('product.updated', Product::class, $locked->id, "Produk {$locked->name} diperbarui.");
        });

        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $name = $product->name;
        $product->delete();
        ActivityLogger::log('product.deleted', Product::class, $product->id, "Produk {$name} dinonaktifkan dengan soft delete.");

        return redirect()->route('products.index')->with('success', "Produk {$name} berhasil dihapus dari katalog.");
    }

    public function generateCode(): JsonResponse
    {
        do {
            $code = 'PRD-'.strtoupper(Str::random(9));
        } while (Product::withTrashed()->where('barcode', $code)->exists());

        return response()->json(['barcode' => $code]);
    }

    public function labels(Request $request): View
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'distinct', 'exists:products,id'],
        ]);

        return view('products.labels', [
            'products' => Product::query()->whereIn('id', $validated['ids'])->orderBy('name')->get(),
        ]);
    }
}
