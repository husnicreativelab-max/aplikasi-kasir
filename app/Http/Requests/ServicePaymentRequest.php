<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServicePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'kasir'], true);
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'gt:0', 'max:999999999999'],
            'payment_method' => ['required', Rule::in(['tunai', 'transfer', 'qris'])],
            'amount_received' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
