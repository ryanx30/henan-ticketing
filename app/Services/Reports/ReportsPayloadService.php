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

    public function build(User $user, Carbon $start, Carbon $end, string $requestedScope, string $range, int $perPage, ?int $selectedUserId = null): array
    {
        $scope = $this->normalizeScopeForUser($requestedScope, $user);
        $selectedUserId = $this->normalizeSelectedUserIdForUser($selectedUserId, $user);
        $baseTickets = $this->reportTicketQuery->base($start, $end, $scope, $user, $selectedUserId);
        $roleCards = $this->cardsService->build($baseTickets, $start, $end, $scope, $user, $selectedUserId);
        $trend = $this->trendService->build($start, $end, $scope, $user, $range, $selectedUserId);

        $rowsPaginator = $this->reportTicketQuery->activity($start, $end, $scope, $user, $selectedUserId)
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
                'report_type' => $scope,
                'report_type_label' => $this->reportTypeLabel($scope),
                'range' => [
                    'start' => $start->toDateString(),
                    'end' => $end->toDateString(),
                ],
                'selected_user_id' => $selectedUserId,
                'user_filter' => $this->userFilterMeta($user, $selectedUserId),
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

    public function canUseUserFilter(User $user, ?int $selectedUserId): bool
    {
        if (!$selectedUserId) {
            return true;
        }

        return $this->reportTicketQuery->canUseUserFilter($user, $selectedUserId);
    }

    public function normalizeSelectedUserIdForUser(?int $selectedUserId, User $user): ?int
    {
        if (!$selectedUserId) {
            return null;
        }

        return $this->canUseUserFilter($user, $selectedUserId)
            ? $selectedUserId
            : null;
    }

    private function reportTypeLabel(string $scope): string
    {
        return match ($scope) {
            'it_performance' => 'IT Performance',
            'cs_performance' => 'CS Performance',
            'team' => 'Team Performance',
            'all' => 'All Tickets',
            default => 'My Performance',
        };
    }

    private function userFilterMeta(User $user, ?int $selectedUserId): array
    {
        if ($user->isHeadCS()) {
            return $this->userFilterResponse('CS Staff', 'All CS Staff', $selectedUserId, $user);
        }

        if ($user->isAdmin()) {
            return $this->userFilterResponse('IT Staff', 'All IT Staff', $selectedUserId, $user);
        }

        return [
            'available' => false,
            'label' => 'User',
            'placeholder' => 'All Users',
            'selected_user_id' => null,
            'options' => [],
        ];
    }

    private function userFilterResponse(string $label, string $placeholder, ?int $selectedUserId, User $user): array
    {
        return [
            'available' => true,
            'label' => $label,
            'placeholder' => $placeholder,
            'selected_user_id' => $selectedUserId,
            'options' => $this->reportTicketQuery->userFilterOptions($user),
        ];
    }
}
