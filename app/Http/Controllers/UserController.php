<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveUserRequest;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', ['users' => User::query()->orderBy('name')->paginate(20)]);
    }

    public function create(): View
    {
        return view('users.form', ['user' => new User(['role' => 'kasir', 'is_active' => true])]);
    }

    public function store(SaveUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['must_change_password'] = true;
        $user = User::query()->create($data);
        ActivityLogger::log('user.created', User::class, $user->id, "Akun {$user->email} dibuat.");

        return redirect()->route('users.index')->with('success', 'Akun pengguna berhasil ditambahkan. Pengguna wajib mengganti kata sandi saat masuk pertama kali.');
    }

    public function edit(User $user): View
    {
        return view('users.form', compact('user'));
    }

    public function update(SaveUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        if ($user->is($request->user()) && ! $data['is_active']) {
            return back()->with('error', 'Akun yang sedang digunakan tidak dapat dinonaktifkan.');
        }

        if ($user->role === 'admin' && $user->is_active && (! $data['is_active'] || $data['role'] !== 'admin')) {
            $otherAdmins = User::query()->where('role', 'admin')->where('is_active', true)->where('id', '!=', $user->id)->count();
            if ($otherAdmins === 0) {
                return back()->with('error', 'Minimal harus ada satu akun Admin aktif.');
            }
        }

        $passwordChanged = filled($data['password'] ?? null);
        if (! $passwordChanged) {
            unset($data['password']);
        } else {
            $data['must_change_password'] = true;
        }

        $user->update($data);
        ActivityLogger::log($passwordChanged ? 'user.password_reset' : 'user.updated', User::class, $user->id, "Akun {$user->email} diperbarui.");

        return redirect()->route('users.index')->with('success', $passwordChanged
            ? 'Data pengguna diperbarui. Kata sandi sementara harus diganti saat pengguna masuk.'
            : 'Data pengguna berhasil diperbarui.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->is(request()->user())) {
            return back()->with('error', 'Akun yang sedang digunakan tidak dapat dinonaktifkan.');
        }

        if ($user->role === 'admin' && $user->is_active) {
            $otherAdmins = User::query()->where('role', 'admin')->where('is_active', true)->where('id', '!=', $user->id)->count();
            if ($otherAdmins === 0) {
                return back()->with('error', 'Minimal harus ada satu akun Admin aktif.');
            }
        }

        $user->update(['is_active' => ! $user->is_active]);
        ActivityLogger::log('user.status_changed', User::class, $user->id, "Status akun {$user->email} diubah menjadi ".($user->is_active ? 'aktif' : 'nonaktif').'.');

        return back()->with('success', 'Status akun pengguna berhasil diubah.');
    }
}
