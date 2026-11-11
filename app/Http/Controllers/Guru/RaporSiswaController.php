<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveDataRaporSiswaRequest;
use App\Models\DataRaporSiswa;
use App\Models\EkstrakurikulerSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RaporSiswaController extends Controller
{
    public function edit(Request $request, Siswa $siswa): View
    {
        $guru = $request->user()->guru()->firstOrFail();
        abort_unless($guru->kelasWali()->whereKey($siswa->kelas_id)->exists(), 403);
        $years = TahunAjaran::query()->whereHas('pengampu', fn ($query) => $query->where('kelas_id', $siswa->kelas_id))->orderByDesc('id')->get();
        $year = $years->firstWhere('id', $request->integer('tahun_ajaran_id')) ?? $years->firstWhere('aktif', true) ?? $years->first();

        abort_if($year === null, 404);

        return view('guru.wali.rapor.data', [
            'student' => $siswa,
            'years' => $years,
            'year' => $year,
            'record' => DataRaporSiswa::query()->where('siswa_id', $siswa->id)->where('tahun_ajaran_id', $year->id)->first(),
            'activities' => EkstrakurikulerSiswa::query()->where('siswa_id', $siswa->id)->where('tahun_ajaran_id', $year->id)->get(),
        ]);
    }

    public function update(SaveDataRaporSiswaRequest $request, Siswa $siswa): RedirectResponse
    {
        $data = $request->validated();
        $year = TahunAjaran::query()->findOrFail($data['tahun_ajaran_id']);
        abort_unless($year->pengampu()->where('kelas_id', $siswa->kelas_id)->exists(), 404);
        $hasExtracurricularInput = array_key_exists('ekstrakurikuler', $data);
        $activities = collect($data['ekstrakurikuler'] ?? [])
            ->map(fn (array $activity): array => [
                'nama_kegiatan' => trim((string) ($activity['nama_kegiatan'] ?? '')),
                'keterangan' => filled($activity['keterangan'] ?? null) ? trim($activity['keterangan']) : null,
            ])
            ->filter(fn (array $activity): bool => $activity['nama_kegiatan'] !== '')
            ->values();

        DB::transaction(function () use ($data, $siswa, $year, $hasExtracurricularInput, $activities): void {
            DataRaporSiswa::query()->updateOrCreate(
                ['siswa_id' => $siswa->id, 'tahun_ajaran_id' => $year->id],
                collect($data)->only(['sakit', 'izin', 'tanpa_keterangan', 'catatan_wali_kelas'])->all(),
            );

            if (! $hasExtracurricularInput) {
                return;
            }

            EkstrakurikulerSiswa::query()->where('siswa_id', $siswa->id)->where('tahun_ajaran_id', $year->id)->delete();

            foreach ($activities as $activity) {
                EkstrakurikulerSiswa::query()->create([
                    'siswa_id' => $siswa->id,
                    'tahun_ajaran_id' => $year->id,
                    ...$activity,
                ]);
            }
        });

        return redirect()->route('guru.wali.rapor.data.edit', ['siswa' => $siswa, 'tahun_ajaran_id' => $year->id])->with('success', 'Data rapor berhasil disimpan.');
    }
}
