<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataRaporSiswa;
use App\Models\IdentitasSekolah;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IdentitasSekolahController extends Controller
{
    public function edit(): View
    {
        return view('admin.identitas-sekolah.edit', [
            'school' => IdentitasSekolah::query()->first(),
            'students' => Siswa::query()->orderBy('nama')->get(['id', 'nama', 'kelas_id']),
            'schoolYears' => TahunAjaran::query()->where('semester', 'Genap')->orderByDesc('id')->get(),
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

        return back()->with('success', 'Identitas sekolah berhasil disimpan.');
    }

    public function decidePromotion(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tahun_ajaran_id' => ['required', 'integer', 'exists:tahun_ajaran,id'],
            'siswa_id' => ['required', 'integer', 'exists:siswa,id'],
            'keputusan_kenaikan' => ['required', 'in:naik,tidak_naik'],
        ]);
        $year = TahunAjaran::query()->findOrFail($data['tahun_ajaran_id']);
        $siswa = Siswa::query()->findOrFail($data['siswa_id']);
        abort_unless($year->semester === 'Genap', 422);
        abort_unless($year->pengampu()->where('kelas_id', $siswa->kelas_id)->exists(), 404);

        DataRaporSiswa::query()->updateOrCreate(
            ['siswa_id' => $siswa->id, 'tahun_ajaran_id' => $year->id],
            ['keputusan_kenaikan' => $data['keputusan_kenaikan'], 'keputusan_oleh' => $request->user()->id],
        );

        return back()->with('success', 'Keputusan kenaikan kelas berhasil dicatat oleh admin.');
    }
}
