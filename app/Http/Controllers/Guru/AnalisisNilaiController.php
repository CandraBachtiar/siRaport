<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Pengampu;
use App\Models\Siswa;
use App\Services\GuruAnalysisService;
use App\Services\GuruDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AnalisisNilaiController extends Controller
{
    public function __construct(
        private GuruAnalysisService $analysisService,
        private GuruDashboardService $dashboardService,
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
        $selectedAssignment = $this->selectedAssignment($request, $assignments);
        $schoolYearId = $selectedAssignment?->tahun_ajaran_id ?: $request->integer('tahun_ajaran_id');

        if ($schoolYearId === 0) {
            $schoolYearId = (int) ($assignments->first(fn (Pengampu $assignment): bool => (bool) $assignment->tahunAjaran?->aktif)?->tahun_ajaran_id ?? 0);
        }

        $subjectId = $selectedAssignment?->mata_pelajaran_id ?: $request->integer('mata_pelajaran_id');
        $classId = $selectedAssignment?->kelas_id ?: $request->integer('kelas_id');
        $schoolYearAssignments = $assignments->when(
            $schoolYearId > 0,
            fn (Collection $items): Collection => $items->where('tahun_ajaran_id', $schoolYearId),
        )->values();
        $subjectAssignments = $schoolYearAssignments->when(
            $subjectId > 0,
            fn (Collection $items): Collection => $items->where('mata_pelajaran_id', $subjectId),
        )->values();

        if ($selectedAssignment === null && $schoolYearId > 0 && $subjectId > 0 && $classId > 0) {
            $selectedAssignment = $assignments->first(fn (Pengampu $assignment): bool => $assignment->tahun_ajaran_id === $schoolYearId
                && $assignment->mata_pelajaran_id === $subjectId
                && $assignment->kelas_id === $classId);
        }

        $student = null;
        $studentId = $request->integer('siswa_id');

        if ($studentId > 0) {
            if ($selectedAssignment === null) {
                abort(404);
            }

            $student = Siswa::query()
                ->where('kelas_id', $selectedAssignment->kelas_id)
                ->findOrFail($studentId);
        }

        $analysis = $selectedAssignment === null
            ? [
                'classSeries' => collect(),
                'distribution' => collect(),
                'classStats' => [],
                'studentAnalysis' => null,
                'students' => collect(),
                'assessments' => collect(),
            ]
            : $this->analysisService->analyze($selectedAssignment, $student);

        return view('guru.analisis-nilai.index', [
            'user' => $user,
            'guru' => $guru,
            'workspace' => 'mapel',
            'assignments' => $assignments,
            'schoolYears' => $assignments->pluck('tahunAjaran')->filter()->unique('id')->values(),
            'subjects' => $schoolYearAssignments->pluck('mataPelajaran')->filter()->unique('id')->sortBy('nama')->values(),
            'classes' => $subjectAssignments->pluck('kelas')->filter()->unique('id')->sortBy('nama')->values(),
            'selectedAssignment' => $selectedAssignment,
            'schoolYearId' => $schoolYearId,
            'subjectId' => $subjectId,
            'classId' => $classId,
            'studentId' => $studentId,
            ...$analysis,
            ...$this->dashboardService->capabilities($guru),
        ]);
    }

    /** @param Collection<int, Pengampu> $assignments */
    private function selectedAssignment(Request $request, Collection $assignments): ?Pengampu
    {
        $assignmentId = $request->integer('pengampu_id');

        if ($assignmentId === 0) {
            return null;
        }

        $assignment = $assignments->firstWhere('id', $assignmentId);

        if ($assignment === null) {
            abort(404);
        }

        return $assignment;
    }
}
