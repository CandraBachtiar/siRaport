<?php

namespace App\Services;

use App\Data\PlacementHistoryLookup;
use App\Enums\AssessmentPlacementStatus;
use App\Enums\PlacementHistoryStatus;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AssessmentPlacementChecker
{
    public function __construct(private AuthorizedPlacementHistoryLookup $historyLookup) {}

    /**
     * @param  iterable<int, int>  $authorizedStudentIds  from the caller's authorized roster
     * @return Collection<int, AssessmentPlacementStatus|PlacementHistoryLookup>
     */
    public function check(User $actor, Pengampu $assignment, Penilaian $assessment, iterable $authorizedStudentIds): Collection
    {
        Gate::forUser($actor)->authorize('view', $assignment);

        if ((int) $assessment->pengampu_id !== (int) $assignment->id) {
            throw ValidationException::withMessages(['penilaian' => 'Penilaian tidak terkait dengan Pengampu yang dipilih.']);
        }

        $studentIds = collect($authorizedStudentIds)->map(fn ($id): int => (int) $id)->filter(fn (int $id): bool => $id > 0)->unique()->values();
        $authorizedIds = Siswa::query()
            ->where('kelas_id', $assignment->kelas_id)
            ->whereIn('id', $studentIds)
            ->pluck('id');

        if ($assessment->tanggal === null) {
            return $authorizedIds->mapWithKeys(fn (int $id): array => [$id => AssessmentPlacementStatus::DateUnavailable]);
        }

        $lookups = $this->historyLookup->forAssignmentDate($authorizedIds, $assignment, $assessment->tanggal->toDateString());

        return $lookups->map(fn (PlacementHistoryLookup $lookup): AssessmentPlacementStatus|PlacementHistoryLookup => match ($lookup->status) {
            PlacementHistoryStatus::Verified => AssessmentPlacementStatus::MatchesAssignment,
            PlacementHistoryStatus::DifferentClass => AssessmentPlacementStatus::DifferentClass,
            PlacementHistoryStatus::Unknown => AssessmentPlacementStatus::Unknown,
            PlacementHistoryStatus::Conflict => AssessmentPlacementStatus::Conflict,
        });
    }
}
