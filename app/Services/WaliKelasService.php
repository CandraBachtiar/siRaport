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

class WaliKelasService
{
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
                ['belowKkmCount', 'desc'],
                ['missingScoreCount', 'desc'],
                ['overallAverage', 'asc'],
            ])
            ->values();
        $studentAverages = $studentRows->pluck('overallAverage')->filter(fn (?float $average): bool => $average !== null);
        $subjectSeries = $assignments->map(function (Pengampu $assignment) use ($studentRows): array {
            $averages = $studentRows
                ->map(fn (array $row): ?float => $row['subjects']->firstWhere('assignment.id', $assignment->id)['average'] ?? null)
                ->filter(fn (?float $average): bool => $average !== null);

            return [
                'label' => $assignment->mataPelajaran->nama,
                'value' => $averages->isEmpty() ? 0 : round((float) $averages->average(), 2),
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
            'subjectSeries' => $subjectSeries,
            'metrics' => [
                'classCount' => $classes->count(),
                'studentCount' => $students->count(),
                'teacherAssignmentCount' => $assignments->count(),
                'subjectCount' => $assignments->pluck('mata_pelajaran_id')->unique()->count(),
                'classAverage' => $studentAverages->isEmpty() ? null : round((float) $studentAverages->average(), 2),
                'completedStudentCount' => $studentRows->where('allScoresComplete', true)->count(),
                'masteredStudentCount' => $studentRows->filter(fn (array $row): bool => $row['subjects']->isNotEmpty()
                    && $row['subjects']->every(fn (array $subject): bool => $subject['meetsKkm']))->count(),
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
        $summary = $this->subjectSummary($assignment, $student);
        $targetScore = $summary['description']?->nilai ?? $summary['referenceScore'];

        if (! $targetScore instanceof Nilai) {
            return null;
        }

        return Deskripsi::query()->updateOrCreate(
            ['nilai_id' => $targetScore->id],
            [
                'rekomendasi' => $summary['suggestion'],
                'deskripsi_akhir' => $content,
                'status' => $status,
            ],
        );
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
        $belowKkmCount = $subjects->where('meetsKkm', false)->filter(fn (array $subject): bool => $subject['average'] !== null)->count();
        $missingScoreCount = $subjects->sum('missingCount');
        $decliningSubjectCount = $subjects->where('trend.label', 'Menurun')->count();
        $attentionReasons = collect();

        if ($belowKkmCount > 0) {
            $attentionReasons->push($belowKkmCount.' mata pelajaran di bawah KKM');
        }

        if ($decliningSubjectCount > 0) {
            $attentionReasons->push($decliningSubjectCount.' mata pelajaran dengan tren menurun');
        }

        if ($missingScoreCount > 0) {
            $attentionReasons->push($missingScoreCount.' nilai belum lengkap');
        }

        $allScoresComplete = $subjects->isNotEmpty() && $subjects->every(fn (array $subject): bool => $subject['isComplete']);
        $allDescriptionsValidated = $subjects->isNotEmpty()
            && $subjects->every(fn (array $subject): bool => $subject['description']?->status === 'tervalidasi');
        $readyToPrint = $allScoresComplete && $allDescriptionsValidated;

        return [
            'student' => $student,
            'subjects' => $subjects,
            'subjectSeries' => $subjects->map(fn (array $subject): array => [
                'label' => $subject['assignment']->mataPelajaran->nama,
                'value' => $subject['average'] ?? 0,
            ]),
            'progressSeries' => $this->progressSeries($subjects),
            'overallAverage' => $averages->isEmpty() ? null : round((float) $averages->average(), 2),
            'belowKkmCount' => $belowKkmCount,
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
        $usesWeights = $assignment->penilaian->isNotEmpty()
            && $assignment->penilaian->every(fn (Penilaian $assessment): bool => (float) $assessment->bobot > 0);
        $average = $this->average($filledScores, $usesWeights);
        $descriptions = $filledScores
            ->pluck('score.deskripsi')
            ->filter()
            ->sortByDesc('updated_at');
        $description = $descriptions->first();
        $referenceScore = $filledScores->last()['score'] ?? null;
        $trend = $this->trend($values);
        $missingCount = max($assignment->penilaian->count() - $filledScores->count(), 0);
        $summary = [
            'assignment' => $assignment,
            'scores' => $scoreEntries,
            'average' => $average,
            'missingCount' => $missingCount,
            'isComplete' => $assignment->penilaian->isNotEmpty() && $missingCount === 0,
            'meetsKkm' => $average !== null && $average >= (float) $assignment->mataPelajaran->kkm,
            'trend' => $trend,
            'description' => $description,
            'referenceScore' => $referenceScore,
            'calculationMode' => $usesWeights ? 'Rata-rata berbobot' : 'Rata-rata sederhana',
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
                    'shortLabel' => $subject['assignment']->mataPelajaran->kode.' P'.$entry['assessment']->urutan,
                    'date' => $entry['assessment']->tanggal,
                    'order' => $entry['assessment']->urutan,
                    'assessmentId' => $entry['assessment']->id,
                    'value' => $entry['value'],
                ];
            }))
            ->sortBy([['date', 'asc'], ['order', 'asc'], ['assessmentId', 'asc']])
            ->values();
    }

    /** @param Collection<int, array<string, mixed>> $filledScores */
    private function average(Collection $filledScores, bool $usesWeights): ?float
    {
        if ($filledScores->isEmpty()) {
            return null;
        }

        if (! $usesWeights) {
            return round((float) $filledScores->average('value'), 2);
        }

        $weightedTotal = $filledScores->sum(fn (array $entry): float => $entry['value'] * (float) $entry['assessment']->bobot);
        $weightTotal = $filledScores->sum(fn (array $entry): float => (float) $entry['assessment']->bobot);

        return $weightTotal > 0 ? round($weightedTotal / $weightTotal, 2) : null;
    }

    /** @return array{label: string, difference: float|null, explanation: string} */
    private function trend(Collection $scores): array
    {
        if ($scores->isEmpty()) {
            return ['label' => 'Belum ada data', 'difference' => null, 'explanation' => 'Belum ada nilai untuk membaca perkembangan.'];
        }

        if ($scores->count() === 1) {
            return ['label' => 'Belum cukup data', 'difference' => null, 'explanation' => 'Diperlukan sedikitnya dua nilai untuk membaca perkembangan.'];
        }

        $windowSize = min(3, intdiv($scores->count(), 2));
        $previousAverage = (float) $scores->slice(-($windowSize * 2), $windowSize)->average();
        $latestAverage = (float) $scores->take(-$windowSize)->average();
        $difference = round($latestAverage - $previousAverage, 2);

        return [
            'label' => $difference > 2 ? 'Meningkat' : ($difference < -2 ? 'Menurun' : 'Stabil'),
            'difference' => $difference,
            'explanation' => 'Rata-rata '.$windowSize.' nilai terbaru '.number_format($latestAverage, 2, ',', '.').' dibanding nilai sebelumnya '.number_format($previousAverage, 2, ',', '.').'.',
        ];
    }

    /** @param array<string, mixed> $summary */
    private function suggestion(Siswa $student, array $summary): string
    {
        $subjectName = $summary['assignment']->mataPelajaran->nama;

        if ($summary['average'] === null) {
            return 'Belum ada nilai '.$subjectName.' yang dapat digunakan untuk menyusun deskripsi '.$student->nama.'.';
        }

        $parts = [];
        $parts[] = $summary['meetsKkm']
            ? 'Menunjukkan capaian yang baik pada '.$subjectName.' dengan rata-rata '.number_format($summary['average'], 2, ',', '.').'.'
            : 'Perlu meningkatkan capaian pada '.$subjectName.' karena rata-rata '.number_format($summary['average'], 2, ',', '.').' masih di bawah KKM '.number_format((float) $summary['assignment']->mataPelajaran->kkm, 0, ',', '.').'.';

        if ($summary['trend']['label'] === 'Meningkat') {
            $parts[] = 'Perkembangan nilai terbaru menunjukkan tren meningkat.';
        } elseif ($summary['trend']['label'] === 'Menurun') {
            $parts[] = 'Perkembangan nilai terbaru perlu dipantau karena menunjukkan tren menurun.';
        } elseif ($summary['trend']['label'] === 'Stabil') {
            $parts[] = 'Capaian nilai relatif stabil dari penilaian sebelumnya.';
        }

        if ($summary['missingCount'] > 0) {
            $parts[] = $summary['missingCount'].' penilaian belum memiliki nilai dan perlu dilengkapi sebelum deskripsi divalidasi.';
        }

        return implode(' ', $parts);
    }
}
