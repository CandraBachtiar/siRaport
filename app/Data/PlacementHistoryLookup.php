<?php

namespace App\Data;

use App\Enums\PlacementHistoryStatus;
use App\Models\RiwayatPenempatanSiswa;
use Illuminate\Database\Eloquent\Collection;

class PlacementHistoryLookup
{
    /** @param Collection<int, RiwayatPenempatanSiswa> $placements */
    public function __construct(
        public PlacementHistoryStatus $status,
        public Collection $placements,
    ) {}

    public function placement(): ?RiwayatPenempatanSiswa
    {
        return $this->status === PlacementHistoryStatus::Verified
            ? $this->placements->first()
            : null;
    }
}
