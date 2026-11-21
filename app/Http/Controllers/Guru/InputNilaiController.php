<?php

namespace App\Http\Controllers\Guru;

use App\Enums\AssessmentPlacementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveNilaiRequest;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Services\AssessmentPlacementChecker;
use App\Services\AssessmentRosterService;
use App\Services\GuruDashboardService;
use App\Services\GuruScoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class InputNilaiController extends Controller
{
    public function __construct(
        private GuruDashboardService $dashboardService,
        private GuruScoreService $scoreService,
        private AssessmentPlacementChecker $placementChecker,
        private AssessmentRosterService $rosterService,
    ) {}

    public function index(Request $request): View
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
        $filters = $this->filters($request, $assignments);
        $schoolYearAssignments = $assignments
            ->when($filters['schoolYearId'] > 0, fn (Collection $items): Collection => $items->where('tahun_ajaran_id', $filters['schoolYearId']))
            ->values();
        $subjectAssignments = $schoolYearAssignments
            ->when($filters['subjectId'] > 0, fn (Collection $items): Collection => $items->where('mata_pelajaran_id', $filters['subjectId']))
            ->values();
        $filteredAssignments = $subjectAssignments
            ->when($filters['classId'] > 0, fn (Collection $items): Collection => $items->where('kelas_id', $filters['classId']));
        $assessments = Penilaian::query()
            ->whereIn('pengampu_id', $filteredAssignments->pluck('id'))
            ->with(['pengampu.kelas:id,nama,tingkat', 'pengampu.mataPelajaran:id,kode,nama'])
            ->orderBy('urutan')
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();
        $selectedAssessment = null;
        $students = collect();
        $scores = collect();
        $importPreview = null;
        $placementStatuses = collect();
        $editableStudentIds = collect();

        if ($filters['assessmentId'] > 0) {
            $selectedAssessment = $assessments->firstWhere('id', $filters['assessmentId']);

            if ($selectedAssessment === null) {
                abort(404);
            }

            $rosterIds = $this->rosterService->studentIds($user, $selectedAssessment);
            $existingScoreIds = $selectedAssessment->nilai()->pluck('siswa_id');
            $students = Siswa::query()
                ->whereIn('id', $rosterIds->concat($existingScoreIds)->unique())
                ->orderBy('nama')
                ->orderBy('id')
                ->get();
            $scores = $selectedAssessment->nilai()
                ->whereIn('siswa_id', $students->pluck('id'))
                ->get()
                ->keyBy('siswa_id');
            $placementStatuses = $this->placementChecker->check($user, $selectedAssessment->pengampu, $selectedAssessment, $students->modelKeys());
            $currentClassStudentIds = Siswa::query()
                ->where('kelas_id', $selectedAssessment->pengampu->kelas_id)
                ->pluck('id');
            $editableStudentIds = $placementStatuses
                ->filter(fn ($status, int $studentId): bool => $status === AssessmentPlacementStatus::MatchesAssignment
                    || ($currentClassStudentIds->contains($studentId)
                        && in_array($status, [AssessmentPlacementStatus::Unknown, AssessmentPlacementStatus::DateUnavailable], true)))
                ->keys();
            $previewKey = 'guru.nilai_import_preview.'.$selectedAssessment->id;
            $storedPreview = $request->session()->get($previewKey);

            if (is_array($storedPreview) && ($storedPreview['user_id'] ?? null) === $user->id) {
                $importPreview = $storedPreview;
            } elseif ($storedPreview !== null) {
                $request->session()->forget($previewKey);
            }
        }

        return view('guru.nilai.index', [
            'user' => $user,
            'guru' => $guru,
            'workspace' => 'mapel',
            'assignments' => $assignments,
            'schoolYears' => $assignments->pluck('tahunAjaran')->filter()->unique('id')->values(),
            'subjects' => $schoolYearAssignments->pluck('mataPelajaran')->filter()->unique('id')->sortBy('nama')->values(),
            'classes' => $subjectAssignments->pluck('kelas')->filter()->unique('id')->sortBy('tingkat')->sortBy('nama')->values(),
            'filteredAssignments' => $filteredAssignments,
            'assessments' => $assessments,
            'selectedAssessment' => $selectedAssessment,
            'students' => $students,
            'scores' => $scores,
            'placementStatuses' => $placementStatuses,
            'editableStudentIds' => $editableStudentIds,
            'placementSummary' => $placementStatuses->countBy(fn ($status): string => $status->name),
            'importPreview' => $importPreview,
            'importHeaderError' => $request->session()->get('nilai_import_header_error'),
            ...$filters,
            ...$this->dashboardService->capabilities($guru),
        ]);
    }

    public function update(SaveNilaiRequest $request, Penilaian $penilaian): RedirectResponse
    {
        $savedCount = $this->scoreService->save($request->user(), $penilaian, $request->validated('nilai'));
        $penilaian->loadMissing('pengampu:id,kelas_id,mata_pelajaran_id,tahun_ajaran_id');

        return redirect()->route('guru.nilai.index', [
            'tahun_ajaran_id' => $penilaian->pengampu->tahun_ajaran_id,
            'mata_pelajaran_id' => $penilaian->pengampu->mata_pelajaran_id,
            'kelas_id' => $penilaian->pengampu->kelas_id,
            'penilaian_id' => $penilaian->id,
        ])->with('success', $savedCount > 0 ? 'Nilai berhasil disimpan.' : 'Tidak ada nilai baru untuk disimpan.');
    }

    /** @return array{schoolYearId: int, subjectId: int, classId: int, assessmentId: int} */
    private function filters(Request $request, Collection $assignments): array
    {
        $schoolYearId = $request->integer('tahun_ajaran_id');

        if ($schoolYearId === 0) {
            $schoolYearId = (int) ($assignments->first(fn (Pengampu $assignment): bool => (bool) $assignment->tahunAjaran?->aktif)?->tahun_ajaran_id ?? 0);
        }

        return [
            'schoolYearId' => $schoolYearId,
            'subjectId' => $request->integer('mata_pelajaran_id'),
            'classId' => $request->integer('kelas_id'),
            'assessmentId' => $request->integer('penilaian_id'),
        ];
    }
}
