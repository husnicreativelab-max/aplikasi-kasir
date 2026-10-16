<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceEstimateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'kasir'], true);
    }

    public function rules(): array
    {
        return [
            'estimated_cost' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
