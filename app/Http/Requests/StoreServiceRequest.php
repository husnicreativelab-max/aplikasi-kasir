<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'kasir'], true);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name' => ['required', 'string', 'max:160'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'device_type' => ['required', 'string', 'max:80'],
            'brand_model' => ['required', 'string', 'max:160'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'complaint' => ['required', 'string', 'max:5000'],
            'accessories' => ['nullable', 'string', 'max:2000'],
            'device_password' => ['nullable', 'string', 'max:255'],
            'estimated_cost' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'deposit_amount' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'deposit_method' => ['required', Rule::in(['tunai', 'transfer', 'qris'])],
            'deposit_received' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Nama pelanggan wajib diisi.',
            'customer_phone.required' => 'Nomor telepon pelanggan wajib diisi.',
            'device_type.required' => 'Jenis perangkat wajib dipilih.',
            'brand_model.required' => 'Merek/tipe perangkat wajib diisi.',
            'complaint.required' => 'Keluhan pelanggan wajib diisi.',
        ];
    }
}
