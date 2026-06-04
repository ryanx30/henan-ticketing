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
     * Admin, Supervisor, and IT can view master data.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isIT();
    }

    /**
     * Admin and IT can create master data.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isIT();
    }

    /**
     * Admin and IT can update master data.
     */
    public function update(User $user, mixed $model = null): bool
    {
        return $user->isAdmin() || $user->isIT();
    }

    /**
     * Admin and IT can delete master data.
     */
    public function delete(User $user, mixed $model = null): bool
    {
        return $user->isAdmin() || $user->isIT();
    }
}
