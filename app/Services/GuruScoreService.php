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
    public function __construct(private TujuanPembelajaranScoreService $outcomeService) {}

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
     *     goalSummaries: Collection<int, array<string, mixed>>,
     *     calculationMode: string
     * }
     */
    public function recap(Pengampu $assignment): array
    {
        $assignment->loadMissing([
            'kelas:id,nama,tingkat',
            'mataPelajaran:id,kode,nama',
            'tahunAjaran:id,tahun,semester',
            'tujuanPembelajaran:id,pengampu_id,urutan,tujuan,kktp,metode_rekap',
        ]);
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

        $goalSummaries = $this->outcomeService->summarize($assignment->tujuanPembelajaran, $students, $assessments);

        return [
            'students' => $students,
            'assessments' => $assessments,
            'goalSummaries' => $goalSummaries,
            'calculationMode' => 'Rekap terpisah per TP dan kategori asesmen',
        ];
    }
}
