<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class GuruAnalysisService
{
    public function __construct(
        private ScoreTrendService $trendService,
        private TujuanPembelajaranScoreService $outcomeService,
        private AssessmentRosterService $rosterService,
    ) {}

    /**
     * @return array{
     *     classSeries: Collection<int, array<string, mixed>>,
     *     distribution: Collection<int, array<string, mixed>>,
     *     classStats: array<string, int|float|null>,
     *     studentAnalysis: array<string, mixed>|null,
     *     students: EloquentCollection<int, Siswa>,
     *     assessments: EloquentCollection<int, Penilaian>,
     *     excludedScoreCount: int
     * }
     */
    public function analyze(User $actor, Pengampu $assignment, ?int $studentId = null): array
    {
        $assignment->loadMissing([
            'kelas:id,nama,tingkat',
            'mataPelajaran:id,kode,nama',
            'tahunAjaran:id,tahun,semester,aktif',
            'tujuanPembelajaran:id,pengampu_id,urutan,tujuan,kktp,metode_rekap',
        ]);
        $assessments = Penilaian::query()
            ->where('pengampu_id', $assignment->id)
            ->orderBy('tanggal')
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();
        $roster = $this->rosterService->analysisRoster($actor, $assignment, $assessments);
        $students = $roster['students'];
        $selectedStudent = $studentId === null ? null : $students->firstWhere('id', $studentId);

        if ($studentId !== null && $selectedStudent === null) {
            abort(404);
        }

        $scoreRows = Nilai::query()
            ->whereIn('penilaian_id', $assessments->modelKeys())
            ->whereIn('siswa_id', $students->modelKeys())
            ->get(['id', 'siswa_id', 'penilaian_id', 'nilai'])
            ->groupBy('penilaian_id');

        foreach ($assessments as $assessment) {
            $eligibleStudentIds = $roster['studentIdsByAssessment']->get($assessment->id, collect());
            $assessment->setRelation('nilai', $scoreRows
                ->get($assessment->id, collect())
                ->filter(fn (Nilai $score): bool => $eligibleStudentIds->contains((int) $score->siswa_id))
                ->values());
        }
        $allScores = $assessments
            ->flatMap(fn (Penilaian $assessment): Collection => $assessment->nilai->pluck('nilai')->map(fn ($score): float => (float) $score));
        $studentAverages = $students
            ->mapWithKeys(function (Siswa $classStudent) use ($assessments): array {
                $scores = $this->studentScores($assessments, $classStudent->id);

                return [$classStudent->id => $scores->isEmpty() ? null : round((float) $scores->average(), 2)];
            });
        $filledStudentAverages = $studentAverages->filter(fn (?float $average): bool => $average !== null);
        $goalSummaries = $this->outcomeService->summarize(
            $assignment->tujuanPembelajaran,
            $students,
            $assessments,
            $roster['studentIdsByAssessment'],
        );

        $classSeries = $assessments->map(function (Penilaian $assessment) use ($assignment, $roster): array {
            $scores = $assessment->nilai->pluck('nilai')->map(fn ($score): float => (float) $score);

            return [
                'assessmentId' => $assessment->id,
                'label' => $assessment->nama,
                'subjectName' => $assignment->mataPelajaran->nama,
                'assessmentName' => $assessment->nama,
                'shortLabel' => 'P'.$assessment->urutan,
                'dateLabel' => $assessment->tanggal?->format('d/m') ?? 'Tanggal tidak tersedia',
                'detailDateLabel' => $assessment->tanggal?->locale('id')->translatedFormat('j F Y') ?? 'Tanggal tidak tersedia',
                'value' => $scores->isEmpty() ? null : round((float) $scores->average(), 2),
                'filledCount' => $scores->count(),
                'eligibleStudentCount' => $roster['studentIdsByAssessment']->get($assessment->id, collect())->count(),
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
            'studentAnalysis' => $selectedStudent === null
                ? null
                : $this->studentAnalysis($selectedStudent, $assignment->mataPelajaran->nama, $assessments, $goalSummaries, $roster['studentIdsByAssessment']),
            'goalSummaries' => $goalSummaries,
            'students' => $students,
            'assessments' => $assessments,
            'excludedScoreCount' => $roster['excludedScoreCount'],
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
     *         excludedScoreCount: int
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
                'mataPelajaran:id,kode,nama',
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

        $actor = $guru->user()->firstOrFail();
        $rosters = $assignments->mapWithKeys(function (Pengampu $assignment) use ($actor): array {
            return [$assignment->id => $this->rosterService->analysisRoster($actor, $assignment, $assignment->penilaian)];
        });

        foreach ($assignments as $assignment) {
            $studentIdsByAssessment = $rosters->get($assignment->id)['studentIdsByAssessment'];

            foreach ($assignment->penilaian as $assessment) {
                $eligibleStudentIds = $studentIdsByAssessment->get($assessment->id, collect());
                $assessment->setRelation('nilai', $assessment->nilai
                    ->filter(fn (Nilai $score): bool => $eligibleStudentIds->contains((int) $score->siswa_id))
                    ->values());
            }
        }

        $rows = $assignments->flatMap(function (Pengampu $assignment) use ($rosters): Collection {
            $roster = $rosters->get($assignment->id);
            $students = $roster['students'];
            $studentIdsByAssessment = $roster['studentIdsByAssessment'];
            $outcomes = $this->outcomeService->summarize(
                $assignment->tujuanPembelajaran,
                $students,
                $assignment->penilaian,
                $studentIdsByAssessment,
            );

            return $students->map(function (Siswa $student) use ($assignment, $studentIdsByAssessment, $outcomes): ?array {
                $participatingAssessmentCount = $assignment->penilaian
                    ->filter(fn (Penilaian $assessment): bool => $studentIdsByAssessment->get($assessment->id, collect())->contains($student->id))
                    ->count();
                $scores = $assignment->penilaian
                    ->flatMap(fn (Penilaian $assessment): Collection => $assessment->nilai
                        ->where('siswa_id', $student->id)
                        ->pluck('nilai')
                        ->map(fn ($score): float => (float) $score));
                $average = $scores->isEmpty() ? null : round((float) $scores->average(), 2);
                $missingCount = max($participatingAssessmentCount - $scores->count(), 0);
                $trend = $this->trendService->summarize($scores);
                $reasons = collect();
                $studentOutcomes = $outcomes->map(function (array $goal) use ($student, $studentIdsByAssessment): array {
                    return [
                        ...$goal,
                        'unclassifiedAssessments' => $goal['unclassifiedAssessments']
                            ->filter(fn (Penilaian $assessment): bool => $studentIdsByAssessment->get($assessment->id, collect())->contains($student->id))
                            ->values(),
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
                    $reasons->push([
                        'label' => $incompleteOutcomeCount.' hasil TP belum lengkap ('.$incompleteCategoryCount.' kategori).',
                        'guidance' => 'Periksa nilai sumatif yang belum diisi atau metode rekap TP yang belum ditentukan.',
                    ]);
                }

                if ($belowThresholdCount > 0) {
                    $reasons->push([
                        'label' => $belowThresholdCount.' hasil TP di bawah KKTP ('.$belowCategoryCount.' kategori).',
                        'guidance' => 'Tinjau capaian TP dan tentukan tindak lanjut pembelajaran.',
                    ]);
                }

                if ($unclassifiedCount > 0) {
                    $reasons->push([
                        'label' => $unclassifiedCount.' penilaian belum memiliki kategori asesmen.',
                        'guidance' => 'Periksa kategori asesmen pada penilaian terkait.',
                    ]);
                }

                if ($trend['label'] === 'Menurun') {
                    $reasons->push([
                        'label' => 'Tren nilai menurun.',
                        'guidance' => 'Periksa perkembangan nilai pada beberapa penilaian.',
                    ]);
                }

                if ($missingCount > 0) {
                    $reasons->push([
                        'label' => $missingCount.' nilai tugas belum diisi.',
                        'guidance' => 'Periksa dan lengkapi nilai pada penilaian terkait.',
                    ]);
                }

                if ($reasons->isNotEmpty() && in_array($trend['label'], ['Belum ada data', 'Belum cukup data'], true)) {
                    $reasons->push([
                        'label' => 'Data belum cukup untuk melihat tren.',
                        'guidance' => 'Analisis tren memerlukan sedikitnya empat nilai.',
                    ]);
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

        $studentTrendAnalyses = $assignments->flatMap(function (Pengampu $assignment) use ($rosters): Collection {
            $students = $rosters->get($assignment->id)['students'];

            return $students->map(function (Siswa $student) use ($assignment, $rosters): array {
                $studentIdsByAssessment = $rosters->get($assignment->id)['studentIdsByAssessment'];
                $scores = $assignment->penilaian
                    ->flatMap(fn (Penilaian $assessment): Collection => $studentIdsByAssessment->get($assessment->id, collect())->contains($student->id)
                        ? $assessment->nilai
                            ->where('siswa_id', $student->id)
                            ->pluck('nilai')
                        : collect());

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
                'rosterCount' => $rosters->flatMap(fn (array $roster): Collection => $roster['studentIds'])
                    ->unique()
                    ->count(),
                'excludedScoreCount' => $rosters->sum('excludedScoreCount'),
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
    private function studentAnalysis(
        Siswa $student,
        string $subjectName,
        EloquentCollection $assessments,
        Collection $goalSummaries,
        Collection $studentIdsByAssessment,
    ): array {
        $series = $assessments->map(function (Penilaian $assessment) use ($student, $subjectName, $studentIdsByAssessment): array {
            $isInRoster = $studentIdsByAssessment->get($assessment->id, collect())->contains($student->id);
            $score = $isInRoster ? $assessment->nilai->firstWhere('siswa_id', $student->id) : null;

            return [
                'label' => $assessment->nama,
                'subjectName' => $subjectName,
                'assessmentName' => $assessment->nama,
                'shortLabel' => 'P'.$assessment->urutan,
                'dateLabel' => $assessment->tanggal->format('d/m'),
                'detailDateLabel' => $assessment->tanggal?->locale('id')->translatedFormat('j F Y') ?? 'Tanggal tidak tersedia',
                'valueLabel' => 'Nilai Siswa',
                'value' => $score === null ? null : (float) $score->nilai,
                'inRoster' => $isInRoster,
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
            'missingCount' => max($series->where('inRoster', true)->count() - $scores->count(), 0),
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
