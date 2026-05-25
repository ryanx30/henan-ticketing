<?php

namespace App\Http\Controllers\Api;

use App\Models\Ticket;
use App\Jobs\ExportDataJob;
use App\Models\TicketStatusHistory;
use App\Models\User;
use App\Queries\ReportTicketQuery;
use App\Support\TicketStatus;
use App\Services\AggregatedStatsReader;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

class ReportsApiController extends BaseApiController
{
    public function __construct(
        private AggregatedStatsReader $statsReader,
        private ReportTicketQuery $reportTicketQuery
    ) {
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Ticket::class);

        [$start, $end] = $this->resolveRange($request);

        $scope = (string) $request->query('scope', 'my');
        if (!in_array($scope, ['my', 'team', 'all'], true)) {
            $scope = 'my';
        }

        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        $user = $request->user();
        $scope = $this->normalizeScopeForUser($scope, $user);

        $baseTickets = $this->reportTicketQuery->base($start, $end, $scope, $user);

        // ===== Role-based KPI cards =====
        // Reports use the same real ticket data, but card labels and calculations change by role.
        $roleCards = $this->buildRoleBasedCards($baseTickets, $start, $end, $scope, $user);

        // ===== Trend Chart =====

        $range       = (string) $request->query('range', 'this_week');
        $trendLabels = [];
        $trendValues = [];
        $trendSource = 'live';

        $rangeDays = $start->diffInDays($end);
        $isOneYear = ($range === 'one_year');

        $canUseGlobalAggregate = $scope === 'all'
            && ($user->isAdmin() || $user->isSupervisor())
            && $this->statsReader->canUsePreAggregated($start, $end);

        if ($canUseGlobalAggregate) {
            // Pre-aggregated stats are only safe for global "all" reports.
            $groupBy  = $isOneYear ? 'month' : 'day';
            $trendData = $this->statsReader->trend($start, $end, $groupBy);

            foreach ($trendData as $row) {
                if ($isOneYear) {
                    // Format label bulan: "2026-04" → "Apr 2026"
                    try {
                        $trendLabels[] = Carbon::createFromFormat('Y-m', $row['label'])->format('M Y');
                    } catch (\Throwable) {
                        $trendLabels[] = $row['label'];
                    }
                } else {
                    // Format label hari: "2026-04-15" → "15 Apr"
                    try {
                        $trendLabels[] = Carbon::createFromFormat('Y-m-d', $row['label'])->format('d M');
                    } catch (\Throwable) {
                        $trendLabels[] = $row['label'];
                    }
                }

                $trendValues[] = $row['resolved'] + $row['closed'];
            }

            $trendSource = 'pre_aggregated';
        } elseif ($isOneYear) {
            // Live query: one_year → group per bulan
            $months = $this->makeMonthRange($start, $end);

            $trendRows = (clone $baseTickets)
                ->whereIn('status', TicketStatus::completedValues())
                ->where(function ($q) use ($start, $end) {
                    $q->where(fn ($qq) => $qq->whereNotNull('resolved_at')->whereBetween('resolved_at', [$start, $end]))
                        ->orWhere(fn ($qq) => $qq->whereNull('resolved_at')->whereNotNull('closed_at')->whereBetween('closed_at', [$start, $end]));
                })
                ->get(['id', 'resolved_at', 'closed_at'])
                ->groupBy(fn (Ticket $ticket) => Carbon::parse($ticket->resolved_at ?: $ticket->closed_at)->format('Y-m'));

            foreach ($months as $month) {
                $key           = $month->format('Y-m');
                $trendLabels[] = $month->format('M Y');
                $trendValues[] = $trendRows->get($key, collect())->count();
            }
        } else {
            // Live query: range pendek → group per hari
            $trendDays = $this->makeDayRange($start, $end);

            $trendRows = (clone $baseTickets)
                ->whereIn('status', TicketStatus::completedValues())
                ->where(function ($q) use ($start, $end) {
                    $q->where(fn ($qq) => $qq->whereNotNull('resolved_at')->whereBetween('resolved_at', [$start, $end]))
                        ->orWhere(fn ($qq) => $qq->whereNull('resolved_at')->whereNotNull('closed_at')->whereBetween('closed_at', [$start, $end]));
                })
                ->get(['id', 'resolved_at', 'closed_at'])
                ->groupBy(fn (Ticket $ticket) => Carbon::parse($ticket->resolved_at ?: $ticket->closed_at)->format('Y-m-d'));

            foreach ($trendDays as $day) {
                $key           = $day->format('Y-m-d');
                $trendLabels[] = $day->format('d M');
                $trendValues[] = $trendRows->get($key, collect())->count();
            }
        }

