<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IdentitasSekolah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IdentitasSekolahController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.identitas-sekolah.edit', [
            'admin' => $request->user(),
            'school' => IdentitasSekolah::query()->first(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_sekolah' => ['required', 'string', 'max:255'],
            'npsn' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:2000'],
            'nama_kepala_sekolah' => ['nullable', 'string', 'max:255'],
            'nip_kepala_sekolah' => ['nullable', 'string', 'max:50'],
        ]);

        $school = IdentitasSekolah::query()->first();

        if ($school === null) {
            IdentitasSekolah::query()->create($validated);
        } else {
            $school->update($validated);
        }

        return back()->with(
            'success',
            'Identitas sekolah berhasil disimpan.'
        );
    }
}