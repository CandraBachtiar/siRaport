<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CloseRiwayatPenempatanSiswaRequest;
use App\Http\Requests\CorrectRiwayatPenempatanSiswaRequest;
use App\Http\Requests\StoreRiwayatPenempatanSiswaRequest;
use App\Models\Kelas;
use App\Models\RiwayatPenempatanSiswa;
use App\Models\RiwayatPenempatanSiswaAudit;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\PlacementHistoryManager;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class RiwayatPenempatanSiswaController extends Controller
{
    public function index(Request $request, Siswa $siswa): View
    {
        $auditReady = $this->auditReady();
        $siswa->load('kelas:id,nama,tingkat');

        $placements = $siswa->riwayatPenempatan()
            ->with([
                'kelas:id,nama,tingkat',
                'tahunAjaran:id,tahun,semester',
                ...($auditReady ? ['audit.user:id,name'] : []),
            ])
            ->orderByDesc('mulai_pada')
            ->orderByDesc('id')
            ->get();

        return view('admin.siswa.riwayat-penempatan', [
            'admin' => $request->user(),
            'siswa' => $siswa,
            'placements' => $placements,
            'classes' => Kelas::query()->orderBy('tingkat')->orderBy('nama')->orderBy('id')->get(['id', 'nama', 'tingkat']),
            'schoolYears' => TahunAjaran::query()->orderByDesc('id')->get(['id', 'tahun', 'semester']),
            'auditReady' => $auditReady,
        ]);
    }

    public function store(
        StoreRiwayatPenempatanSiswaRequest $request,
        Siswa $siswa,
        PlacementHistoryManager $manager,
    ): RedirectResponse {
        if (! $this->auditReady()) {
            return $this->migrationRequired($siswa);
        }

        $data = $request->validated();

        try {
            $manager->record(
                $request->user(),
                $siswa->id,
                $data['kelas_id'],
                $data['tahun_ajaran_id'],
                $data['mulai_pada'],
                $data['berakhir_pada'] ?? null,
                $data['alasan'],
                $data['referensi_bukti'],
            );
        } catch (QueryException $exception) {
            report($exception);

            return redirect()->route('admin.siswa.riwayat.index', $siswa)
                ->withInput()
                ->with('error', 'Riwayat tidak dapat disimpan. Pastikan migration audit tersedia, lalu coba kembali.');
        }

        return redirect()->route('admin.siswa.riwayat.index', $siswa)
            ->with('success', 'Riwayat penempatan berhasil dicatat. Kelas aktif siswa tidak berubah.');
    }

    public function close(
        CloseRiwayatPenempatanSiswaRequest $request,
        Siswa $siswa,
        RiwayatPenempatanSiswa $placement,
        PlacementHistoryManager $manager,
    ): RedirectResponse {
        $placement = $this->placementForStudent($siswa, $placement);

        if (! $this->auditReady()) {
            return $this->migrationRequired($siswa);
        }

        $data = $request->validated();

        try {
            $manager->close($request->user(), $placement->id, $data['berakhir_pada'], $data['alasan'], $data['referensi_bukti']);
        } catch (QueryException $exception) {
            report($exception);

            return redirect()->route('admin.siswa.riwayat.index', $siswa)
                ->withInput()
                ->with('error', 'Riwayat tidak dapat ditutup. Pastikan migration audit tersedia, lalu coba kembali.');
        }

        return redirect()->route('admin.siswa.riwayat.index', $siswa)
            ->with('success', 'Penempatan berhasil ditutup tanpa mengubah kelas aktif siswa.');
    }

    public function correct(
        CorrectRiwayatPenempatanSiswaRequest $request,
        Siswa $siswa,
        RiwayatPenempatanSiswa $placement,
        PlacementHistoryManager $manager,
    ): RedirectResponse {
        $placement = $this->placementForStudent($siswa, $placement);

        if (! $this->auditReady()) {
            return $this->migrationRequired($siswa);
        }

        $data = $request->validated();

        try {
            $manager->correct(
                $request->user(),
                $placement->id,
                $data['kelas_id'],
                $data['tahun_ajaran_id'],
                $data['mulai_pada'],
                $data['berakhir_pada'] ?? null,
                $data['alasan'],
                $data['referensi_bukti'],
            );
        } catch (QueryException $exception) {
            report($exception);

            return redirect()->route('admin.siswa.riwayat.index', $siswa)
                ->withInput()
                ->with('error', 'Koreksi tidak dapat disimpan. Pastikan migration audit tersedia, lalu coba kembali.');
        }

        return redirect()->route('admin.siswa.riwayat.index', $siswa)
            ->with('success', 'Koreksi riwayat berhasil dicatat dalam audit. Kelas aktif siswa tidak berubah.');
    }

    private function placementForStudent(Siswa $siswa, RiwayatPenempatanSiswa $placement): RiwayatPenempatanSiswa
    {
        return $siswa->riwayatPenempatan()->whereKey($placement->id)->firstOrFail();
    }

    private function auditReady(): bool
    {
        return Schema::hasTable((new RiwayatPenempatanSiswaAudit)->getTable());
    }

    private function migrationRequired(Siswa $siswa): RedirectResponse
    {
        return redirect()->route('admin.siswa.riwayat.index', $siswa)
            ->with('error', 'Pencatatan riwayat belum tersedia karena migration audit belum diterapkan.');
    }
}
