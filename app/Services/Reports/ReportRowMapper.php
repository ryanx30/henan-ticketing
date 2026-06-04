<?php

namespace App\Services\Reports;

use App\Models\Ticket;
use Carbon\Carbon;

/**
 * Maps report ticket records into frontend-friendly table rows.
 */
final class ReportRowMapper
{
    public function __construct(
        private ReportSlaService $slaService,
        private ReportDurationFormatter $durationFormatter,
    ) {
    }

    public function map(Ticket $ticket): array
    {
        $responseSeconds = null;

        if ($ticket->isTeamCode('it') && $ticket->claimed_at) {
            $responseSeconds = Carbon::parse($ticket->created_at)
                ->diffInSeconds(Carbon::parse($ticket->claimed_at));
        }

        $slaSnapshot = $this->slaService->snapshot($ticket);

        return [
            'id' => $ticket->id,
            'ticket_code' => $ticket->ticket_code ? 'T-' . $ticket->ticket_code : 'T-' . $ticket->id,
            'status' => $ticket->status,
            'team' => strtoupper($ticket->displayTeamCode()),
            'sla_time' => $slaSnapshot['sla_time'],
            'response_time' => $ticket->isTeamCode('it')
                ? $this->durationFormatter->human($responseSeconds)
                : 'N/A',
            'result' => $slaSnapshot['result'],
        ];
    }
}
