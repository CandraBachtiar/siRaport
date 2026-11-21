<?php

namespace App\Services;

use App\Models\Penilaian;
use App\Models\RiwayatPenempatanSiswa;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class AssessmentRosterService
{
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
}
