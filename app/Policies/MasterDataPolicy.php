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
     * Admin, Supervisor, IT, and CS can view the master data page.
     * CS is still type-scoped in AdminMasterDataApiController to Category and Issue Type only.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isIT() || $user->isCS();
    }

    /**
     * Admin, IT, and CS can create allowed master data.
     * CS is limited to categories and issue types by the API controller.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isIT() || $user->isCS();
    }

    /**
     * Admin, IT, and CS can update allowed master data.
     * CS is limited to categories and issue types by the API controller.
     */
    public function update(User $user, mixed $model = null): bool
    {
        return $user->isAdmin() || $user->isIT() || $user->isCS();
    }

    /**
     * Delete remains limited to Admin and IT. CS can add/edit, but not delete.
     */
    public function delete(User $user, mixed $model = null): bool
    {
        return $user->isAdmin() || $user->isIT();
    }
}
