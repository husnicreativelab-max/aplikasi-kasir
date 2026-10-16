<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'name' => ['required', 'string', 'max:160'],
            'barcode' => ['nullable', 'string', 'max:80', Rule::unique('products', 'barcode')->ignore($productId)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'type' => ['required', Rule::in(['barang', 'jasa'])],
            'purchase_price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'selling_price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'stock' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'minimum_stock' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'unit' => ['required', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'barcode.unique' => 'Kode/barcode sudah digunakan produk lain.',
            'category_id.exists' => 'Kategori yang dipilih tidak ditemukan.',
        ];
    }
}
