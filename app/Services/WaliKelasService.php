<?php

namespace App\Services;

use App\Models\Deskripsi;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WaliKelasService
{
    public function __construct(
        private ScoreTrendService $trendService,
        private TujuanPembelajaranScoreService $outcomeService,
    ) {}

    /** @return array<string, mixed> */
    public function workspace(Guru $guru, ?int $classId = null, ?int $schoolYearId = null): array
    {
        $classes = $guru->kelasWali()
            ->withCount('siswa')
            ->orderBy('tingkat')
            ->orderBy('nama')
            ->get();
        $selectedClass = $classId === null || $classId === 0
            ? $classes->first()
            : $classes->firstWhere('id', $classId);

        if ($classId !== null && $classId > 0 && $selectedClass === null) {
            abort(404);
        }

        $schoolYears = $selectedClass === null
            ? new EloquentCollection
            : TahunAjaran::query()
                ->whereHas('pengampu', fn ($query) => $query->where('kelas_id', $selectedClass->id))
                ->orderByDesc('aktif')
                ->orderByDesc('id')
                ->get();
        $selectedSchoolYear = $schoolYearId === null || $schoolYearId === 0
            ? $schoolYears->firstWhere('aktif', true) ?? $schoolYears->first()
            : $schoolYears->firstWhere('id', $schoolYearId);

        if ($schoolYearId !== null && $schoolYearId > 0 && $selectedSchoolYear === null) {
            abort(404);
        }

        $students = $selectedClass === null
            ? new EloquentCollection
            : Siswa::query()
                ->where('kelas_id', $selectedClass->id)
                ->orderBy('nama')
                ->orderBy('id')
                ->get();
        $assignments = $this->assignments($selectedClass, $selectedSchoolYear, $students->pluck('id'));
        $studentRows = $students->map(fn (Siswa $student): array => $this->studentRow($student, $assignments));
        $attentionRows = $studentRows
            ->filter(fn (array $row): bool => $row['attentionReasons']->isNotEmpty())
            ->sortBy([
                ['belowKktpCount', 'desc'],
                ['missingScoreCount', 'desc'],
                ['overallAverage', 'asc'],
            ])
            ->values();
        $insufficientTrendCount = $studentRows->sum(fn (array $row): int => $row['subjects']
            ->filter(fn (array $subject): bool => in_array($subject['trend']['label'], ['Belum ada data', 'Belum cukup data'], true))
            ->count());
        $trendCount = $studentRows->sum(fn (array $row): int => $row['subjects']->count());
        $studentAverages = $studentRows->pluck('overallAverage')->filter(fn (?float $average): bool => $average !== null);
        $subjectSeries = $assignments->map(function (Pengampu $assignment) use ($studentRows): array {
            $averages = $studentRows
                ->map(fn (array $row): ?float => $row['subjects']->firstWhere('assignment.id', $assignment->id)['average'] ?? null)
                ->filter(fn (?float $average): bool => $average !== null);

            return [
                'label' => $assignment->mataPelajaran->nama,
                'value' => $averages->isEmpty() ? null : round((float) $averages->average(), 2),
            ];
        })->values();
        $readyReportCount = $studentRows->where('readyToPrint', true)->count();

        return [
            'classes' => $classes,
            'schoolYears' => $schoolYears,
            'selectedClass' => $selectedClass,
            'selectedSchoolYear' => $selectedSchoolYear,
            'students' => $students,
            'assignments' => $assignments,
            'studentRows' => $studentRows,
            'attentionRows' => $attentionRows,
            'attentionData' => [
                'assignmentCount' => $assignments->count(),
                'assessmentCount' => $assignments->sum(fn (Pengampu $assignment): int => $assignment->penilaian->count()),
                'studentCount' => $students->count(),
                'insufficientTrendCount' => $insufficientTrendCount,
                'trendCount' => $trendCount,
            ],
            'subjectSeries' => $subjectSeries,
            'metrics' => [
                'classCount' => $classes->count(),
                'studentCount' => $students->count(),
                'teacherAssignmentCount' => $assignments->count(),
                'subjectCount' => $assignments->pluck('mata_pelajaran_id')->unique()->count(),
                'classAverage' => $studentAverages->isEmpty() ? null : round((float) $studentAverages->average(), 2),
                'completedStudentCount' => $studentRows->where('allScoresComplete', true)->count(),
                'masteredStudentCount' => $studentRows->filter(fn (array $row): bool => $row['subjects']->isNotEmpty()
                    && $row['subjects']->every(fn (array $subject): bool => $subject['hasSumativeOutcomes']
                        && $subject['belowKktpCount'] === 0
                        && $subject['incompleteOutcomeCount'] === 0))->count(),
                'attentionCount' => $attentionRows->count(),
                'validatedDescriptionCount' => $studentRows->where('allDescriptionsValidated', true)->count(),
                'readyReportCount' => $readyReportCount,
                'reportCompletionPercentage' => $students->isEmpty() ? 0 : (int) round(($readyReportCount / $students->count()) * 100),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function subjectSummary(Pengampu $assignment, Siswa $student): array
    {
        $assignment->load([
            'mataPelajaran:id,kode,nama,kkm',
            'tahunAjaran:id,tahun,semester,aktif',
            'tujuanPembelajaran:id,pengampu_id,urutan,tujuan,kktp,metode_rekap',
            'penilaian' => fn ($query) => $query
                ->with(['nilai' => fn ($scoreQuery) => $scoreQuery
                    ->where('siswa_id', $student->id)
                    ->with('deskripsi')])
                ->orderBy('tanggal')
                ->orderBy('urutan')
                ->orderBy('id'),
        ]);

        return $this->subjectRow($assignment, $student);
    }

    public function saveDescription(Pengampu $assignment, Siswa $student, string $content, string $status): ?Deskripsi
    {
        return DB::transaction(function () use ($assignment, $student, $content, $status): ?Deskripsi {
            $assessmentIds = $assignment->penilaian()->orderBy('id')->pluck('id');
            DB::table('tujuan_pembelajaran')->where('pengampu_id', $assignment->id)->orderBy('id')->lockForUpdate()->get();
            DB::table('penilaian')->where('pengampu_id', $assignment->id)->orderBy('id')->lockForUpdate()->get();
            DB::table('nilai')->whereIn('penilaian_id', $assessmentIds)->where('siswa_id', $student->id)->orderBy('id')->lockForUpdate()->get();

            $summary = $this->subjectSummary($assignment, $student);
            $targetScore = $summary['description']?->nilai ?? $summary['referenceScore'];

            if (! $targetScore instanceof Nilai || ($status === 'tervalidasi' && ! $summary['isComplete'])) {
                return null;
            }

            return Deskripsi::query()->updateOrCreate(
                ['nilai_id' => $targetScore->id],
                [
                    'rekomendasi' => $summary['suggestion'],
                    'deskripsi_akhir' => $content,
                    'status' => $status,
                    'validasi_fingerprint' => $status === 'tervalidasi' ? $summary['academicFingerprint'] : null,
                ],
            );
        });
    }

    /** @param Collection<int, int> $studentIds */
    private function assignments(?Kelas $class, ?TahunAjaran $schoolYear, Collection $studentIds): EloquentCollection
    {
        if ($class === null || $schoolYear === null) {
            return new EloquentCollection;
        }

        return Pengampu::query()
            ->where('kelas_id', $class->id)
            ->where('tahun_ajaran_id', $schoolYear->id)
            ->with([
                'guru:id,nama',
                'mataPelajaran:id,kode,nama,kkm',
                'tahunAjaran:id,tahun,semester,aktif',
                'tujuanPembelajaran:id,pengampu_id,urutan,tujuan,kktp,metode_rekap',
                'penilaian' => fn ($query) => $query
                    ->with(['nilai' => fn ($scoreQuery) => $scoreQuery
                        ->whereIn('siswa_id', $studentIds)
                        ->with('deskripsi')])
                    ->orderBy('tanggal')
                    ->orderBy('urutan')
                    ->orderBy('id'),
            ])
            ->orderBy('mata_pelajaran_id')
            ->get()
            ->sortBy('mataPelajaran.nama')
            ->values();
    }

    /** @param EloquentCollection<int, Pengampu> $assignments */
    private function studentRow(Siswa $student, EloquentCollection $assignments): array
    {
        $subjects = $assignments->map(fn (Pengampu $assignment): array => $this->subjectRow($assignment, $student));
        $averages = $subjects->pluck('average')->filter(fn (?float $average): bool => $average !== null);
        $belowKktpCount = $subjects->sum('belowKktpCount');
        $belowKktpCategoryCount = $subjects->sum('belowKktpCategoryCount');
        $missingScoreCount = $subjects->sum('missingCount');
        $unclassifiedAssessmentCount = $subjects->where('hasUnclassifiedAssessments', true)->count();
        $decliningSubjectCount = $subjects->where('trend.label', 'Menurun')->count();
        $attentionReasons = collect();

        if ($belowKktpCount > 0) {
            $attentionReasons->push($belowKktpCount.' TP memiliki hasil kategori sumatif di bawah KKTP ('.$belowKktpCategoryCount.' temuan kategori)');
        }

        $incompleteOutcomeCount = $subjects->sum('incompleteOutcomeCount');
        $incompleteCategoryCount = $subjects->sum('incompleteCategoryCount');

        if ($incompleteOutcomeCount > 0) {
            $attentionReasons->push($incompleteOutcomeCount.' TP memiliki kategori sumatif yang belum lengkap atau metodenya belum dipilih ('.$incompleteCategoryCount.' temuan kategori); periksa kemungkinan susulan');
        }

        if ($unclassifiedAssessmentCount > 0) {
            $attentionReasons->push($unclassifiedAssessmentCount.' mata pelajaran memiliki penilaian yang belum diklasifikasikan');
        }

        if ($decliningSubjectCount > 0) {
            $attentionReasons->push($decliningSubjectCount.' mata pelajaran dengan tren menurun');
        }

        if ($missingScoreCount > 0) {
            $attentionReasons->push($missingScoreCount.' nilai tugas belum diisi');
        }

        $insufficientTrendSubjectCount = $subjects
            ->filter(fn (array $subject): bool => in_array($subject['trend']['label'], ['Belum ada data', 'Belum cukup data'], true))
            ->count();

        if ($attentionReasons->isNotEmpty() && $insufficientTrendSubjectCount > 0) {
            $attentionReasons->push($insufficientTrendSubjectCount.' mata pelajaran belum cukup data untuk menentukan tren');
        }

        $allScoresComplete = $subjects->isNotEmpty() && $subjects->every(fn (array $subject): bool => $subject['isComplete']);
        $allDescriptionsValidated = $subjects->isNotEmpty()
            && $subjects->every(fn (array $subject): bool => $subject['descriptionIsCurrent']);
        $readyToPrint = $allScoresComplete && $allDescriptionsValidated;

        return [
            'student' => $student,
            'subjects' => $subjects,
            'subjectSeries' => $subjects->map(fn (array $subject): array => [
                'label' => $subject['assignment']->mataPelajaran->nama,
                'value' => $subject['average'],
            ]),
            'progressSeries' => $this->progressSeries($subjects),
            'overallAverage' => $averages->isEmpty() ? null : round((float) $averages->average(), 2),
            'belowKktpCount' => $belowKktpCount,
            'belowKktpCategoryCount' => $belowKktpCategoryCount,
            'incompleteOutcomeCount' => $incompleteOutcomeCount,
            'incompleteCategoryCount' => $incompleteCategoryCount,
            'unclassifiedAssessmentCount' => $unclassifiedAssessmentCount,
            'missingScoreCount' => $missingScoreCount,
            'decliningSubjectCount' => $decliningSubjectCount,
            'attentionReasons' => $attentionReasons,
            'allScoresComplete' => $allScoresComplete,
            'allDescriptionsValidated' => $allDescriptionsValidated,
            'readyToPrint' => $readyToPrint,
            'reportStatus' => $readyToPrint ? 'Siap Dicetak' : ($allScoresComplete ? 'Perlu Ditinjau' : 'Belum Lengkap'),
        ];
    }

    /** @return array<string, mixed> */
    private function subjectRow(Pengampu $assignment, Siswa $student): array
    {
        $scoreEntries = $assignment->penilaian->map(function (Penilaian $assessment) use ($student): array {
            $score = $assessment->nilai->firstWhere('siswa_id', $student->id);

            return [
                'assessment' => $assessment,
                'score' => $score,
                'value' => $score === null ? null : (float) $score->nilai,
            ];
        });
        $filledScores = $scoreEntries->whereNotNull('value');
        $values = $filledScores->pluck('value');
        $average = $this->average($filledScores);
        $descriptions = $filledScores
            ->pluck('score.deskripsi')
            ->filter()
            ->sortByDesc('updated_at');
        $description = $descriptions->first();
        $referenceScore = $filledScores->last()['score'] ?? null;
        $trend = $this->trendService->summarize($values);
        $missingCount = max($assignment->penilaian->count() - $filledScores->count(), 0);
        $goalSummaries = $this->outcomeService->summarize($assignment->tujuanPembelajaran, collect([$student]), $assignment->penilaian);
        $sumativeCategories = $goalSummaries->flatMap(fn (array $goal): Collection => $goal['categories']->filter(fn (array $category): bool => ! $category['isFormative']));
        $belowKktpCategoryCount = $sumativeCategories->filter(fn (array $category): bool => $category['rows']->get($student->id)['attainment'] === false)->count();
        $belowKktpCount = $goalSummaries
            ->filter(fn (array $goal): bool => $goal['categories']->contains(fn (array $category): bool => ! $category['isFormative'] && $category['rows']->get($student->id)['attainment'] === false))
            ->count();
        $incompleteCategoryCount = $sumativeCategories->filter(fn (array $category): bool => in_array(
            $category['rows']->get($student->id)['status'],
            ['Nilai belum lengkap', 'Metode rekap TP belum dipilih'],
            true,
        ))->count();
        $incompleteOutcomeCount = $goalSummaries
            ->filter(fn (array $goal): bool => $goal['categories']->contains(fn (array $category): bool => ! $category['isFormative'] && in_array(
                $category['rows']->get($student->id)['status'],
                ['Nilai belum lengkap', 'Metode rekap TP belum dipilih'],
                true,
            )))
            ->count();
        $hasSumativeOutcomes = $sumativeCategories->isNotEmpty();
        $academicSnapshot = [
            'goals' => $assignment->tujuanPembelajaran
                ->filter(fn ($goal): bool => $assignment->penilaian->contains(fn (Penilaian $assessment): bool => $assessment->tujuan_pembelajaran_id === $goal->id && $assessment->kategori_asesmen !== Penilaian::KATEGORI_FORMATIF))
                ->map(fn ($goal): array => [
                    'id' => $goal->id,
                    'tujuan' => $goal->tujuan,
                    'kktp' => (string) $goal->kktp,
                    'metode_rekap' => $goal->metode_rekap,
                ])->all(),
            'assessments' => $assignment->penilaian
                ->filter(fn (Penilaian $assessment): bool => $assessment->tujuan_pembelajaran_id === null || $assessment->kategori_asesmen !== Penilaian::KATEGORI_FORMATIF)
                ->map(fn (Penilaian $assessment): array => [
                    'id' => $assessment->id,
                    'tp_id' => $assessment->tujuan_pembelajaran_id,
                    'kategori' => $assessment->kategori_asesmen,
                    'nilai' => $assessment->nilai->firstWhere('siswa_id', $student->id)?->nilai === null
                        ? null
                        : (string) $assessment->nilai->firstWhere('siswa_id', $student->id)->nilai,
                ])->all(),
        ];
        $academicFingerprint = hash('sha256', json_encode($academicSnapshot, JSON_THROW_ON_ERROR));
        $descriptionIsCurrent = $description?->status === 'tervalidasi'
            && $description->validasi_fingerprint !== null
            && hash_equals($description->validasi_fingerprint, $academicFingerprint);
        $hasUnclassifiedAssessments = $assignment->penilaian->contains(fn (Penilaian $assessment): bool => $assessment->tujuan_pembelajaran_id === null)
            || $goalSummaries->contains(fn (array $goal): bool => $goal['unclassifiedAssessments']->isNotEmpty());
        $isComplete = $hasSumativeOutcomes
            && $incompleteOutcomeCount === 0
            && ! $hasUnclassifiedAssessments
            && $sumativeCategories->every(fn (array $category): bool => $category['rows']->get($student->id)['isComplete']);
        $summary = [
            'assignment' => $assignment,
            'scores' => $scoreEntries,
            'average' => $average,
            'missingCount' => $missingCount,
            'isComplete' => $isComplete,
            'hasSumativeOutcomes' => $hasSumativeOutcomes,
            'hasUnclassifiedAssessments' => $hasUnclassifiedAssessments,
            'belowKktpCount' => $belowKktpCount,
            'belowKktpCategoryCount' => $belowKktpCategoryCount,
            'incompleteOutcomeCount' => $incompleteOutcomeCount,
            'incompleteCategoryCount' => $incompleteCategoryCount,
            'goalSummaries' => $goalSummaries,
            'trend' => $trend,
            'description' => $description,
            'descriptionIsCurrent' => $descriptionIsCurrent,
            'academicFingerprint' => $academicFingerprint,
            'referenceScore' => $referenceScore,
            'calculationMode' => 'Ringkasan nilai tugas terisi; bukan nilai akhir',
        ];

        return [
            ...$summary,
            'suggestion' => $this->suggestion($student, $summary),
        ];
    }

    /** @param Collection<int, array<string, mixed>> $subjects */
    private function progressSeries(Collection $subjects): Collection
    {
        return $subjects
            ->flatMap(fn (array $subject): Collection => $subject['scores']->map(function (array $entry) use ($subject): array {
                return [
                    'label' => $subject['assignment']->mataPelajaran->nama.' · '.$entry['assessment']->nama,
                    'subjectName' => $subject['assignment']->mataPelajaran->nama,
                    'assessmentName' => $entry['assessment']->nama,
                    'shortLabel' => $subject['assignment']->mataPelajaran->kode.' P'.$entry['assessment']->urutan,
                    'date' => $entry['assessment']->tanggal,
                    'dateLabel' => $entry['assessment']->tanggal->format('d/m'),
                    'detailDateLabel' => $entry['assessment']->tanggal->locale('id')->translatedFormat('j F Y'),
                    'order' => $entry['assessment']->urutan,
                    'assessmentId' => $entry['assessment']->id,
                    'value' => $entry['value'],
                ];
            }))
            ->sortBy([['date', 'asc'], ['order', 'asc'], ['assessmentId', 'asc']])
            ->values();
    }

    /** @param Collection<int, array<string, mixed>> $filledScores */
    private function average(Collection $filledScores): ?float
    {
        if ($filledScores->isEmpty()) {
            return null;
        }

        return round((float) $filledScores->average('value'), 2);
    }

    /** @param array<string, mixed> $summary */
    private function suggestion(Siswa $student, array $summary): string
    {
        $subjectName = $summary['assignment']->mataPelajaran->nama;

        if (! $summary['hasSumativeOutcomes']) {
            return 'Belum ada hasil sumatif per TP yang dapat digunakan untuk menyusun deskripsi '.$student->nama.' pada '.$subjectName.'.';
        }

        $strongest = collect();
        $needsImprovement = collect();
        $insufficientCategories = collect();

        foreach ($summary['goalSummaries'] as $goal) {
            foreach ($goal['categories'] as $category) {
                if ($category['isFormative']) {
                    continue;
                }

                $result = $category['rows']->get($student->id);
                $label = 'TP '.$goal['goal']->urutan.' ('.$goal['goal']->tujuan.')';

                if ($result['isComplete'] && $result['attainment'] === true) {
                    $strongest->push(['label' => $label, 'score' => $result['score']]);
                } elseif ($result['isComplete'] && $result['attainment'] === false) {
                    $needsImprovement->push($label);
                } else {
                    $insufficientCategories->push($label);
                }
            }
        }

        $parts = [];
        if ($strongest->isNotEmpty()) {
            $highestScore = $strongest->max('score');
            $labels = $strongest->where('score', $highestScore)->pluck('label')->implode(', ');
            $parts[] = 'Capaian terkuat: '.$labels.' ('.$highestScore.').';
        }

        if ($needsImprovement->isNotEmpty()) {
            $parts[] = 'Perlu ditingkatkan: '.implode(', ', $needsImprovement->all()).' karena hasil sumatif belum mencapai KKTP.';
        } elseif ($strongest->isNotEmpty()) {
            $parts[] = 'Seluruh capaian sumatif yang lengkap telah mencapai KKTP; tidak ada bagian yang ditandai belum tuntas.';
        }

        if ($parts === []) {
            $parts[] = 'Data sumatif per TP belum cukup untuk menyusun rekomendasi capaian.';
        }

        if ($insufficientCategories->isNotEmpty()) {
            $parts[] = 'Hasil belum lengkap untuk: '.implode(', ', $insufficientCategories->unique()->all()).'.';
        }

        if ($summary['hasUnclassifiedAssessments']) {
            $parts[] = 'Ada penilaian yang belum dipetakan ke TP atau kategori dan perlu ditinjau.';
        }

        if ($summary['trend']['label'] === 'Meningkat') {
            $parts[] = 'Perkembangan nilai terbaru menunjukkan tren meningkat.';
        } elseif ($summary['trend']['label'] === 'Menurun') {
            $parts[] = 'Perkembangan nilai terbaru perlu dipantau karena menunjukkan tren menurun.';
        } elseif ($summary['trend']['label'] === 'Stabil') {
            $parts[] = 'Capaian nilai relatif stabil dari penilaian sebelumnya.';
        }

        if ($summary['missingCount'] > 0) {
            $parts[] = $summary['missingCount'].' nilai tugas belum diisi; periksa kemungkinan susulan sebelum deskripsi divalidasi.';
        }

        return implode(' ', $parts);
    }
}
