<?php

namespace App\Policies;

use App\Models\ResolverMessage;
use App\Models\User;

class ResolverMessagePolicy
{
    /**
     * Block all resolver-message abilities for inactive users.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->is_active ? null : false;
    }

    /**
     * Operational roles can open their scoped inbox; query scoping still limits rows.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            User::ROLE_ADMIN,
            User::ROLE_SUPERVISOR,
            User::ROLE_CS,
            User::ROLE_IT,
        ], true);
    }

    /**
     * Admin and supervisors can monitor every resolver message.
     * CS and IT can open conversation details when they are part of the message.
     */
    public function view(User $user, ResolverMessage $resolverMessage): bool
    {
        if ($user->isAdmin() || $user->isSupervisor()) {
            return true;
        }

        return (int) $resolverMessage->to_user_id === (int) $user->id
            || (int) $resolverMessage->from_user_id === (int) $user->id;
    }

    /**
     * CS, IT, and Admin can compose resolver messages for authorized tickets.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [
            User::ROLE_ADMIN,
            User::ROLE_CS,
            User::ROLE_IT,
        ], true);
    }

    /**
     * Only the actual recipient or elevated roles can mark a message as read.
     */
    public function markAsRead(User $user, ResolverMessage $resolverMessage): bool
    {
        return $user->isAdmin()
            || $user->isSupervisor()
            || (int) $resolverMessage->to_user_id === (int) $user->id;
    }

    /**
     * Admin and supervisors can manage the monitoring inbox.
     * CS and IT can delete messages that involve them.
     */
    public function delete(User $user, ResolverMessage $resolverMessage): bool
    {
        return $user->isAdmin()
            || $user->isSupervisor()
            || (int) $resolverMessage->to_user_id === (int) $user->id
            || (int) $resolverMessage->from_user_id === (int) $user->id;
    }
}
