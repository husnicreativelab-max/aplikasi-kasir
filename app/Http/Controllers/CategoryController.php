<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveCategoryRequest;
use App\Models\Category;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', [
            'categories' => Category::query()->withCount('products')->orderBy('name')->paginate(20),
            'editing' => request()->filled('edit') ? Category::query()->findOrFail(request()->integer('edit')) : new Category(['is_active' => true]),
        ]);
    }

    public function store(SaveCategoryRequest $request): RedirectResponse
    {
        $category = Category::query()->create($request->validated() + ['is_active' => $request->boolean('is_active')]);
        ActivityLogger::log('category.created', Category::class, $category->id, "Kategori {$category->name} dibuat.");

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(SaveCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated() + ['is_active' => $request->boolean('is_active')]);
        ActivityLogger::log('category.updated', Category::class, $category->id, "Kategori {$category->name} diperbarui.");

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Kategori masih dipakai produk. Ubah kategori produk terlebih dahulu.');
        }

        $name = $category->name;
        $category->delete();
        ActivityLogger::log('category.deleted', Category::class, $category->id, "Kategori {$name} dihapus.");

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
