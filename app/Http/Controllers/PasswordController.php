<?php

namespace App\Http\Controllers;

use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.password-change');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini tidak benar.',
            'password.confirmed' => 'Konfirmasi kata sandi belum sama.',
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ])->save();
        $request->session()->regenerate();

        ActivityLogger::log('auth.password_changed', $user::class, $user->id, 'Kata sandi berhasil diubah.');

        return redirect()->route('dashboard')->with('success', 'Kata sandi berhasil diubah.');
    }
}
