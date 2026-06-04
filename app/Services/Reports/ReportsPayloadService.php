<?php

namespace App\Services\Reports;

use App\Models\User;
use App\Queries\ReportTicketQuery;
use Carbon\Carbon;

/**
 * Composes the reports response from range, cards, trend, SLA, and row mapping services.
 */
final class ReportsPayloadService
{
    public function __construct(
        private ReportTicketQuery $reportTicketQuery,
        private ReportCardsService $cardsService,
        private ReportTrendService $trendService,
        private ReportRowMapper $rowMapper,
    ) {
    }

    public function build(User $user, Carbon $start, Carbon $end, string $requestedScope, string $range, int $perPage): array
    {
        $scope = $this->normalizeScopeForUser($requestedScope, $user);
        $baseTickets = $this->reportTicketQuery->base($start, $end, $scope, $user);
        $roleCards = $this->cardsService->build($baseTickets, $start, $end, $scope, $user);
        $trend = $this->trendService->build($start, $end, $scope, $user, $range);

        $rowsPaginator = $this->reportTicketQuery->activity($start, $end, $scope, $user)
            ->latest('updated_at')
            ->paginate($perPage)
            ->withQueryString();

        $rows = $rowsPaginator->getCollection()
            ->map(fn ($ticket) => $this->rowMapper->map($ticket))
            ->values();

        return [
            'cards' => $roleCards['legacy'],
            'card_items' => $roleCards['items'],
            'trend' => $trend,
            'rows' => $rows,
            'pagination' => [
                'current_page' => $rowsPaginator->currentPage(),
                'last_page' => $rowsPaginator->lastPage(),
                'per_page' => $rowsPaginator->perPage(),
                'total' => $rowsPaginator->total(),
                'from' => $rowsPaginator->firstItem(),
                'to' => $rowsPaginator->lastItem(),
            ],
            'meta' => [
                'scope' => $scope,
                'range' => [
                    'start' => $start->toDateString(),
                    'end' => $end->toDateString(),
                ],
                'table_labels' => [
                    'sla_time' => 'SLA Remaining / Outcome',
                    'result' => 'SLA Result',
                ],
            ],
        ];
    }

    public function normalizeScopeForUser(string $scope, User $user): string
    {
        return $this->reportTicketQuery->normalizeScopeForUser($scope, $user);
    }
}
