<?php

namespace App\Policies;

use App\Models\User;

/**
 * Protects master data operations based on system-control role access.
 */
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
     * Admin and Head CS can manage master data, while Supervisor and IT can view it only.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isHeadCS() || $user->isSupervisor() || $user->isIT();
    }

    /**
     * Master data creation is limited to Admin and Head CS.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isHeadCS();
    }

    /**
     * Master data updates are limited to Admin and Head CS.
     */
    public function update(User $user, mixed $model = null): bool
    {
        return $user->isAdmin() || $user->isHeadCS();
    }

    /**
     * Physical deletes are disabled for master data to preserve historical relations.
     */
    public function delete(User $user, mixed $model = null): bool
    {
        return false;
    }
}
