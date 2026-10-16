<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        ActivityLogger::log('auth.login', null, null, 'Pengguna berhasil masuk.');

        return redirect()->intended(
            $request->user()->must_change_password
                ? route('password.change.edit')
                : route('dashboard')
        )->with('success', 'Selamat datang, '.$request->user()->name.'.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user()) {
            ActivityLogger::log('auth.logout', null, null, 'Pengguna keluar dari aplikasi.');
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda sudah keluar.');
    }
}
