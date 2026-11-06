<?php

namespace App\Policies;

use App\Models\TujuanPembelajaran;
use App\Models\User;

class TujuanPembelajaranPolicy
{
    public function view(User $user, TujuanPembelajaran $tujuanPembelajaran): bool
    {
        return $this->ownsTujuanPembelajaran($user, $tujuanPembelajaran);
    }

    public function update(User $user, TujuanPembelajaran $tujuanPembelajaran): bool
    {
        return $this->ownsTujuanPembelajaran($user, $tujuanPembelajaran);
    }

    public function delete(User $user, TujuanPembelajaran $tujuanPembelajaran): bool
    {
        return $this->ownsTujuanPembelajaran($user, $tujuanPembelajaran);
    }

    private function ownsTujuanPembelajaran(User $user, TujuanPembelajaran $tujuanPembelajaran): bool
    {
        return $user->role === 'guru'
            && $user->guru()
                ->whereHas('pengampu', fn ($query) => $query->whereKey($tujuanPembelajaran->pengampu_id))
                ->exists();
    }
}
