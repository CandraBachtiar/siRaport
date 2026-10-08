<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Pengampu;
use App\Services\GuruAnalysisService;
use App\Services\GuruDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SiswaPerluPerhatianController extends Controller
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
            ->with(['kelas:id,nama,tingkat', 'mataPelajaran:id,kode,nama', 'tahunAjaran:id,tahun,semester,aktif'])
            ->orderByDesc('tahun_ajaran_id')
            ->orderBy('kelas_id')
            ->orderBy('mata_pelajaran_id')
            ->get();
        $assignmentId = $request->integer('pengampu_id');

        if ($assignmentId > 0 && $assignments->doesntContain('id', $assignmentId)) {
            abort(404);
        }

        $selectedAssignment = $assignments->firstWhere('id', $assignmentId);
        $schoolYearId = $selectedAssignment?->tahun_ajaran_id ?: $request->integer('tahun_ajaran_id');

        if ($schoolYearId === 0) {
            $schoolYearId = (int) ($assignments
                ->first(fn (Pengampu $assignment): bool => (bool) $assignment->tahunAjaran?->aktif)
                ?->tahun_ajaran_id ?? 0);
        }

        $visibleAssignments = $assignments
            ->when($schoolYearId > 0, fn ($items) => $items->where('tahun_ajaran_id', $schoolYearId))
            ->values();

        $search = $request->string('search')->trim()->toString();
        $attention = $this->analysisService->attention(
            $guru,
            $assignmentId > 0 ? $assignmentId : null,
            $schoolYearId > 0 ? $schoolYearId : null,
        );
        $rows = $attention['rows']->when($search !== '', function ($rows) use ($search) {
            $needle = Str::lower($search);

            return $rows->filter(fn (array $row): bool => Str::contains(
                Str::lower($row['student']->nama.' '.$row['student']->nis.' '.$row['student']->nisn),
                $needle,
            ));
        })->values();

        return view('guru.siswa-perlu-perhatian.index', [
            'user' => $user,
            'guru' => $guru,
            'workspace' => 'mapel',
            'assignments' => $visibleAssignments,
            'assignmentId' => $assignmentId,
            'schoolYearId' => $schoolYearId,
            'search' => $search,
            'rows' => $rows,
            'summary' => $attention['summary'],
            ...$this->dashboardService->capabilities($guru),
        ]);
    }
}
