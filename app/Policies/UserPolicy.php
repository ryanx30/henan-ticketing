<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Inactive users cannot administer other accounts.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->is_active ? null : false;
    }

    /**
     * User Management is Admin-only.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * User Management detail is Admin-only.
     */
    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Creating users is Admin-only.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Updating users is Admin-only.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Toggling active/inactive status is Admin-only.
     */
    public function toggleStatus(User $user, User $model): bool
    {
        return $user->isAdmin();
    }
}
