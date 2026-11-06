<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('account.settings', [
            'user' => $user,
            'guru' => $user->guru,
            'admin' => $user,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ], [
            'current_password' => 'password saat ini',
            'password' => 'password baru',
        ]);

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        return to_route('akun.edit')->with('success', 'Password berhasil diperbarui.');
    }
}
