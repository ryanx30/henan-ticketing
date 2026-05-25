<?php

namespace App\Policies;

use App\Models\User;

class MasterDataPolicy
{
    /**
     * Inactive users cannot access master data administration.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->is_active ? null : false;
    }

    /**
     * Admin and Supervisor can view master data.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    /**
     * Master data mutations are Admin-only.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Master data mutations are Admin-only.
     */
    public function update(User $user, mixed $model = null): bool
    {
        return $user->isAdmin();
    }

    /**
     * Master data mutations are Admin-only.
     */
    public function delete(User $user, mixed $model = null): bool
    {
        return $user->isAdmin();
    }
}
