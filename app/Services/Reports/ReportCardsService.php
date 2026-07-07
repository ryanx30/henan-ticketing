<?php

namespace App\Services\Reports;

use App\Models\Ticket;
use App\Models\User;
use App\Queries\ReportTicketQuery;
use App\Support\TicketStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Calculates report card values from the shared report activity query.
 */
final class ReportCardsService
{
    public function __construct(
        private ReportTicketQuery $reportTicketQuery,
        private ReportSlaService $slaService,
        private ReportTicketDateResolver $dateResolver,
        private ReportDurationFormatter $durationFormatter,
    ) {
    }

    /**
     * Reports use the same real ticket data, but card labels/calculations change by role.
     */
    public function build(Builder $baseTickets, Carbon $start, Carbon $end, string $scope, User $user, ?int $selectedUserId = null): array
    {
        $completedCount = $this->reportTicketQuery->completed($start, $end, $scope, $user, $selectedUserId)->count();
        $slaBreachRate = $this->slaService->breachRate($start, $end, $scope, $user, $selectedUserId);

        if ($user->isIT()) {
            $activeCount = (clone $baseTickets)
                ->whereIn('status', TicketStatus::activeValues())
                ->count();

            $avgResolutionSeconds = $this->averageResolutionSeconds(
                $this->reportTicketQuery->completed($start, $end, $scope, $user, $selectedUserId)
            );

            $activeLabel = $scope === 'team' ? 'Team Active Tickets' : 'My Active Tickets';
            $activeDescription = $scope === 'team'
                ? 'Tickets assigned to the IT team that are still open.'
                : 'Tickets assigned to you that are still open.';

            $items = [
                $this->cardItem('active_tickets', $activeLabel, $activeCount, $activeDescription),
                $this->cardItem('avg_resolution_time', 'Avg Resolution Time', $this->durationFormatter->human($avgResolutionSeconds), 'Average time from claim to resolution.'),
                $this->cardItem('completed_tickets', 'Completed Tickets', $completedCount, 'Resolved or closed tickets in the selected range.'),
                $this->cardItem('sla_breach_rate', 'SLA Breach Rate', $this->durationFormatter->percent($slaBreachRate), 'Percentage of SLA-tracked tickets that breached the deadline.'),
            ];

            return $this->response($items, [
                'resolved' => $completedCount,
                'avg_response_seconds' => 0,
                'avg_response_label' => $this->durationFormatter->human($avgResolutionSeconds),
                'reopen_rate' => 0,
                'sla_risk' => $slaBreachRate,
            ]);
        }

        if ($user->isCsStaffOrHead()) {
            $createdCount = (clone $baseTickets)->count();
            $activeCount = (clone $baseTickets)
                ->whereIn('status', TicketStatus::activeValues())
                ->count();
            $needFollowUpCount = (clone $baseTickets)
                ->where('status', TicketStatus::WAITING_INFO)
                ->count();

            $items = [
                $this->cardItem('created_tickets', 'Created Tickets', $createdCount, 'Tickets created by the selected CS scope.'),
                $this->cardItem('active_tickets', 'Active Tickets', $activeCount, 'New, ongoing, or waiting-info tickets.'),
                $this->cardItem('need_follow_up', 'Need Follow-up', $needFollowUpCount, 'Tickets currently waiting for additional information.'),
                $this->cardItem('completed_tickets', 'Completed Tickets', $completedCount, 'Resolved or closed tickets in the selected range.'),
            ];

            return $this->response($items, [
                'resolved' => $completedCount,
                'avg_response_seconds' => 0,
                'avg_response_label' => '0m',
                'reopen_rate' => 0,
                'sla_risk' => 0,
            ]);
        }

        $totalCount = $this->reportTicketQuery->activity($start, $end, $scope, $user, $selectedUserId)->count();
        $activeCount = (clone $baseTickets)
            ->whereIn('status', TicketStatus::activeValues())
            ->count();

        $items = [
            $this->cardItem('total_tickets', 'Total Tickets', $totalCount, 'Total tickets in the selected scope and range.'),
            $this->cardItem('active_tickets', 'Active Tickets', $activeCount, 'New, ongoing, or waiting-info tickets.'),
            $this->cardItem('completed_tickets', 'Completed Tickets', $completedCount, 'Resolved or closed tickets in the selected range.'),
            $this->cardItem('sla_breach_rate', 'SLA Breach Rate', $this->durationFormatter->percent($slaBreachRate), 'Percentage of SLA-tracked tickets that breached the deadline.'),
        ];

        return $this->response($items, [
            'resolved' => $completedCount,
            'avg_response_seconds' => 0,
            'avg_response_label' => '0m',
            'reopen_rate' => 0,
            'sla_risk' => $slaBreachRate,
        ]);
    }

    private function averageResolutionSeconds(Builder $query): int
    {
        $rows = (clone $query)
            ->whereNotNull('claimed_at')
            ->get(['claimed_at', 'resolved_at', 'closed_at']);

        if ($rows->isEmpty()) {
            return 0;
        }

        $durations = $rows
            ->map(function (Ticket $ticket) {
                $completedAt = $this->dateResolver->completedAt($ticket);

                if (! $ticket->claimed_at || ! $completedAt) {
                    return null;
                }

                return Carbon::parse($ticket->claimed_at)->diffInSeconds($completedAt);
            })
            ->filter(fn ($seconds) => $seconds !== null);

        if ($durations->isEmpty()) {
            return 0;
        }

        return (int) round($durations->avg());
    }

    private function cardItem(string $key, string $label, int|float|string $value, string $description): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => (string) $value,
            'description' => $description,
        ];
    }

    private function response(array $items, array $legacy): array
    {
        return [
            'items' => $items,
            'legacy' => $legacy,
        ];
    }
}
