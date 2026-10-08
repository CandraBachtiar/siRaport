<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class GuruAnalysisService
{
    /**
     * @return array{
     *     classSeries: Collection<int, array<string, mixed>>,
     *     distribution: Collection<int, array<string, mixed>>,
     *     classStats: array<string, int|float|null>,
     *     studentAnalysis: array<string, mixed>|null,
     *     students: EloquentCollection<int, Siswa>,
     *     assessments: EloquentCollection<int, Penilaian>
     * }
     */
    public function analyze(Pengampu $assignment, ?Siswa $student = null): array
    {
        $assignment->loadMissing([
            'kelas:id,nama,tingkat',
            'mataPelajaran:id,kode,nama,kkm',
            'tahunAjaran:id,tahun,semester,aktif',
        ]);
        $students = Siswa::query()
            ->where('kelas_id', $assignment->kelas_id)
            ->orderBy('nama')
            ->orderBy('id')
            ->get();
        $assessments = Penilaian::query()
            ->where('pengampu_id', $assignment->id)
            ->with(['nilai' => fn ($query) => $query
                ->whereIn('siswa_id', $students->pluck('id'))
                ->select(['id', 'siswa_id', 'penilaian_id', 'nilai'])])
            ->orderBy('tanggal')
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();
        $allScores = $assessments
            ->flatMap(fn (Penilaian $assessment): Collection => $assessment->nilai->pluck('nilai')->map(fn ($score): float => (float) $score));
        $studentAverages = $students
            ->mapWithKeys(function (Siswa $classStudent) use ($assessments): array {
                $scores = $this->studentScores($assessments, $classStudent->id);

                return [$classStudent->id => $scores->isEmpty() ? null : round((float) $scores->average(), 2)];
            });
        $filledStudentAverages = $studentAverages->filter(fn (?float $average): bool => $average !== null);
        $kkm = (float) $assignment->mataPelajaran->kkm;

        $classSeries = $assessments->map(function (Penilaian $assessment): array {
            $scores = $assessment->nilai->pluck('nilai')->map(fn ($score): float => (float) $score);

            return [
                'label' => $assessment->nama,
                'shortLabel' => 'P'.$assessment->urutan,
                'value' => $scores->isEmpty() ? null : round((float) $scores->average(), 2),
                'filledCount' => $scores->count(),
            ];
        });
        $distribution = collect([
            ['label' => '0–59', 'minimum' => 0, 'maximum' => 59.99],
            ['label' => '60–69', 'minimum' => 60, 'maximum' => 69.99],
            ['label' => '70–79', 'minimum' => 70, 'maximum' => 79.99],
            ['label' => '80–89', 'minimum' => 80, 'maximum' => 89.99],
            ['label' => '90–100', 'minimum' => 90, 'maximum' => 100],
        ])->map(function (array $bucket) use ($filledStudentAverages): array {
            return [
                'label' => $bucket['label'],
                'value' => $filledStudentAverages
                    ->filter(fn (float $average): bool => $average >= $bucket['minimum'] && $average <= $bucket['maximum'])
                    ->count(),
            ];
        });

        return [
            'classSeries' => $classSeries,
            'distribution' => $distribution,
            'classStats' => [
                'average' => $allScores->isEmpty() ? null : round((float) $allScores->average(), 2),
                'highest' => $allScores->isEmpty() ? null : (float) $allScores->max(),
                'lowest' => $allScores->isEmpty() ? null : (float) $allScores->min(),
                'belowKkmCount' => $filledStudentAverages->filter(fn (float $average): bool => $average < $kkm)->count(),
                'meetsKkmCount' => $filledStudentAverages->filter(fn (float $average): bool => $average >= $kkm)->count(),
                'studentCount' => $students->count(),
                'assessmentCount' => $assessments->count(),
            ],
            'studentAnalysis' => $student === null
                ? null
                : $this->studentAnalysis($student, $assessments, $kkm),
            'students' => $students,
            'assessments' => $assessments,
        ];
    }

    /**
     * @return array{
     *     assignments: EloquentCollection<int, Pengampu>,
     *     rows: Collection<int, array<string, mixed>>,
     *     summary: array{studentCount: int, belowKkmCount: int, decliningCount: int, incompleteCount: int}
     * }
     */
    public function attention(Guru $guru, ?int $assignmentId = null, ?int $schoolYearId = null): array
    {
        $assignments = Pengampu::query()
            ->where('guru_id', $guru->id)
            ->when($assignmentId !== null, fn ($query) => $query->whereKey($assignmentId))
            ->when($schoolYearId !== null, fn ($query) => $query->where('tahun_ajaran_id', $schoolYearId))
            ->with([
                'kelas:id,nama,tingkat',
                'kelas.siswa' => fn ($query) => $query
                    ->select(['id', 'kelas_id', 'nis', 'nisn', 'nama'])
                    ->orderBy('nama')
                    ->orderBy('id'),
                'mataPelajaran:id,kode,nama,kkm',
                'tahunAjaran:id,tahun,semester,aktif',
                'penilaian' => fn ($query) => $query
                    ->with('nilai:id,siswa_id,penilaian_id,nilai')
                    ->orderBy('tanggal')
                    ->orderBy('urutan')
                    ->orderBy('id'),
            ])
            ->orderByDesc('tahun_ajaran_id')
            ->orderBy('kelas_id')
            ->orderBy('mata_pelajaran_id')
            ->get();

        $rows = $assignments->flatMap(function (Pengampu $assignment): Collection {
            $assessmentCount = $assignment->penilaian->count();
            $kkm = (float) $assignment->mataPelajaran->kkm;
            $classStudentIds = $assignment->kelas->siswa->pluck('id');

            return $assignment->kelas->siswa->map(function (Siswa $student) use ($assignment, $assessmentCount, $classStudentIds, $kkm): ?array {
                $scores = $assignment->penilaian
                    ->flatMap(fn (Penilaian $assessment): Collection => $assessment->nilai
                        ->whereIn('siswa_id', $classStudentIds)
                        ->where('siswa_id', $student->id)
                        ->pluck('nilai')
                        ->map(fn ($score): float => (float) $score));
                $average = $scores->isEmpty() ? null : round((float) $scores->average(), 2);
                $missingCount = max($assessmentCount - $scores->count(), 0);
                $trend = $this->trend($scores);
                $reasons = collect();

                if ($average !== null && $average < $kkm) {
                    $reasons->push('Rata-rata di bawah KKM');
                }

                if ($trend['label'] === 'Menurun') {
                    $reasons->push('Tren nilai menurun');
                }

                if ($missingCount > 0) {
                    $reasons->push($missingCount.' nilai belum diisi');
                }

                if ($reasons->isEmpty()) {
                    return null;
                }

                return [
                    'student' => $student,
                    'assignment' => $assignment,
                    'average' => $average,
                    'trend' => $trend,
                    'missingCount' => $missingCount,
                    'reasons' => $reasons,
                    'status' => $average !== null && $average < $kkm
                        ? 'Perlu Tindak Lanjut'
                        : ($trend['label'] === 'Menurun' ? 'Pantau Perkembangan' : 'Lengkapi Nilai'),
                    'severity' => ($average !== null && $average < $kkm ? 2 : 0)
                        + ($trend['label'] === 'Menurun' ? 1 : 0),
                ];
            })->filter();
        })->sortBy([
            ['severity', 'desc'],
            ['average', 'asc'],
        ])->values();

        return [
            'assignments' => $assignments,
            'rows' => $rows,
            'summary' => [
                'studentCount' => $rows->pluck('student.id')->unique()->count(),
                'belowKkmCount' => $rows->filter(fn (array $row): bool => $row['average'] !== null && $row['average'] < (float) $row['assignment']->mataPelajaran->kkm)->count(),
                'decliningCount' => $rows->where('trend.label', 'Menurun')->count(),
                'incompleteCount' => $rows->filter(fn (array $row): bool => $row['missingCount'] > 0)->count(),
            ],
        ];
    }

    /**
     * @param  EloquentCollection<int, Penilaian>  $assessments
     * @return array<string, mixed>
     */
    private function studentAnalysis(Siswa $student, EloquentCollection $assessments, float $kkm): array
    {
        $series = $assessments->map(function (Penilaian $assessment) use ($student): array {
            $score = $assessment->nilai->firstWhere('siswa_id', $student->id);

            return [
                'label' => $assessment->nama,
                'shortLabel' => 'P'.$assessment->urutan,
                'value' => $score === null ? null : (float) $score->nilai,
            ];
        });
        $scores = $series->pluck('value')->filter(fn (?float $score): bool => $score !== null)->values();
        $average = $scores->isEmpty() ? null : round((float) $scores->average(), 2);
        $trend = $this->trend($scores);

        return [
            'student' => $student,
            'series' => $series,
            'average' => $average,
            'latest' => $scores->isEmpty() ? null : (float) $scores->last(),
            'highest' => $scores->isEmpty() ? null : (float) $scores->max(),
            'lowest' => $scores->isEmpty() ? null : (float) $scores->min(),
            'missingCount' => max($assessments->count() - $scores->count(), 0),
            'meetsKkm' => $average !== null && $average >= $kkm,
            'trend' => $trend,
        ];
    }

    /** @param EloquentCollection<int, Penilaian> $assessments */
    private function studentScores(EloquentCollection $assessments, int $studentId): Collection
    {
        return $assessments
            ->map(fn (Penilaian $assessment) => $assessment->nilai->firstWhere('siswa_id', $studentId)?->nilai)
            ->filter(fn ($score): bool => $score !== null)
            ->map(fn ($score): float => (float) $score)
            ->values();
    }

    /** @return array{label: string, difference: float|null, explanation: string} */
    private function trend(Collection $scores): array
    {
        if ($scores->isEmpty()) {
            return [
                'label' => 'Belum ada data',
                'difference' => null,
                'explanation' => 'Belum ada nilai yang dapat digunakan untuk membaca tren.',
            ];
        }

        if ($scores->count() === 1) {
            return [
                'label' => 'Belum cukup data',
                'difference' => null,
                'explanation' => 'Diperlukan sedikitnya dua nilai untuk membandingkan perkembangan.',
            ];
        }

        $windowSize = min(3, intdiv($scores->count(), 2));
        $previousAverage = (float) $scores->slice(0, $windowSize)->average();
        $latestAverage = (float) $scores->slice(-$windowSize)->average();
        $difference = round($latestAverage - $previousAverage, 2);
        $label = $difference > 2 ? 'Meningkat' : ($difference < -2 ? 'Menurun' : 'Stabil');

        return [
            'label' => $label,
            'difference' => $difference,
            'explanation' => 'Rata-rata '.$windowSize.' nilai terbaru '.number_format($latestAverage, 2, ',', '.')
                .', dibanding '.$windowSize.' nilai sebelumnya '.number_format($previousAverage, 2, ',', '.').'.',
        ];
    }
}
