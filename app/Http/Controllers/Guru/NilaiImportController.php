<?php

namespace App\Http\Controllers\Guru;

use App\Exports\NilaiTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\PreviewNilaiImportRequest;
use App\Models\Nilai;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Services\NilaiImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NilaiImportController extends Controller
{
    public function __construct(private NilaiImportService $importService) {}

    public function template(Penilaian $penilaian): BinaryFileResponse
    {
        Gate::authorize('update', $penilaian);
        $penilaian->loadMissing([
            'pengampu:id,kelas_id,mata_pelajaran_id,tahun_ajaran_id',
            'pengampu.kelas:id,nama,tingkat',
            'pengampu.mataPelajaran:id,nama',
            'tujuanPembelajaran:id,tujuan',
        ]);
        $students = Siswa::query()
            ->where('kelas_id', $penilaian->pengampu->kelas_id)
            ->orderBy('nama')
            ->orderBy('id')
            ->get(['id', 'nis', 'nama']);
        $scores = Nilai::query()
            ->where('penilaian_id', $penilaian->id)
            ->whereIn('siswa_id', $students->pluck('id'))
            ->get(['siswa_id', 'nilai'])
            ->keyBy('siswa_id');
        $rows = $students->map(fn (Siswa $student): array => [
            $penilaian->id,
            $student->id,
            (string) $student->nis,
            $student->nama,
            $scores->get($student->id)?->nilai === null ? null : (float) $scores->get($student->id)->nilai,
        ])->all();

        return Excel::download(new NilaiTemplateExport($rows, [
            'assessment_name' => $penilaian->nama,
            'assessment_id' => $penilaian->id,
            'subject' => $penilaian->pengampu->mataPelajaran->nama,
            'class' => $penilaian->pengampu->kelas->tingkat.' '.$penilaian->pengampu->kelas->nama,
            'goal' => $penilaian->tujuanPembelajaran?->tujuan ?? 'Belum dipilih',
        ]), 'template-nilai-'.$penilaian->id.'.xlsx');
    }

    public function preview(PreviewNilaiImportRequest $request, Penilaian $penilaian): RedirectResponse
    {
        $result = $this->importService->preview($request->file('file'), $penilaian);
        $sessionKey = $this->previewSessionKey($penilaian);

        if ($result['headerError'] !== null) {
            $request->session()->forget($sessionKey);

            return $this->inputRedirect($penilaian)->with('nilai_import_header_error', $result['headerError']);
        }

        $rows = $result['rows'];
        $request->session()->put($sessionKey, [
            'user_id' => $request->user()->id,
            'rows' => $rows,
            'can_import' => $rows !== []
                && collect($rows)->every(fn (array $row): bool => $row['valid'])
                && collect($rows)->contains(fn (array $row): bool => $row['will_import']),
        ]);

        return $this->inputRedirect($penilaian)->with('success', 'Pratinjau impor nilai berhasil dibuat. Periksa semua baris sebelum menyimpan.');
    }

    public function import(Request $request, Penilaian $penilaian): RedirectResponse
    {
        Gate::authorize('update', $penilaian);
        $sessionKey = $this->previewSessionKey($penilaian);
        $preview = $request->session()->get($sessionKey);

        if (! is_array($preview) || ($preview['user_id'] ?? null) !== $request->user()->id) {
            return $this->inputRedirect($penilaian)->with('error', 'Pratinjau tidak ditemukan. Unggah ulang file untuk membuat pratinjau.');
        }

        if (! ($preview['can_import'] ?? false)) {
            return $this->inputRedirect($penilaian)->with('error', 'Perbaiki semua baris pada pratinjau dan unggah ulang sebelum menyimpan.');
        }

        try {
            $savedCount = $this->importService->import($penilaian, $preview['rows']);
        } catch (ValidationException $exception) {
            return $this->inputRedirect($penilaian)->withErrors($exception->errors());
        }

        $request->session()->forget($sessionKey);

        return $this->inputRedirect($penilaian)->with('success', $savedCount.' nilai berhasil diimpor.');
    }

    private function previewSessionKey(Penilaian $penilaian): string
    {
        return 'guru.nilai_import_preview.'.$penilaian->id;
    }

    private function inputRedirect(Penilaian $penilaian): RedirectResponse
    {
        $penilaian->load('pengampu:id,kelas_id,mata_pelajaran_id,tahun_ajaran_id');

        return redirect()->route('guru.nilai.index', [
            'tahun_ajaran_id' => $penilaian->pengampu->tahun_ajaran_id,
            'mata_pelajaran_id' => $penilaian->pengampu->mata_pelajaran_id,
            'kelas_id' => $penilaian->pengampu->kelas_id,
            'penilaian_id' => $penilaian->id,
        ]);
    }
}
