<?php

namespace App\Services\Reports;

use App\Models\Ticket;
use App\Models\User;
use App\Queries\ReportTicketQuery;
use App\Support\TicketStatus;
use Carbon\Carbon;

/**
 * Calculates SLA breach metrics for report cards and SLA tracking rows.
 */
final class ReportSlaService
{
    public function __construct(
        private ReportTicketQuery $reportTicketQuery,
        private ReportTicketDateResolver $dateResolver,
        private ReportDurationFormatter $durationFormatter,
    ) {
    }

    public function breachRate(Carbon $start, Carbon $end, string $scope, User $user, ?int $selectedUserId = null): float
    {
        $tickets = $this->reportTicketQuery->activity($start, $end, $scope, $user, $selectedUserId)
            ->forTeamCode('it')
            ->whereNotNull('sla_deadline_at')
            ->get(['status', 'sla_deadline_at', 'resolved_at', 'closed_at', 'team', 'team_id']);

        $total = $tickets->count();

        if ($total === 0) {
            return 0;
        }

        $breached = $tickets->filter(fn (Ticket $ticket) => $this->isBreached($ticket))->count();

        return round(($breached / $total) * 100, 1);
    }

    public function isBreached(Ticket $ticket): bool
    {
        if (! $ticket->sla_deadline_at) {
            return false;
        }

        $deadline = Carbon::parse($ticket->sla_deadline_at);
        $completedAt = $this->dateResolver->completedAt($ticket);

        if ($completedAt) {
            return $completedAt->gt($deadline);
        }

        return in_array($ticket->status, TicketStatus::activeValues(), true)
            && now()->gt($deadline);
    }

    public function snapshot(Ticket $ticket): array
    {
        if (! $ticket->isTeamCode('it')) {
            if (in_array($ticket->status, TicketStatus::completedValues(), true)) {
                return [
                    'sla_time' => 'Direct close',
                    'result' => 'Closed',
                ];
            }

            return [
                'sla_time' => 'No SLA',
                'result' => 'Open',
            ];
        }

        if (! $ticket->sla_deadline_at) {
            return [
                'sla_time' => 'No SLA',
                'result' => 'Open',
            ];
        }

        $deadline = Carbon::parse($ticket->sla_deadline_at);
        $completedAt = $this->dateResolver->completedAt($ticket);
        $isCompleted = in_array($ticket->status, TicketStatus::completedValues(), true) && $completedAt !== null;

        if ($isCompleted) {
            $diffSeconds = $completedAt->diffInSeconds($deadline, false);

            if ($diffSeconds >= 0) {
                return [
                    'sla_time' => 'Met by ' . $this->durationFormatter->human($diffSeconds),
                    'result' => 'OK',
                ];
            }

            return [
                'sla_time' => 'Breached by ' . $this->durationFormatter->human(abs($diffSeconds)),
                'result' => 'Breach',
            ];
        }

        $remainingSeconds = now()->diffInSeconds($deadline, false);

        if ($remainingSeconds >= 0) {
            return [
                'sla_time' => $this->durationFormatter->human($remainingSeconds) . ' left',
                'result' => 'Open',
            ];
        }

        return [
            'sla_time' => 'Overdue ' . $this->durationFormatter->human(abs($remainingSeconds)),
            'result' => 'Breach',
        ];
    }
}
