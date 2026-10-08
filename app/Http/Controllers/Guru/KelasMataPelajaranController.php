<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Services\GuruDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class KelasMataPelajaranController extends Controller
{
    public function __construct(private GuruDashboardService $dashboardService) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $guru = $user->guru()->firstOrFail();
        Gate::authorize('viewAny', Pengampu::class);

        $assignments = $guru->pengampu()
            ->select(['id', 'guru_id', 'kelas_id', 'mata_pelajaran_id', 'tahun_ajaran_id'])
            ->with([
                'kelas' => fn ($query) => $query->select(['id', 'nama', 'tingkat'])->withCount('siswa'),
                'mataPelajaran:id,kode,nama,kkm',
                'tahunAjaran:id,tahun,semester,aktif',
            ])
            ->withCount('penilaian')
            ->orderByDesc('tahun_ajaran_id')
            ->orderBy('kelas_id')
            ->orderBy('mata_pelajaran_id')
            ->get();

        return view('guru.kelas-mata-pelajaran.index', [
            'user' => $user,
            'guru' => $guru,
            'workspace' => 'mapel',
            'assignments' => $assignments,
            ...$this->dashboardService->capabilities($guru),
        ]);
    }

    public function show(Request $request, Pengampu $pengampu): View
    {
        Gate::authorize('view', $pengampu);

        $user = $request->user();
        $guru = $user->guru()->firstOrFail();
        $pengampu->load([
            'kelas:id,nama,tingkat',
            'kelas.siswa' => fn ($query) => $query->select(['id', 'kelas_id', 'nis', 'nisn', 'nama', 'jenis_kelamin'])->orderBy('nama')->orderBy('id'),
            'mataPelajaran:id,kode,nama,kkm',
            'tahunAjaran:id,tahun,semester,aktif',
            'penilaian' => fn ($query) => $query
                ->withCount('nilai')
                ->withAvg('nilai', 'nilai')
                ->orderBy('urutan')
                ->orderBy('tanggal')
                ->orderBy('id'),
        ]);
        $classAverage = Nilai::query()
            ->join('penilaian', 'penilaian.id', '=', 'nilai.penilaian_id')
            ->join('siswa', 'siswa.id', '=', 'nilai.siswa_id')
            ->where('penilaian.pengampu_id', $pengampu->id)
            ->where('siswa.kelas_id', $pengampu->kelas_id)
            ->avg('nilai.nilai');

        return view('guru.kelas-mata-pelajaran.show', [
            'user' => $user,
            'guru' => $guru,
            'workspace' => 'mapel',
            'assignment' => $pengampu,
            'classAverage' => $classAverage === null ? null : round((float) $classAverage, 2),
            ...$this->dashboardService->capabilities($guru),
        ]);
    }
}
