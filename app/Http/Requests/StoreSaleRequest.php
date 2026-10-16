<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'discount_type' => ['required', Rule::in(['nominal', 'persen', 'none'])],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'payment_method' => ['required', Rule::in(['tunai', 'transfer', 'qris'])],
            'amount_paid' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Keranjang masih kosong.',
            'items.*.quantity.gt' => 'Jumlah produk harus lebih dari nol.',
            'payment_method.in' => 'Metode pembayaran tidak dikenal.',
        ];
    }
}
