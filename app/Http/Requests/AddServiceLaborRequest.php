<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddServiceLaborRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'kasir'], true);
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:180'],
            'unit' => ['required', 'string', 'max:30'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'unit_price' => ['required', 'integer', 'min:0', 'max:999999999999'],
        ];
    }
}
