<?php

namespace App\Policies;

use App\Models\TicketAttachment;
use App\Models\User;

/**
 * Controls access to ticket attachment downloads and attachment-related actions.
 */
class TicketAttachmentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->is_active ? null : false;
    }

    public function view(User $user, TicketAttachment $attachment): bool
    {
        $ticket = $attachment->ticket;

        if (! $ticket) {
            return false;
        }

        if ($user->isAdmin() || $user->isSupervisor() || $user->isHeadCS()) {
            return true;
        }

        if ($user->isCS()) {
            return (int) $ticket->created_by === (int) $user->id;
        }

        if ($user->isIT()) {
            return $ticket->isTeamCode('it')
                && (
                    $ticket->holder_id === null
                    || (int) $ticket->holder_id === (int) $user->id
                );
        }

        return false;
    }
}
