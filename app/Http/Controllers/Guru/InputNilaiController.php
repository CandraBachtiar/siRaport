<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveNilaiRequest;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
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
    ) {}

    public function index(Request $request): View
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
            ->with(['pengampu.kelas:id,nama,tingkat', 'pengampu.mataPelajaran:id,kode,nama,kkm'])
            ->orderBy('urutan')
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();
        $selectedAssessment = null;
        $students = collect();
        $scores = collect();

        if ($filters['assessmentId'] > 0) {
            $selectedAssessment = $assessments->firstWhere('id', $filters['assessmentId']);

            if ($selectedAssessment === null) {
                abort(404);
            }

            $students = Siswa::query()
                ->where('kelas_id', $selectedAssessment->pengampu->kelas_id)
                ->orderBy('nama')
                ->orderBy('id')
                ->get();
            $scores = $selectedAssessment->nilai()
                ->whereIn('siswa_id', $students->pluck('id'))
                ->get()
                ->keyBy('siswa_id');
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
            ...$filters,
            ...$this->dashboardService->capabilities($guru),
        ]);
    }

    public function update(SaveNilaiRequest $request, Penilaian $penilaian): RedirectResponse
    {
        $savedCount = $this->scoreService->save($penilaian, $request->validated('nilai'));
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
