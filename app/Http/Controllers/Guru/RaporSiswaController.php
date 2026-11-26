<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveDataRaporSiswaRequest;
use App\Models\DataRaporSiswa;
use App\Models\EkstrakurikulerSiswa;
use App\Models\RiwayatPenempatanSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RaporSiswaController extends Controller
{
    public function edit(Request $request, Siswa $siswa): View
    {
        $guru = $request->user()->guru()->firstOrFail();

        abort_unless(
            $guru->kelasWali()->whereKey($siswa->kelas_id)->exists(),
            403
        );

        $years = TahunAjaran::query()
            ->whereHas(
                'pengampu',
                fn ($query) => $query->where('kelas_id', $siswa->kelas_id)
            )
            ->orderByDesc('id')
            ->get();

        $year = $years->firstWhere('id', $request->integer('tahun_ajaran_id'))
            ?? $years->firstWhere('aktif', true)
            ?? $years->first();

        abort_if($year === null, 404);

        $record = DataRaporSiswa::query()
            ->where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $year->id)
            ->first();

        return view('guru.wali.rapor.data', [
            'student' => $siswa,
            'years' => $years,
            'year' => $year,
            'record' => $record,

            'activities' => EkstrakurikulerSiswa::query()
                ->where('siswa_id', $siswa->id)
                ->where('tahun_ajaran_id', $year->id)
                ->get(),

            'promotionLocked' => $this->promotionLocked($siswa, $year),
        ]);
    }

    public function update(
        SaveDataRaporSiswaRequest $request,
        Siswa $siswa
    ): RedirectResponse {
        $data = $request->validated();

        $year = TahunAjaran::query()
            ->findOrFail($data['tahun_ajaran_id']);

        $hasExtracurricularInput = array_key_exists(
            'ekstrakurikuler',
            $data
        );

        $activities = collect($data['ekstrakurikuler'] ?? [])
            ->map(fn (array $activity): array => [
                'nama_kegiatan' => trim(
                    (string) ($activity['nama_kegiatan'] ?? '')
                ),

                'keterangan' => filled($activity['keterangan'] ?? null)
                    ? trim($activity['keterangan'])
                    : null,
            ])
            ->filter(
                fn (array $activity): bool =>
                    $activity['nama_kegiatan'] !== ''
            )
            ->values();

        DB::transaction(function () use (
            $request,
            $data,
            $siswa,
            $year,
            $hasExtracurricularInput,
            $activities
        ): void {
            $student = Siswa::query()
                ->lockForUpdate()
                ->findOrFail($siswa->id);

            $guru = $request->user()->guru()->firstOrFail();

            abort_unless(
                $guru->kelasWali()
                    ->whereKey($student->kelas_id)
                    ->exists(),
                403
            );

            abort_unless(
                $year->pengampu()
                    ->where('kelas_id', $student->kelas_id)
                    ->exists(),
                404
            );

            $record = DataRaporSiswa::query()
                ->where('siswa_id', $student->id)
                ->where('tahun_ajaran_id', $year->id)
                ->lockForUpdate()
                ->first();

            $reportData = collect($data)->only([
                'sakit',
                'izin',
                'tanpa_keterangan',
                'catatan_wali_kelas',
                'kokurikuler',
            ])->all();

            if (array_key_exists('keputusan_kenaikan', $data)) {
                $decision = $data['keputusan_kenaikan'];

                if ($year->semester !== 'Genap') {
                    throw ValidationException::withMessages([
                        'keputusan_kenaikan' =>
                            'Keputusan kenaikan kelas hanya dapat dicatat pada semester genap.',
                    ]);
                }

                if ($decision !== $record?->keputusan_kenaikan) {
                    if ($this->promotionLocked($student, $year)) {
                        throw ValidationException::withMessages([
                            'keputusan_kenaikan' =>
                                'Keputusan kenaikan kelas tidak dapat diubah karena periode atau riwayat penempatan siswa tidak memenuhi ketentuan.',
                        ]);
                    }

                    $reportData['keputusan_kenaikan'] = $decision;

                    $reportData['keputusan_oleh'] = $decision !== null
                        ? $request->user()->id
                        : null;
                }
            }

            DataRaporSiswa::query()->updateOrCreate(
                [
                    'siswa_id' => $student->id,
                    'tahun_ajaran_id' => $year->id,
                ],
                $reportData
            );

            if (! $hasExtracurricularInput) {
                return;
            }

            EkstrakurikulerSiswa::query()
                ->where('siswa_id', $student->id)
                ->where('tahun_ajaran_id', $year->id)
                ->delete();

            foreach ($activities as $activity) {
                EkstrakurikulerSiswa::query()->create([
                    'siswa_id' => $student->id,
                    'tahun_ajaran_id' => $year->id,
                    ...$activity,
                ]);
            }
        });

        return redirect()->route('guru.wali.rapor.data.edit', [
            'siswa' => $siswa,
            'tahun_ajaran_id' => $year->id,
        ])->with('success', 'Data rapor berhasil disimpan.');
    }

    private function promotionLocked(
        Siswa $student,
        TahunAjaran $year
    ): bool {
        if ($year->semester !== 'Genap') {
            return true;
        }

        if (! $year->aktif) {
            return true;
        }

        if (
            ! preg_match('/^(\d{4})\/(\d{4})$/', $year->tahun, $matches)
            || (int) $matches[2] !== (int) $matches[1] + 1
        ) {
            return true;
        }

        $placements = RiwayatPenempatanSiswa::query()
            ->where('siswa_id', $student->id)
            ->where('tahun_ajaran_id', $year->id)
            ->whereDate('mulai_pada', '<=', now()->toDateString())
            ->where(function ($query): void {
                $query->whereNull('berakhir_pada')
                    ->orWhereDate('berakhir_pada', '>=', now()->toDateString());
            })
            ->get();

        if (
            $placements->count() !== 1
            || (int) $placements->first()->kelas_id !== (int) $student->kelas_id
        ) {
            return true;
        }

        $nextYearName = $matches[2]
            . '/'
            . ((int) $matches[2] + 1);

        return RiwayatPenempatanSiswa::query()
            ->where('siswa_id', $student->id)
            ->whereHas(
                'tahunAjaran',
                fn ($query) => $query->where('tahun', $nextYearName)
            )
            ->exists();
    }
}