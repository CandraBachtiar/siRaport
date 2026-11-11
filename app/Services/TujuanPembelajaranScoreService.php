<?php

namespace App\Services;

use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\TujuanPembelajaran;
use Illuminate\Support\Collection;

class TujuanPembelajaranScoreService
{
    /**
     * @param  Collection<int, TujuanPembelajaran>  $goals
     * @param  Collection<int, Siswa>  $students
     * @param  Collection<int, Penilaian>  $assessments
     * @return Collection<int, array<string, mixed>>
     */
    public function summarize(Collection $goals, Collection $students, Collection $assessments): Collection
    {
        return $goals->map(function (TujuanPembelajaran $goal) use ($students, $assessments): array {
            $goalAssessments = $assessments
                ->where('tujuan_pembelajaran_id', $goal->id)
                ->values();
            $classifiedAssessments = $goalAssessments->whereNotNull('kategori_asesmen');
            $categories = $classifiedAssessments
                ->groupBy('kategori_asesmen')
                ->map(fn (Collection $categoryAssessments, string $category): array => [
                    'key' => $category,
                    'label' => Penilaian::KATEGORI_LABELS[$category] ?? $category,
                    'isFormative' => $category === Penilaian::KATEGORI_FORMATIF,
                    'assessments' => $categoryAssessments->values(),
                    'rows' => $students->mapWithKeys(fn (Siswa $student): array => [
                        $student->id => $this->studentResult($goal, $category, $categoryAssessments, $student->id),
                    ]),
                ]);

            return [
                'goal' => $goal,
                'assessments' => $goalAssessments,
                'unclassifiedAssessments' => $goalAssessments->whereNull('kategori_asesmen')->values(),
                'categories' => $categories,
            ];
        });
    }

    /** @param Collection<int, Penilaian> $assessments
     * @return array<string, mixed>
     */
    private function studentResult(TujuanPembelajaran $goal, string $category, Collection $assessments, int $studentId): array
    {
        $scores = $assessments->mapWithKeys(function (Penilaian $assessment) use ($studentId): array {
            $score = $assessment->nilai->firstWhere('siswa_id', $studentId);

            return [$assessment->id => $score === null ? null : (float) $score->nilai];
        });
        $filledScores = $scores->filter(fn (?float $score): bool => $score !== null)->values();
        $missingCount = $assessments->count() - $filledScores->count();
        $score = $this->aggregate($filledScores, $goal->metode_rekap);
        $isFormative = $category === Penilaian::KATEGORI_FORMATIF;
        $isComplete = $assessments->isNotEmpty() && $missingCount === 0;
        $attainment = null;

        if ($isFormative) {
            $status = $missingCount > 0
                ? 'Nilai belum lengkap · umpan balik pembelajaran'
                : 'Umpan balik pembelajaran';
        } elseif ($goal->metode_rekap === null) {
            $status = 'Metode rekap TP belum dipilih';
        } elseif ($missingCount > 0) {
            $status = 'Nilai belum lengkap';
        } elseif ($score === null) {
            $status = 'Belum ada nilai';
        } else {
            $attainment = $score >= (float) $goal->kktp;
            $status = $attainment ? 'Mencapai KKTP' : 'Belum mencapai KKTP';
        }

        return [
            'scores' => $scores,
            'score' => $score,
            'filledCount' => $filledScores->count(),
            'totalCount' => $assessments->count(),
            'missingCount' => $missingCount,
            'isComplete' => $isComplete,
            'attainment' => $attainment,
            'status' => $status,
        ];
    }

    /** @param Collection<int, float> $scores */
    private function aggregate(Collection $scores, ?string $method): ?float
    {
        if ($scores->isEmpty() || $method === null) {
            return null;
        }

        return match ($method) {
            TujuanPembelajaran::METODE_RATA_RATA => (float) $scores->average(),
            TujuanPembelajaran::METODE_NILAI_TERTINGGI => (float) $scores->max(),
            default => null,
        };
    }
}
