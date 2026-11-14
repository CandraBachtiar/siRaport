<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\TahunAjaran;

class GuruDashboardService
{
    public function __construct(private GuruAnalysisService $analysisService) {}

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
        $attention = $this->analysisService->attention($guru, null, $activeSchoolYear?->id);
        $assignments = $attention['assignments'];
        $classIds = $assignments->pluck('kelas_id')->filter()->unique()->values();
        $studentCountsByClass = $assignments
            ->keyBy('kelas_id')
            ->map(fn (Pengampu $assignment): int => $assignment->kelas->siswa->count());
        $assignments->each(function (Pengampu $assignment) use ($studentCountsByClass): void {
            $assignment->setAttribute('student_count', (int) $studentCountsByClass->get($assignment->kelas_id, 0));
            $assignment->setAttribute('penilaian_count', $assignment->penilaian->count());
        });
        $assessmentProgress = $assignments->flatMap(function (Pengampu $assignment): array {
            $studentIds = $assignment->kelas->siswa->pluck('id');
            $studentCount = $assignment->kelas->siswa->count();

            return $assignment->penilaian->map(function (Penilaian $assessment) use ($assignment, $studentIds, $studentCount): array {
                $filledCount = min($assessment->nilai->whereIn('siswa_id', $studentIds)->count(), $studentCount);

                return [
                    'id' => $assessment->id,
                    'nama' => $assessment->nama,
                    'jenis' => str($assessment->jenis)->replace('_', ' ')->title()->toString(),
                    'tanggal' => $assessment->tanggal,
                    'urutan' => $assessment->urutan,
                    'mataPelajaran' => $assignment->mataPelajaran?->nama ?? 'Mata pelajaran tidak tersedia',
                    'kelas' => $assignment->kelas->tingkat.' '.$assignment->kelas->nama,
                    'filledCount' => $filledCount,
                    'studentCount' => $studentCount,
                    'missingCount' => max($studentCount - $filledCount, 0),
                ];
            })->all();
        });
        $assessmentProgress = $assessmentProgress
            ->sortBy(fn (array $assessment): array => [
                $assessment['tanggal']->getTimestamp(),
                $assessment['urutan'],
                $assessment['id'],
            ])
            ->values();
        $incompleteAssessments = $assessmentProgress
            ->where('missingCount', '>', 0)
            ->sortByDesc('missingCount')
            ->values();

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
                'assessmentCount' => $assignments->sum('penilaian_count'),
                'missingScoreCount' => $incompleteAssessments->sum('missingCount'),
                'incompleteAssessmentCount' => $incompleteAssessments->count(),
                'attentionStudentCount' => $attention['summary']['studentCount'],
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
}
