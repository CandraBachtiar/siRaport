<?php

namespace App\Policies;

use App\Models\Penilaian;
use App\Models\User;

class PenilaianPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'guru' && $user->guru()->whereHas('pengampu')->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Penilaian $penilaian): bool
    {
        return $this->ownsAssessment($user, $penilaian);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Penilaian $penilaian): bool
    {
        return $this->ownsAssessment($user, $penilaian);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Penilaian $penilaian): bool
    {
        return $this->ownsAssessment($user, $penilaian);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Penilaian $penilaian): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Penilaian $penilaian): bool
    {
        return false;
    }

    private function ownsAssessment(User $user, Penilaian $penilaian): bool
    {
        if ($user->role !== 'guru') {
            return false;
        }

        return $user->guru()
            ->whereHas('pengampu', fn ($query) => $query->whereKey($penilaian->pengampu_id))
            ->exists();
    }
}
