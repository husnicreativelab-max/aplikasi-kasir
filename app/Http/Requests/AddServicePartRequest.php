<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddServicePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'kasir'], true);
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
