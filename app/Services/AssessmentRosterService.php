<?php

namespace App\Services;

use App\Enums\AssessmentPlacementStatus;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\RiwayatPenempatanSiswa;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AssessmentRosterService
{
    public function __construct(private AssessmentPlacementChecker $placementChecker) {}

    /**
     * Return verified student IDs for an assessment.
     *
     * @return Collection<int, int>
     */
    public function verifiedStudentIds(User $actor, Penilaian $assessment): Collection
    {
        $assessment->loadMissing('pengampu');

        $assignment = $assessment->pengampu;

        Gate::forUser($actor)->authorize('view', $assessment);

        if ($assessment->tanggal === null) {
            return collect();
        }

        $date = $assessment->tanggal->toDateString();

        $placements = RiwayatPenempatanSiswa::query()
            ->whereHas('siswa')
            ->where('tahun_ajaran_id', $assignment->tahun_ajaran_id)
            ->whereDate('mulai_pada', '<=', $date)
            ->where(function ($query) use ($date): void {
                $query->whereNull('berakhir_pada')
                    ->orWhereDate('berakhir_pada', '>=', $date);
            })
            ->get();

        return $placements
            ->groupBy('siswa_id')
            ->filter(fn ($records) => $records->count() === 1
                && (int) $records->first()->kelas_id === (int) $assignment->kelas_id)
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->values();
    }

    /**
     * Include the current class roster for legacy records, plus students whose
     * placement is verified for this assessment's historical date.
     *
     * @return Collection<int, int>
     */
    public function studentIds(User $actor, Penilaian $assessment): Collection
    {
        $assessment->loadMissing('pengampu');
        $assignment = $assessment->pengampu;

        Gate::forUser($actor)->authorize('view', $assessment);

        $currentStudentIds = Siswa::query()
            ->where('kelas_id', $assignment->kelas_id)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id);

        return $currentStudentIds
            ->concat($this->verifiedStudentIds($actor, $assessment))
            ->unique()
            ->values();
    }

    /**
     * Resolve students eligible for analysis on the assessment's actual date.
     * Legacy students still active in the assigned class remain eligible when
     * placement history is unknown; moved students require a matching history.
     * An assessment without a date is never treated as a historical match.
     *
     * @return Collection<int, int>
     */
    public function analysisStudentIds(User $actor, Penilaian $assessment): Collection
    {
        $assessment->loadMissing('pengampu');
        $assignment = $assessment->pengampu;
        $currentClassIds = Siswa::query()
            ->where('kelas_id', $assignment->kelas_id)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id);
        $candidateIds = $currentClassIds
            ->concat($this->verifiedStudentIds($actor, $assessment))
            ->unique()
            ->values();
        $statuses = $this->placementChecker->check($actor, $assignment, $assessment, $candidateIds);

        return $statuses
            ->filter(fn (AssessmentPlacementStatus $status, int $studentId): bool => $status === AssessmentPlacementStatus::MatchesAssignment
                || ($currentClassIds->contains($studentId)
                    && $status === AssessmentPlacementStatus::Unknown))
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->values();
    }

    /**
     * Resolve the semester union and per-assessment cohort for one Pengampu.
     * Saved scores outside a verified/current roster are retained in storage,
     * but excluded from historical calculations until their placement is resolved.
     *
     * @param  EloquentCollection<int, Penilaian>  $assessments
     * @return array{
     *     students: EloquentCollection<int, Siswa>,
     *     studentIds: Collection<int, int>,
     *     studentIdsByAssessment: Collection<int, Collection<int, int>>,
     *     excludedScoreCount: int
     * }
     */
    public function analysisRoster(User $actor, Pengampu $assignment, EloquentCollection $assessments): array
    {
        Gate::forUser($actor)->authorize('view', $assignment);

        foreach ($assessments as $assessment) {
            if ((int) $assessment->pengampu_id !== (int) $assignment->id) {
                throw ValidationException::withMessages(['penilaian' => 'Penilaian tidak terkait dengan Pengampu yang dipilih.']);
            }
        }

        $studentIdsByAssessment = $assessments->mapWithKeys(fn (Penilaian $assessment): array => [
            $assessment->id => $this->analysisStudentIds($actor, $assessment),
        ]);
        $studentIds = $studentIdsByAssessment
            ->flatMap(fn (Collection $ids): Collection => $ids)
            ->concat($assessments->isEmpty()
                ? Siswa::query()->where('kelas_id', $assignment->kelas_id)->pluck('id')
                : collect())
            ->unique()
            ->values();
        $students = Siswa::query()
            ->whereIn('id', $studentIds)
            ->orderBy('nama')
            ->orderBy('id')
            ->get();
        $scores = $assessments->isEmpty()
            ? collect()
            : Nilai::query()
                ->whereIn('penilaian_id', $assessments->modelKeys())
                ->get(['siswa_id', 'penilaian_id']);
        $excludedScoreCount = $scores->filter(fn (Nilai $score): bool => ! $studentIdsByAssessment
            ->get($score->penilaian_id, collect())
            ->contains((int) $score->siswa_id))->count();

        return [
            'students' => $students,
            'studentIds' => $studentIds,
            'studentIdsByAssessment' => $studentIdsByAssessment,
            'excludedScoreCount' => $excludedScoreCount,
        ];
    }
}
