<?php

namespace App\Services;

use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GuruScoreService
{
    /** @param array<int|string, mixed> $scores */
    public function save(Penilaian $assessment, array $scores): int
    {
        $assessment->loadMissing('pengampu:id,kelas_id');
        $studentIds = Siswa::query()
            ->where('kelas_id', $assessment->pengampu->kelas_id)
            ->pluck('id');
        $now = now();
        $rows = [];
        $clearedStudentIds = [];

        foreach ($studentIds as $studentId) {
            $scoreKey = array_key_exists($studentId, $scores)
                ? $studentId
                : (string) $studentId;

            if (! array_key_exists($scoreKey, $scores)) {
                continue;
            }

            $score = $scores[$scoreKey];

            if ($score === null || $score === '') {
                $clearedStudentIds[] = $studentId;

                continue;
            }

            $rows[] = [
                'siswa_id' => $studentId,
                'penilaian_id' => $assessment->id,
                'nilai' => (float) $score,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows === [] && $clearedStudentIds === []) {
            return 0;
        }

        return DB::transaction(function () use ($assessment, $clearedStudentIds, $rows): int {
            if ($rows !== []) {
                Nilai::query()->upsert(
                    $rows,
                    ['siswa_id', 'penilaian_id'],
                    ['nilai', 'updated_at'],
                );
            }

            $deletedCount = $clearedStudentIds === []
                ? 0
                : Nilai::query()
                    ->where('penilaian_id', $assessment->id)
                    ->whereIn('siswa_id', $clearedStudentIds)
                    ->delete();

            return count($rows) + $deletedCount;
        });
    }

    /**
     * @return array{
     *     students: Collection<int, Siswa>,
     *     assessments: Collection<int, Penilaian>,
     *     rows: Collection<int, array<string, mixed>>,
     *     calculationMode: string
     * }
     */
    public function recap(Pengampu $assignment): array
    {
        $assignment->loadMissing(['kelas:id,nama,tingkat', 'mataPelajaran:id,kode,nama,kkm', 'tahunAjaran:id,tahun,semester']);
        $students = Siswa::query()
            ->where('kelas_id', $assignment->kelas_id)
            ->orderBy('nama')
            ->orderBy('id')
            ->get();
        $studentIds = $students->pluck('id');
        $assessments = Penilaian::query()
            ->where('pengampu_id', $assignment->id)
            ->with(['nilai' => fn ($query) => $query->whereIn('siswa_id', $studentIds)->select(['id', 'siswa_id', 'penilaian_id', 'nilai'])])
            ->orderBy('urutan')
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();
        $usesWeights = $assessments->isNotEmpty()
            && $assessments->every(fn (Penilaian $assessment): bool => (float) $assessment->bobot > 0);

        $rows = $students->map(function (Siswa $student) use ($assessments, $assignment, $usesWeights): array {
            $scores = $assessments->mapWithKeys(function (Penilaian $assessment) use ($student): array {
                $score = $assessment->nilai->firstWhere('siswa_id', $student->id);

                return [$assessment->id => $score === null ? null : (float) $score->nilai];
            });
            $filledScores = $scores->filter(fn (?float $score): bool => $score !== null);
            $average = $this->average($filledScores, $assessments, $usesWeights);

            return [
                'student' => $student,
                'scores' => $scores,
                'average' => $average,
                'isComplete' => $assessments->isNotEmpty() && $filledScores->count() === $assessments->count(),
                'meetsKkm' => $average !== null && $average >= (float) $assignment->mataPelajaran->kkm,
            ];
        });

        return [
            'students' => $students,
            'assessments' => $assessments,
            'rows' => $rows,
            'calculationMode' => $usesWeights ? 'Rata-rata berbobot' : 'Rata-rata sederhana',
        ];
    }

    /**
     * @param  Collection<int, float>  $filledScores
     * @param  Collection<int, Penilaian>  $assessments
     */
    private function average(Collection $filledScores, Collection $assessments, bool $usesWeights): ?float
    {
        if ($filledScores->isEmpty()) {
            return null;
        }

        if (! $usesWeights) {
            return round((float) $filledScores->average(), 2);
        }

        $weightedTotal = 0.0;
        $weightTotal = 0.0;

        foreach ($filledScores as $assessmentId => $score) {
            $weight = (float) $assessments->firstWhere('id', $assessmentId)?->bobot;
            $weightedTotal += $score * $weight;
            $weightTotal += $weight;
        }

        return $weightTotal > 0 ? round($weightedTotal / $weightTotal, 2) : null;
    }
}