        // ===== Table Rows =====
        $rowsQuery = (clone $baseTickets)
            ->whereBetween('created_at', [$start, $end])
            ->latest();

        $rowsPaginator = $rowsQuery
            ->paginate($perPage)
            ->withQueryString();

        $rows = $rowsPaginator->getCollection()
            ->map(fn($ticket) => $this->mapTicketRow($ticket))
            ->values();

        return $this->success([
            'cards' => $roleCards['legacy'],
            'card_items' => $roleCards['items'],
            'trend' => [
                'labels' => $trendLabels,
                'values' => $trendValues,
                'source' => $trendSource,
            ],
            'rows'       => $rows,
            'pagination' => [
                'current_page' => $rowsPaginator->currentPage(),
                'last_page'    => $rowsPaginator->lastPage(),
                'per_page'     => $rowsPaginator->perPage(),
                'total'        => $rowsPaginator->total(),
                'from'         => $rowsPaginator->firstItem(),
                'to'           => $rowsPaginator->lastItem(),
            ],
            'meta' => [
                'scope' => $scope,
                'range' => [
                    'start' => $start->toDateString(),
                    'end'   => $end->toDateString(),
                ],
                'table_labels' => [
                    'sla_time' => 'SLA Remaining / Outcome',
                    'result'   => 'SLA Result',
                ],
            ],
        ], 'Reports loaded');
    }

    public function export(Request $request)
    {
        Gate::authorize('viewAny', Ticket::class);

        [$start, $end] = $this->resolveRange($request);

        $scope = (string) $request->query('scope', 'my');
        if (!in_array($scope, ['my', 'team', 'all'], true)) {
            $scope = 'my';
        }

        $user = $request->user();
        $scope = $this->normalizeScopeForUser($scope, $user);

        $filename = 'reports-' . now()->format('Ymd-His') . '-' . Str::lower(Str::random(6)) . '.csv';

        $batch = Bus::batch([
            new ExportDataJob('reports_csv', $user->id, [
                'scope' => $scope,
                'date_from' => $start->toDateTimeString(),
                'date_to' => $end->toDateTimeString(),
            ], $filename),
        ])->name('reports-export-' . $filename)->dispatch();

        return $this->success([
            'queued' => true,
            'batch_id' => $batch->id,
            'filename' => $filename,
            'storage_disk' => 'local',
            'storage_path' => 'exports/reports/' . $filename,
        ], 'Report export has been queued.', 202);
    }

    /**
     * Build role-based report cards from real ticket records.
     * IT focuses on assigned workload and resolver performance.
     * CS focuses on created tickets and follow-up needs.
     * Admin/Supervisor focuses on organization-wide volume and SLA health.
     */
    protected function buildRoleBasedCards(Builder $baseTickets, Carbon $start, Carbon $end, string $scope, User $user): array
    {
        $completedCount = $this->completedTicketsQuery($start, $end, $scope, $user)->count();
        $slaBreachRate = $this->calculateSlaBreachRate($baseTickets);

        if ($user->isIT()) {
            $activeCount = (clone $baseTickets)
                ->whereIn('status', TicketStatus::activeValues())
                ->count();

            $avgResolutionSeconds = $this->calculateAverageResolutionSeconds(
                $this->completedTicketsQuery($start, $end, $scope, $user)
            );

            $items = [
                $this->makeCardItem('my_active_tickets', 'My Active Tickets', $activeCount, 'Tickets assigned to you that are still open.'),
                $this->makeCardItem('avg_resolution_time', 'Avg Resolution Time', $this->formatHumanDuration($avgResolutionSeconds), 'Average time from claim to resolution.'),
                $this->makeCardItem('completed_tickets', 'Completed Tickets', $completedCount, 'Resolved or closed tickets in the selected range.'),
                $this->makeCardItem('sla_breach_rate', 'SLA Breach Rate', $this->formatPercent($slaBreachRate), 'Percentage of SLA-tracked tickets that breached the deadline.'),
            ];

            return [
                'items' => $items,
                'legacy' => [
                    'resolved' => $completedCount,
                    'avg_response_seconds' => 0,
                    'avg_response_label' => $this->formatHumanDuration($avgResolutionSeconds),
                    'reopen_rate' => 0,
                    'sla_risk' => $slaBreachRate,
                ],
            ];
        }

        if ($user->isCS()) {
            $createdCount = (clone $baseTickets)->count();
            $activeCount = (clone $baseTickets)
                ->whereIn('status', TicketStatus::activeValues())
                ->count();
            $needFollowUpCount = (clone $baseTickets)
                ->where('status', TicketStatus::WAITING_INFO)
                ->count();

            $items = [
                $this->makeCardItem('created_tickets', 'Created Tickets', $createdCount, 'Tickets created by the selected CS scope.'),
                $this->makeCardItem('active_tickets', 'Active Tickets', $activeCount, 'New, ongoing, or waiting-info tickets.'),
                $this->makeCardItem('need_follow_up', 'Need Follow-up', $needFollowUpCount, 'Tickets currently waiting for additional information.'),
                $this->makeCardItem('completed_tickets', 'Completed Tickets', $completedCount, 'Resolved or closed tickets in the selected range.'),
            ];

            return [
                'items' => $items,
                'legacy' => [
                    'resolved' => $completedCount,
                    'avg_response_seconds' => 0,
                    'avg_response_label' => '0m',
                    'reopen_rate' => 0,
                    'sla_risk' => 0,
                ],
            ];
        }

        $totalCount = (clone $baseTickets)->count();
        $activeCount = (clone $baseTickets)
            ->whereIn('status', TicketStatus::activeValues())
            ->count();

        $items = [
            $this->makeCardItem('total_tickets', 'Total Tickets', $totalCount, 'Total tickets in the selected scope and range.'),
            $this->makeCardItem('active_tickets', 'Active Tickets', $activeCount, 'New, ongoing, or waiting-info tickets.'),
            $this->makeCardItem('completed_tickets', 'Completed Tickets', $completedCount, 'Resolved or closed tickets in the selected range.'),
            $this->makeCardItem('sla_breach_rate', 'SLA Breach Rate', $this->formatPercent($slaBreachRate), 'Percentage of SLA-tracked tickets that breached the deadline.'),
        ];

        return [
            'items' => $items,
            'legacy' => [
                'resolved' => $completedCount,
                'avg_response_seconds' => 0,
                'avg_response_label' => '0m',
                'reopen_rate' => 0,
                'sla_risk' => $slaBreachRate,
            ],
        ];
    }

    protected function makeCardItem(string $key, string $label, int|float|string $value, string $description): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => (string) $value,
            'description' => $description,
        ];
    }

    /**
     * Completed cards are based on the actual completion timestamp, not only ticket creation date.
     */
    protected function completedTicketsQuery(Carbon $start, Carbon $end, string $scope, User $user): Builder
    {
        return $this->reportTicketQuery->completed($start, $end, $scope, $user);
    }

    /**
     * Resolver time uses claimed_at -> resolved_at/closed_at so it measures real handling time.
     */
    protected function calculateAverageResolutionSeconds(Builder $query): int
    {
        $rows = (clone $query)
            ->whereNotNull('claimed_at')
            ->get(['claimed_at', 'resolved_at', 'closed_at']);

        if ($rows->isEmpty()) {
            return 0;
        }

        $durations = $rows
            ->map(function (Ticket $ticket) {
                $completedAt = $this->resolveCompletedAt($ticket);

                if (!$ticket->claimed_at || !$completedAt) {
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

    /**
     * SLA breach rate only counts SLA-tracked IT tickets because SLA deadlines are assigned to resolver work.
     */
    protected function calculateSlaBreachRate(Builder $baseTickets): float
    {
        $tickets = (clone $baseTickets)
            ->forTeamCode('it')
            ->whereNotNull('sla_deadline_at')
            ->get(['status', 'sla_deadline_at', 'resolved_at', 'closed_at', 'team', 'team_id']);

        $total = $tickets->count();

        if ($total === 0) {
            return 0;
        }

        $breached = $tickets->filter(function (Ticket $ticket) {
            $deadline = Carbon::parse($ticket->sla_deadline_at);
            $completedAt = $this->resolveCompletedAt($ticket);

            if ($completedAt) {
                return $completedAt->gt($deadline);
            }

            return in_array($ticket->status, TicketStatus::activeValues(), true)
                && now()->gt($deadline);
        })->count();

        return round(($breached / $total) * 100, 1);
    }

    protected function formatPercent(float|int $value): string
    {
        $rounded = round((float) $value, 1);

        return (floor($rounded) == $rounded ? (string) (int) $rounded : (string) $rounded) . '%';
    }

    protected function mapTicketRow(Ticket $ticket): array
    {
        $responseSeconds = null;

        if ($ticket->isTeamCode('it') && $ticket->claimed_at) {
            $responseSeconds = Carbon::parse($ticket->created_at)
                ->diffInSeconds(Carbon::parse($ticket->claimed_at));
        }

        $slaSnapshot = $this->buildSlaSnapshot($ticket);

        return [
            'id'            => $ticket->id,
            'ticket_code'   => $ticket->ticket_code ? 'T-' . $ticket->ticket_code : 'T-' . $ticket->id,
            'status'        => $ticket->status,
            'team'          => strtoupper($ticket->displayTeamCode()),
            'sla_time'      => $slaSnapshot['sla_time'],
            'response_time' => $ticket->isTeamCode('it')
                ? $this->formatHumanDuration($responseSeconds)
                : 'N/A',
            'result'        => $slaSnapshot['result'],
        ];
    }

    protected function resolveRange(Request $request): array
    {
        $range      = (string) $request->query('range', 'this_week');
        $customFrom = (string) $request->query('date_from', '');
        $customTo   = (string) $request->query('date_to', '');

        return match ($range) {
            '7d'         => [now()->copy()->subDays(6)->startOfDay(), now()->copy()->endOfDay()],
            '30d'        => [now()->copy()->subDays(29)->startOfDay(), now()->copy()->endOfDay()],
            'this_month' => [now()->copy()->startOfMonth(), now()->copy()->endOfDay()],
            'one_year'   => [now()->copy()->subMonths(11)->startOfMonth(), now()->copy()->endOfDay()],
            'custom'     => [
                $customFrom !== '' ? Carbon::parse($customFrom)->startOfDay() : now()->copy()->startOfWeek(),
                $customTo !== '' ? Carbon::parse($customTo)->endOfDay() : now()->copy()->endOfDay(),
            ],
            default => [now()->copy()->startOfWeek(), now()->copy()->endOfDay()],
        };
    }


    /**
     * Downgrade unsupported scopes so non-elevated users cannot request organization-wide data.
     */
    protected function normalizeScopeForUser(string $scope, User $user): string
    {
        return $this->reportTicketQuery->normalizeScopeForUser($scope, $user);
    }

    protected function applyScope(Builder $query, string $scope, User $user): void
    {
        $this->reportTicketQuery->applyScope($query, $scope, $user);
    }

    protected function makeDayRange(Carbon $start, Carbon $end): Collection
    {
        $days   = collect();
        $cursor = $start->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $days->push($cursor->copy());
            $cursor->addDay();
        }

        return $days;
    }

    protected function makeMonthRange(Carbon $start, Carbon $end): Collection
    {
        $months = collect();
        $cursor = $start->copy()->startOfMonth();

        while ($cursor->lte($end)) {
            $months->push($cursor->copy());
            $cursor->addMonth();
        }

        return $months;
    }

    protected function buildSlaSnapshot(Ticket $ticket): array
    {
        if (!$ticket->isTeamCode('it')) {
            if (in_array($ticket->status, TicketStatus::completedValues(), true)) {
                return [
                    'sla_time' => 'Direct close',
                    'result'   => 'Closed',
                ];
            }

            return [
                'sla_time' => 'No SLA',
                'result'   => 'Open',
            ];
        }

        if (!$ticket->sla_deadline_at) {
            return [
                'sla_time' => 'No SLA',
                'result'   => 'Open',
            ];
        }

        $deadline    = Carbon::parse($ticket->sla_deadline_at);
        $completedAt = $this->resolveCompletedAt($ticket);
        $isCompleted = in_array($ticket->status, TicketStatus::completedValues(), true) && $completedAt !== null;

        if ($isCompleted) {
            $diffSeconds = $completedAt->diffInSeconds($deadline, false);

            if ($diffSeconds >= 0) {
                return [
                    'sla_time' => 'Met by ' . $this->formatHumanDuration($diffSeconds),
                    'result'   => 'OK',
                ];
            }

            return [
                'sla_time' => 'Breached by ' . $this->formatHumanDuration(abs($diffSeconds)),
                'result'   => 'Breach',
            ];
        }

        $remainingSeconds = now()->diffInSeconds($deadline, false);

        if ($remainingSeconds >= 0) {
            return [
                'sla_time' => $this->formatHumanDuration($remainingSeconds) . ' left',
                'result'   => 'Open',
            ];
        }

        return [
            'sla_time' => 'Overdue ' . $this->formatHumanDuration(abs($remainingSeconds)),
            'result'   => 'Breach',
        ];
    }

    protected function resolveCompletedAt(Ticket $ticket): ?Carbon
    {
        if ($ticket->resolved_at) {
            return Carbon::parse($ticket->resolved_at);
        }

        if ($ticket->closed_at) {
            return Carbon::parse($ticket->closed_at);
        }

        return null;
    }

    protected function formatAvgResponse(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0m';
        }

        $minutes = (int) round($seconds / 60);

        if ($minutes < 60) {
            return $minutes . 'm';
        }

        $days              = intdiv($minutes, 1440);
        $hours             = intdiv($minutes % 1440, 60);
        $remainingMinutes  = $minutes % 60;

        if ($days > 0) {
            return $hours > 0
                ? $days . 'd ' . $hours . 'h'
                : $days . 'd';
        }

        return $remainingMinutes > 0
            ? intdiv($minutes, 60) . 'h ' . $remainingMinutes . 'm'
            : intdiv($minutes, 60) . 'h';
    }

    protected function formatHumanDuration(?int $seconds): string
    {
        if ($seconds === null || $seconds <= 0) {
            return '0m';
        }

        $minutes = (int) floor($seconds / 60);

        if ($minutes <= 0) {
            return '0m';
        }

        $days             = intdiv($minutes, 1440);
        $hours            = intdiv($minutes % 1440, 60);
        $remainingMinutes = $minutes % 60;

        if ($days > 0) {
            return $hours > 0
                ? $days . 'd ' . $hours . 'h'
                : $days . 'd';
        }

        if ($hours > 0) {
            return $remainingMinutes > 0
                ? $hours . 'h ' . $remainingMinutes . 'm'
                : $hours . 'h';
        }

        return $remainingMinutes . 'm';
    }
}
