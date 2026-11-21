<?php

namespace App\Services;

use App\Enums\AssessmentPlacementStatus;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuruScoreService
{
    public function __construct(
        private TujuanPembelajaranScoreService $outcomeService,
        private AssessmentRosterService $rosterService,
        private AssessmentPlacementChecker $placementChecker,
    ) {}

    /** @param array<int|string, mixed> $scores */
    public function save(User $actor, Penilaian $assessment, array $scores): int
    {
        $assessment->loadMissing('pengampu:id,kelas_id,tahun_ajaran_id');
        $candidateIds = $this->rosterService->studentIds($actor, $assessment);
        $statuses = $this->placementChecker->check($actor, $assessment->pengampu, $assessment, $candidateIds);
        $currentStudentIds = Siswa::query()
            ->where('kelas_id', $assessment->pengampu->kelas_id)
            ->pluck('id');
        $allowedIds = $statuses
            ->filter(fn ($status, int $studentId): bool => $status === AssessmentPlacementStatus::MatchesAssignment
                || ($currentStudentIds->contains($studentId)
                    && in_array($status, [AssessmentPlacementStatus::Unknown, AssessmentPlacementStatus::DateUnavailable], true)))
            ->keys()
            ->map(fn ($id): int => (int) $id);

        foreach (array_keys($scores) as $studentId) {
            if (! is_string($studentId) && ! is_int($studentId) || ! preg_match('/^[1-9][0-9]*$/', (string) $studentId)
                || ! $allowedIds->contains((int) $studentId)) {
                throw ValidationException::withMessages(['nilai.'.$studentId => 'Siswa tidak termasuk dalam roster penilaian ini.']);
            }
        }
        $now = now();
        $rows = [];
        $clearedStudentIds = [];

        foreach ($allowedIds as $studentId) {
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
    public function recap(User $actor, Pengampu $assignment): array
    {
        $assignment->loadMissing([
            'kelas:id,nama,tingkat',
            'mataPelajaran:id,kode,nama',
            'tahunAjaran:id,tahun,semester',
            'tujuanPembelajaran:id,pengampu_id,urutan,tujuan,kktp,metode_rekap',
        ]);
        $assessments = Penilaian::query()
            ->where('pengampu_id', $assignment->id)
            ->orderBy('urutan')
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();
        $rosterIds = $assessments
            ->flatMap(fn (Penilaian $assessment): Collection => $assessment->tanggal === null
                ? collect()
                : $this->rosterService->verifiedStudentIds($actor, $assessment))
            ->concat(Siswa::query()->where('kelas_id', $assignment->kelas_id)->pluck('id'))
            ->concat(Nilai::query()
                ->whereIn('penilaian_id', $assessments->modelKeys())
                ->pluck('siswa_id'))
            ->unique()
            ->values();
        $students = Siswa::query()
            ->whereIn('id', $rosterIds)
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
