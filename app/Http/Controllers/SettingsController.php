<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveSettingsRequest;
use App\Models\Setting;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', ['settings' => Setting::allAsArray()]);
    }

    public function update(SaveSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $previousLogo = (string) Setting::valueOf('logo_path', '');
        $logoPath = $previousLogo;

        if ($request->boolean('remove_logo')) {
            if ($previousLogo !== '') {
                Storage::disk('public')->delete($previousLogo);
            }
            $logoPath = '';
        }

        if ($request->hasFile('logo')) {
            if ($previousLogo !== '') {
                Storage::disk('public')->delete($previousLogo);
            }
            $logoPath = $request->file('logo')->store('store', 'public') ?: '';
        }

        $values = [
            'store_name' => $data['store_name'],
            'short_name' => $data['short_name'],
            'tagline' => $data['tagline'] ?? '',
            'address' => $data['address'] ?? '',
            'phone' => $data['phone'] ?? '',
            'whatsapp' => $data['whatsapp'] ?? '',
            'theme_color' => strtoupper($data['theme_color']),
            'receipt_footer' => $data['receipt_footer'] ?? '',
            'service_terms' => $data['service_terms'] ?? '',
            'receipt_width_mm' => $data['receipt_width_mm'],
            'invoice_prefix' => strtoupper($data['invoice_prefix']),
            'allow_negative_stock' => $request->boolean('allow_negative_stock') ? '1' : '0',
            'show_receipt_logo' => $request->boolean('show_receipt_logo') ? '1' : '0',
            'logo_path' => $logoPath,
        ];

        DB::transaction(fn () => Setting::putMany($values));
        ActivityLogger::log('settings.updated', Setting::class, null, 'Pengaturan toko diperbarui.', [
            'changed_keys' => array_keys($values),
        ]);

        return redirect()->route('settings.edit')->with('success', 'Pengaturan toko berhasil disimpan.');
    }
}
