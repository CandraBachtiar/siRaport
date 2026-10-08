<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Support\Collection;

class GuruDashboardService
{
    /** @return array{canAccessMapel: bool, canAccessWaliKelas: bool} */
    public function capabilities(Guru $guru): array
    {
        return [
            'canAccessMapel' => $guru->pengampu()->exists(),
            'canAccessWaliKelas' => $guru->kelasWali()->exists(),
        ];
    }

    /** @return array<string, mixed> */
    public function mapelDashboard(Guru $guru): array
    {
        $activeSchoolYear = $this->activeSchoolYear();
        $assignments = $guru->pengampu()
            ->with([
                'kelas:id,nama,tingkat',
                'mataPelajaran:id,kode,nama,kkm',
                'tahunAjaran:id,tahun,semester,aktif',
            ])
            ->withCount('penilaian')
            ->when(
                $activeSchoolYear !== null,
                fn ($query) => $query->where('tahun_ajaran_id', $activeSchoolYear->id),
            )
            ->orderBy('kelas_id')
            ->orderBy('mata_pelajaran_id')
            ->get();

        $assignmentIds = $assignments->pluck('id');
        $classIds = $assignments->pluck('kelas_id')->filter()->unique()->values();
        $studentCountsByClass = $this->studentCountsByClass($classIds);
        $assignments->each(function (Pengampu $assignment) use ($studentCountsByClass): void {
            $assignment->setAttribute('student_count', (int) $studentCountsByClass->get($assignment->kelas_id, 0));
        });
        $filledScoreCounts = Nilai::query()
            ->join('siswa', 'siswa.id', '=', 'nilai.siswa_id')
            ->join('penilaian', 'penilaian.id', '=', 'nilai.penilaian_id')
            ->join('pengampu', 'pengampu.id', '=', 'penilaian.pengampu_id')
            ->whereIn('penilaian.pengampu_id', $assignmentIds)
            ->whereColumn('siswa.kelas_id', 'pengampu.kelas_id')
            ->selectRaw('nilai.penilaian_id, COUNT(*) as aggregate')
            ->groupBy('nilai.penilaian_id')
            ->pluck('aggregate', 'nilai.penilaian_id');
        $assessments = Penilaian::query()
            ->whereIn('pengampu_id', $assignmentIds)
            ->orderBy('tanggal')
            ->orderBy('urutan')
            ->get();
        $assignmentsById = $assignments->keyBy('id');
        $assessmentProgress = $assessments->map(function (Penilaian $assessment) use ($assignmentsById, $filledScoreCounts, $studentCountsByClass): array {
            $assignment = $assignmentsById->get($assessment->pengampu_id);
            $studentCount = (int) $studentCountsByClass->get($assignment?->kelas_id, 0);
            $filledCount = min((int) $filledScoreCounts->get($assessment->id, 0), $studentCount);

            return [
                'id' => $assessment->id,
                'nama' => $assessment->nama,
                'jenis' => str($assessment->jenis)->replace('_', ' ')->title()->toString(),
                'tanggal' => $assessment->tanggal,
                'mataPelajaran' => $assignment?->mataPelajaran?->nama ?? 'Mata pelajaran tidak tersedia',
                'kelas' => $assignment?->kelas === null
                    ? 'Kelas tidak tersedia'
                    : $assignment->kelas->tingkat.' '.$assignment->kelas->nama,
                'filledCount' => $filledCount,
                'studentCount' => $studentCount,
                'missingCount' => max($studentCount - $filledCount, 0),
            ];
        });
        $incompleteAssessments = $assessmentProgress
            ->where('missingCount', '>', 0)
            ->sortByDesc('missingCount')
            ->values();

        $belowKkmStudentCount = Nilai::query()
            ->join('siswa', 'siswa.id', '=', 'nilai.siswa_id')
            ->join('penilaian', 'penilaian.id', '=', 'nilai.penilaian_id')
            ->join('pengampu', 'pengampu.id', '=', 'penilaian.pengampu_id')
            ->join('mata_pelajaran', 'mata_pelajaran.id', '=', 'pengampu.mata_pelajaran_id')
            ->whereIn('penilaian.pengampu_id', $assignmentIds)
            ->whereColumn('siswa.kelas_id', 'pengampu.kelas_id')
            ->whereColumn('nilai.nilai', '<', 'mata_pelajaran.kkm')
            ->distinct()
            ->count('nilai.siswa_id');

        return [
            ...$this->capabilities($guru),
            'activeSchoolYear' => $activeSchoolYear,
            'assignments' => $assignments,
            'incompleteAssessments' => $incompleteAssessments,
            'metrics' => [
                'assignmentCount' => $assignments->count(),
                'classCount' => $classIds->count(),
                'subjectCount' => $assignments->pluck('mata_pelajaran_id')->filter()->unique()->count(),
                'studentCount' => $studentCountsByClass->sum(),
                'assessmentCount' => $assessments->count(),
                'missingScoreCount' => $incompleteAssessments->sum('missingCount'),
                'incompleteAssessmentCount' => $incompleteAssessments->count(),
                'belowKkmStudentCount' => $belowKkmStudentCount,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function waliKelasDashboard(Guru $guru): array
    {
        $activeSchoolYear = $this->activeSchoolYear();
        $classes = $guru->kelasWali()
            ->withCount('siswa')
            ->orderBy('tingkat')
            ->orderBy('nama')
            ->get();
        $classIds = $classes->pluck('id');
        $assignments = Pengampu::query()
            ->whereIn('kelas_id', $classIds)
            ->when(
                $activeSchoolYear !== null,
                fn ($query) => $query->where('tahun_ajaran_id', $activeSchoolYear->id),
            );

        return [
            ...$this->capabilities($guru),
            'activeSchoolYear' => $activeSchoolYear,
            'classes' => $classes,
            'metrics' => [
                'classCount' => $classes->count(),
                'studentCount' => $classes->sum('siswa_count'),
                'teacherAssignmentCount' => (clone $assignments)->count(),
                'subjectCount' => (clone $assignments)->whereNotNull('mata_pelajaran_id')->distinct()->count('mata_pelajaran_id'),
            ],
        ];
    }

    private function activeSchoolYear(): ?TahunAjaran
    {
        return TahunAjaran::query()
            ->where('aktif', true)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @param  Collection<int, int>  $classIds
     * @return Collection<int, int>
     */
    private function studentCountsByClass(Collection $classIds): Collection
    {
        if ($classIds->isEmpty()) {
            return collect();
        }

        return Siswa::query()
            ->whereIn('kelas_id', $classIds)
            ->selectRaw('kelas_id, COUNT(*) as aggregate')
            ->groupBy('kelas_id')
            ->pluck('aggregate', 'kelas_id')
            ->map(fn ($count): int => (int) $count);
    }
}
