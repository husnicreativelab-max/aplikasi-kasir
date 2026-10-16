<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'purchase_price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
