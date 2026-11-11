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
    public function __construct(
        private ScoreTrendService $trendService,
        private TujuanPembelajaranScoreService $outcomeService,
    ) {}

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
            'tujuanPembelajaran:id,pengampu_id,urutan,tujuan,kktp,metode_rekap',
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
        $goalSummaries = $this->outcomeService->summarize($assignment->tujuanPembelajaran, $students, $assessments);

        $classSeries = $assessments->map(function (Penilaian $assessment) use ($assignment): array {
            $scores = $assessment->nilai->pluck('nilai')->map(fn ($score): float => (float) $score);

            return [
                'assessmentId' => $assessment->id,
                'label' => $assessment->nama,
                'subjectName' => $assignment->mataPelajaran->nama,
                'assessmentName' => $assessment->nama,
                'shortLabel' => 'P'.$assessment->urutan,
                'dateLabel' => $assessment->tanggal->format('d/m'),
                'detailDateLabel' => $assessment->tanggal->locale('id')->translatedFormat('j F Y'),
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
                'classifiedOutcomeCount' => $goalSummaries->sum(fn (array $goal): int => $goal['categories']->filter(fn (array $category): bool => ! $category['isFormative'])->count()),
                'studentCount' => $students->count(),
                'assessmentCount' => $assessments->count(),
            ],
            'studentAnalysis' => $student === null
                ? null
                : $this->studentAnalysis($student, $assignment->mataPelajaran->nama, $assessments, $goalSummaries),
            'goalSummaries' => $goalSummaries,
            'students' => $students,
            'assessments' => $assessments,
        ];
    }

    /**
     * @return array{
     *     assignments: EloquentCollection<int, Pengampu>,
     *     rows: Collection<int, array<string, mixed>>,
     *     summary: array{
     *         studentCount: int,
     *         belowKktpCount: int,
     *         belowTpStudentCount: int,
     *         belowCategoryCount: int,
     *         incompleteTpStudentCount: int,
     *         decliningCount: int,
     *         incompleteCount: int,
     *         assignmentCount: int,
     *         assessmentCount: int,
     *         rosterCount: int,
     *         insufficientTrendCount: int,
     *         trendCount: int
     *     }
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
                'tujuanPembelajaran:id,pengampu_id,urutan,tujuan,kktp,metode_rekap',
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
            $classStudentIds = $assignment->kelas->siswa->pluck('id');

            $outcomes = $this->outcomeService->summarize($assignment->tujuanPembelajaran, $assignment->kelas->siswa, $assignment->penilaian);

            return $assignment->kelas->siswa->map(function (Siswa $student) use ($assignment, $assessmentCount, $classStudentIds, $outcomes): ?array {
                $scores = $assignment->penilaian
                    ->flatMap(fn (Penilaian $assessment): Collection => $assessment->nilai
                        ->whereIn('siswa_id', $classStudentIds)
                        ->where('siswa_id', $student->id)
                        ->pluck('nilai')
                        ->map(fn ($score): float => (float) $score));
                $average = $scores->isEmpty() ? null : round((float) $scores->average(), 2);
                $missingCount = max($assessmentCount - $scores->count(), 0);
                $trend = $this->trendService->summarize($scores);
                $reasons = collect();
                $studentOutcomes = $outcomes->map(function (array $goal) use ($student): array {
                    return [
                        ...$goal,
                        'categories' => $goal['categories']->map(fn (array $category): array => [
                            ...$category,
                            'result' => $category['rows']->get($student->id),
                        ]),
                    ];
                });
                $incompleteCategoryCount = $studentOutcomes->sum(fn (array $goal): int => $goal['categories']
                    ->filter(fn (array $category): bool => ! $category['isFormative'] && in_array(
                        $category['result']['status'],
                        ['Nilai belum lengkap', 'Metode rekap TP belum dipilih'],
                        true,
                    ))
                    ->count());
                $incompleteOutcomeCount = $studentOutcomes
                    ->filter(fn (array $goal): bool => $goal['categories']->contains(fn (array $category): bool => ! $category['isFormative'] && in_array(
                        $category['result']['status'],
                        ['Nilai belum lengkap', 'Metode rekap TP belum dipilih'],
                        true,
                    )))
                    ->count();
                $belowCategoryCount = $studentOutcomes->sum(fn (array $goal): int => $goal['categories']
                    ->filter(fn (array $category): bool => ! $category['isFormative'] && $category['result']['attainment'] === false)
                    ->count());
                $belowThresholdCount = $studentOutcomes
                    ->filter(fn (array $goal): bool => $goal['categories']->contains(fn (array $category): bool => ! $category['isFormative'] && $category['result']['attainment'] === false))
                    ->count();
                $unclassifiedCount = $studentOutcomes->sum(fn (array $goal): int => $goal['unclassifiedAssessments']->count());

                if ($incompleteOutcomeCount > 0) {
                    $reasons->push($incompleteOutcomeCount.' TP memiliki kategori sumatif yang belum lengkap atau metodenya belum dipilih ('.$incompleteCategoryCount.' temuan kategori); periksa kemungkinan susulan.');
                }

                if ($belowThresholdCount > 0) {
                    $reasons->push($belowThresholdCount.' TP memiliki hasil kategori sumatif di bawah KKTP ('.$belowCategoryCount.' temuan kategori).');
                }

                if ($unclassifiedCount > 0) {
                    $reasons->push($unclassifiedCount.' penilaian belum diklasifikasikan.');
                }

                if ($trend['label'] === 'Menurun') {
                    $reasons->push('Tren nilai menurun');
                }

                if ($missingCount > 0) {
                    $reasons->push($missingCount.' nilai tugas belum diisi.');
                }

                if ($reasons->isNotEmpty() && in_array($trend['label'], ['Belum ada data', 'Belum cukup data'], true)) {
                    $reasons->push('Data belum cukup untuk menentukan tren');
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
                    'incompleteOutcomeCount' => $incompleteOutcomeCount,
                    'incompleteCategoryCount' => $incompleteCategoryCount,
                    'belowThresholdCount' => $belowThresholdCount,
                    'belowCategoryCount' => $belowCategoryCount,
                    'goalSummaries' => $studentOutcomes,
                    'reasons' => $reasons,
                    'status' => $belowThresholdCount > 0 || $incompleteOutcomeCount > 0
                        ? 'Perlu Tindak Lanjut'
                        : ($trend['label'] === 'Menurun' ? 'Pantau Perkembangan' : 'Periksa Data'),
                    'severity' => ($belowThresholdCount > 0 || $incompleteOutcomeCount > 0 ? 2 : 0)
                        + ($trend['label'] === 'Menurun' ? 1 : 0),
                ];
            })->filter();
        })->sortBy([
            ['severity', 'desc'],
            ['belowThresholdCount', 'desc'],
        ])->values();

        $studentTrendAnalyses = $assignments->flatMap(function (Pengampu $assignment): Collection {
            $classStudentIds = $assignment->kelas->siswa->pluck('id');

            return $assignment->kelas->siswa->map(function (Siswa $student) use ($assignment, $classStudentIds): array {
                $scores = $assignment->penilaian
                    ->flatMap(fn (Penilaian $assessment): Collection => $assessment->nilai
                        ->whereIn('siswa_id', $classStudentIds)
                        ->where('siswa_id', $student->id)
                        ->pluck('nilai'));

                return $this->trendService->summarize($scores);
            });
        });

        return [
            'assignments' => $assignments,
            'rows' => $rows,
            'summary' => [
                'studentCount' => $rows->pluck('student.id')->unique()->count(),
                'belowKktpCount' => $rows
                    ->filter(fn (array $row): bool => $row['belowThresholdCount'] > 0)
                    ->pluck('student.id')
                    ->unique()
                    ->count(),
                'belowTpStudentCount' => $rows->sum('belowThresholdCount'),
                'belowCategoryCount' => $rows->sum('belowCategoryCount'),
                'incompleteTpStudentCount' => $rows->sum('incompleteOutcomeCount'),
                'decliningCount' => $rows->where('trend.label', 'Menurun')->pluck('student.id')->unique()->count(),
                'incompleteCount' => $rows
                    ->filter(fn (array $row): bool => $row['missingCount'] > 0)
                    ->pluck('student.id')
                    ->unique()
                    ->count(),
                'assignmentCount' => $assignments->count(),
                'assessmentCount' => $assignments->sum(fn (Pengampu $assignment): int => $assignment->penilaian->count()),
                'rosterCount' => $assignments->flatMap(fn (Pengampu $assignment): Collection => $assignment->kelas->siswa->pluck('id'))
                    ->unique()
                    ->count(),
                'insufficientTrendCount' => $studentTrendAnalyses
                    ->filter(fn (array $trend): bool => in_array($trend['label'], ['Belum ada data', 'Belum cukup data'], true))
                    ->count(),
                'trendCount' => $studentTrendAnalyses->count(),
            ],
        ];
    }

    /**
     * @param  EloquentCollection<int, Penilaian>  $assessments
     * @return array<string, mixed>
     */
    private function studentAnalysis(Siswa $student, string $subjectName, EloquentCollection $assessments, Collection $goalSummaries): array
    {
        $series = $assessments->map(function (Penilaian $assessment) use ($student, $subjectName): array {
            $score = $assessment->nilai->firstWhere('siswa_id', $student->id);

            return [
                'label' => $assessment->nama,
                'subjectName' => $subjectName,
                'assessmentName' => $assessment->nama,
                'shortLabel' => 'P'.$assessment->urutan,
                'dateLabel' => $assessment->tanggal->format('d/m'),
                'detailDateLabel' => $assessment->tanggal->locale('id')->translatedFormat('j F Y'),
                'valueLabel' => 'Nilai Siswa',
                'value' => $score === null ? null : (float) $score->nilai,
            ];
        });
        $scores = $series->pluck('value')->filter(fn (?float $score): bool => $score !== null)->values();
        $average = $scores->isEmpty() ? null : round((float) $scores->average(), 2);
        $trend = $this->trendService->summarize($series->pluck('value'));

        return [
            'student' => $student,
            'series' => $series,
            'average' => $average,
            'latest' => $scores->isEmpty() ? null : (float) $scores->last(),
            'highest' => $scores->isEmpty() ? null : (float) $scores->max(),
            'lowest' => $scores->isEmpty() ? null : (float) $scores->min(),
            'missingCount' => max($assessments->count() - $scores->count(), 0),
            'goalSummaries' => $goalSummaries->map(function (array $goal) use ($student): array {
                return [
                    ...$goal,
                    'categories' => $goal['categories']->map(function (array $category) use ($student): array {
                        return [
                            ...$category,
                            'rows' => collect([$student->id => $category['rows']->get($student->id)]),
                        ];
                    }),
                ];
            }),
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
}
