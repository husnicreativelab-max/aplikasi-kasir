<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:160'],
            'short_name' => ['required', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:180'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'theme_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'receipt_footer' => ['nullable', 'string', 'max:500'],
            'service_terms' => ['nullable', 'string', 'max:5000'],
            'receipt_width_mm' => ['required', Rule::in(['58', '80'])],
            'invoice_prefix' => ['required', 'alpha_num', 'min:2', 'max:8'],
            'allow_negative_stock' => ['nullable', 'boolean'],
            'show_receipt_logo' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $hex = $this->input('theme_color');
            if (! is_string($hex) || ! preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
                return;
            }

            $channels = array_map(static fn (string $value): float => hexdec($value) / 255, str_split(substr($hex, 1), 2));
            $linear = array_map(static fn (float $channel): float => $channel <= 0.04045
                ? $channel / 12.92
                : (($channel + 0.055) / 1.055) ** 2.4, $channels);
            $luminance = 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
            $contrastWithBlack = ($luminance + 0.05) / 0.05;

            if ($contrastWithBlack < 4.5) {
                $validator->errors()->add('theme_color', 'Pilih warna yang cukup terang agar teks hitam tetap mudah dibaca (kontras WCAG AA).');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'theme_color.regex' => 'Warna tema harus berupa kode HEX, misalnya #FFD93D.',
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
            'logo.mimes' => 'Logo harus berupa file JPG, PNG, atau WEBP.',
        ];
    }
}
