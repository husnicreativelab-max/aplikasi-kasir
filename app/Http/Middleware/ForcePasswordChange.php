<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Akun ini sudah dinonaktifkan. Hubungi Admin toko.');
        }

        if ($user?->must_change_password
            && ! $request->routeIs('password.change.*')
            && ! $request->routeIs('logout')) {
            return redirect()->route('password.change.edit')
                ->with('warning', 'Demi keamanan, ubah kata sandi sementara sebelum menggunakan aplikasi.');
        }

        return $next($request);
    }
}
