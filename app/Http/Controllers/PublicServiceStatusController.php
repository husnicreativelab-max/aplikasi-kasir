<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PublicServiceStatusController extends Controller
{
    public function index(): View
    {
        return view('public-services.lookup');
    }

    public function check(Request $request): Response|RedirectResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'max:40'],
            'tracking_code' => ['required', 'string', 'size:24', 'regex:/^[A-Z0-9]+$/i'],
        ], [
            'number.required' => 'Nomor service wajib diisi.',
            'tracking_code.required' => 'Kode cek status wajib diisi.',
            'tracking_code.size' => 'Kode cek status tidak sesuai.',
            'tracking_code.regex' => 'Kode cek status tidak sesuai.',
        ]);

        $number = strtoupper(trim($validated['number']));
        $tokenHash = hash('sha256', strtoupper(trim($validated['tracking_code'])));
        $service = Service::query()
            ->where('number', $number)
            ->where('tracking_code_hash', $tokenHash)
            ->first();

        if (! $service) {
            return back()
                ->withErrors(['lookup' => 'Nomor service atau kode cek status tidak cocok.'])
                ->withInput(['number' => $number]);
        }

        $history = $service->publicStatusLogs()
            ->orderBy('created_at')
            ->get(['status', 'created_at']);

        return response()->view('public-services.status', compact('service', 'history'))
            ->header('Cache-Control', 'private, no-store, max-age=0');
    }
}
