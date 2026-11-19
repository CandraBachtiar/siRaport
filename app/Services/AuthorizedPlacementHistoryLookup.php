<?php

namespace App\Services;

use App\Data\PlacementHistoryLookup;
use App\Models\Pengampu;
use Illuminate\Support\Collection;

class AuthorizedPlacementHistoryLookup
{
    public function __construct(private PlacementHistoryReader $reader) {}

    /**
     * This adapter is not an authorization boundary. Callers must authorize the assignment
     * and derive these student IDs from their already-authorized roster before calling it.
     * Never pass IDs directly from request input. The reference date must come from the
     * academic record being examined, such as Penilaian::tanggal; it is never inferred here.
     *
     * @param  iterable<int, int>  $authorizedStudentIds
     * @return Collection<int, PlacementHistoryLookup>
     */
    public function forAssignmentDate(iterable $authorizedStudentIds, Pengampu $assignment, string $referenceDate): Collection
    {
        return $this->reader->forStudents(
            $authorizedStudentIds,
            (int) $assignment->kelas_id,
            (int) $assignment->tahun_ajaran_id,
            $referenceDate,
        );
    }
}
