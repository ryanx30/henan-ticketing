<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

/**
 * Defines role-based authorization rules for viewing, creating, updating, and deleting tickets.
 */
class TicketPolicy
{
    /**
     * Only active authenticated users can pass ticket authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->is_active ? null : false;
    }

    /**
     * Allow ticket listing for operational roles; actual row visibility is scoped in queries.
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
     * CS/Admin/Supervisor can monitor ticket details; IT is limited to IT queue visibility.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin() || $user->isSupervisor()) {
            return true;
        }

        if ($user->isCS()) {
            return true;
        }

        if ($user->isIT()) {
            // IT users may monitor all IT tickets from detail.
            // Mutations stay limited to the current holder through updateStatus()/transferHolder().
            return $ticket->isTeamCode('it');
        }

        return false;
    }

    /**
     * Only CS/Admin can create tickets from the ticket form.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [
            User::ROLE_ADMIN,
            User::ROLE_CS,
        ], true);
    }

    /**
     * CS can still update only their own non-closed tickets; Admin can update all tickets.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isCS()
            && (int) $ticket->created_by === (int) $user->id
            && $ticket->status !== 'closed';
    }

    /**
     * Destructive ticket removal is limited to Admin for production safety.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    /**
     * IT/Admin can claim unassigned IT tickets or re-claim their own ticket.
     */
    public function claim(User $user, Ticket $ticket): bool
    {
        if (!($user->isIT() || $user->isAdmin())) {
            return false;
        }

        return $ticket->isTeamCode('it')
            && $ticket->status !== 'closed'
            && (
                $ticket->holder_id === null
                || (int) $ticket->holder_id === (int) $user->id
            );
    }

    /**
     * Status transition is limited to the ticket holder for IT, or Admin.
     */
    public function updateStatus(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Allow IT to update unassigned IT tickets or tickets held by themselves.
        // Invalid lifecycle transitions, including closed -> active, are still rejected by TicketWorkflowService with 422.
        return $user->isIT()
            && $ticket->isTeamCode('it')
            && (
                $ticket->holder_id === null
                || (int) $ticket->holder_id === (int) $user->id
            );
    }

    /**
     * Ticket handoff is available only for the current same-role owner, or Admin.
     */
    public function transferHolder(User $user, Ticket $ticket): bool
    {
        if ($ticket->status === 'closed') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isCS()) {
            return (int) $ticket->created_by === (int) $user->id;
        }

        if ($user->isIT()) {
            return $ticket->isTeamCode('it')
                && (int) $ticket->holder_id === (int) $user->id;
        }

        return false;
    }
}
