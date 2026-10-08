<?php

namespace App\Policies;

use App\Models\Pengampu;
use App\Models\User;

class PengampuPolicy
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
    public function view(User $user, Pengampu $pengampu): bool
    {
        return $user->role === 'guru' && $user->guru()->whereKey($pengampu->guru_id)->exists();
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
    public function update(User $user, Pengampu $pengampu): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Pengampu $pengampu): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Pengampu $pengampu): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Pengampu $pengampu): bool
    {
        return false;
    }
}
