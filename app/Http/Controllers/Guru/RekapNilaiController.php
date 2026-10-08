<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Pengampu;
use App\Services\GuruDashboardService;
use App\Services\GuruScoreService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RekapNilaiController extends Controller
{
    public function __construct(
        private GuruDashboardService $dashboardService,
        private GuruScoreService $scoreService,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $guru = $user->guru()->firstOrFail();
        $assignments = Pengampu::query()
            ->where('guru_id', $guru->id)
            ->with(['kelas:id,nama,tingkat', 'mataPelajaran:id,kode,nama,kkm', 'tahunAjaran:id,tahun,semester,aktif'])
            ->orderByDesc('tahun_ajaran_id')
            ->orderBy('kelas_id')
            ->orderBy('mata_pelajaran_id')
            ->get();
        $assignmentId = $request->integer('pengampu_id');
        $selectedAssignment = $assignmentId > 0
            ? $assignments->firstWhere('id', $assignmentId)
            : $assignments->first(fn (Pengampu $assignment): bool => (bool) $assignment->tahunAjaran?->aktif) ?? $assignments->first();

        if ($assignmentId > 0 && $selectedAssignment === null) {
            abort(404);
        }

        $recap = $selectedAssignment === null
            ? ['students' => collect(), 'assessments' => collect(), 'rows' => collect(), 'calculationMode' => 'Rata-rata sederhana']
            : $this->scoreService->recap($selectedAssignment);

        return view('guru.rekap-nilai.index', [
            'user' => $user,
            'guru' => $guru,
            'workspace' => 'mapel',
            'assignments' => $assignments,
            'selectedAssignment' => $selectedAssignment,
            ...$recap,
            ...$this->dashboardService->capabilities($guru),
        ]);
    }
}
