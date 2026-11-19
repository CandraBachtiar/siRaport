<?php

namespace App\Services;

use App\Data\PlacementHistoryLookup;
use App\Enums\PlacementHistoryStatus;
use App\Models\RiwayatPenempatanSiswa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class PlacementHistoryReader
{
    /**
     * Read the verified placement for a student on a calendar date.
     *
     * Both mulai_pada and berakhir_pada are inclusive. A null berakhir_pada
     * means the placement has no recorded end. More than one placement
     * matching the student, academic period, and date is reported as a conflict.
     * No matching history remains unknown; siswa.kelas_id is not a historical fallback.
     */
    public function forDate(int $studentId, int $classId, int $schoolYearId, string $date): PlacementHistoryLookup
    {
        $placements = RiwayatPenempatanSiswa::query()
            ->where('siswa_id', $studentId)
            ->where('tahun_ajaran_id', $schoolYearId)
            ->whereDate('mulai_pada', '<=', $date)
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('berakhir_pada')->orWhereDate('berakhir_pada', '>=', $date);
            })
            ->orderBy('mulai_pada')
            ->orderBy('id')
            ->get();

        return $this->lookup($placements, $classId);
    }

    /** @param iterable<int, int> $studentIds @return SupportCollection<int, PlacementHistoryLookup> */
    public function forStudents(iterable $studentIds, int $classId, int $schoolYearId, string $date): SupportCollection
    {
        $studentIds = collect($studentIds)->map(fn ($id): int => (int) $id)->filter(fn (int $id): bool => $id > 0)->unique()->values();
        $results = $studentIds->mapWithKeys(fn (int $id): array => [$id => new PlacementHistoryLookup(PlacementHistoryStatus::Unknown, new Collection)]);

        if ($studentIds->isEmpty()) {
            return $results;
        }

        $placementsByStudent = RiwayatPenempatanSiswa::query()
            ->whereIn('siswa_id', $studentIds)
            ->where('tahun_ajaran_id', $schoolYearId)
            ->whereDate('mulai_pada', '<=', $date)
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('berakhir_pada')->orWhereDate('berakhir_pada', '>=', $date);
            })
            ->orderBy('siswa_id')
            ->orderBy('mulai_pada')
            ->orderBy('id')
            ->get()
            ->groupBy('siswa_id');

        foreach ($placementsByStudent as $studentId => $placements) {
            $results->put((int) $studentId, $this->lookup($placements, $classId));
        }

        return $results;
    }

    private function lookup(Collection $placements, int $classId): PlacementHistoryLookup
    {
        if ($placements->count() > 1) {
            return new PlacementHistoryLookup(PlacementHistoryStatus::Conflict, $placements);
        }

        if ($placements->isEmpty()) {
            return new PlacementHistoryLookup(PlacementHistoryStatus::Unknown, new Collection);
        }

        $placement = $placements->first();
        $status = $placement->kelas_id === $classId
            ? PlacementHistoryStatus::Verified
            : PlacementHistoryStatus::DifferentClass;

        return new PlacementHistoryLookup($status, $placements);
    }
}
