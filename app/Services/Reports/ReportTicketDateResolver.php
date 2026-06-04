<?php

namespace App\Services\Reports;

use App\Models\Ticket;
use Carbon\Carbon;

/**
 * Resolves the effective report date for completed tickets and activity-based reporting.
 */
final class ReportTicketDateResolver
{
    public function completedAt(Ticket $ticket): ?Carbon
    {
        if ($ticket->resolved_at) {
            return Carbon::parse($ticket->resolved_at);
        }

        if ($ticket->closed_at) {
            return Carbon::parse($ticket->closed_at);
        }

        return null;
    }
}
