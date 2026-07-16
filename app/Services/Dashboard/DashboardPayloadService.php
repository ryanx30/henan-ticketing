<?php

namespace App\Services\Dashboard;

use App\Http\Resources\TicketResource;
use App\Models\ResolverMessage;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Builds role-aware dashboard payloads and keeps dashboard controller logic thin.
 */
class DashboardPayloadService
{
    public function make(Request $request): array
    {
        $role = $request->user()->role;

        $currentMonthStart = now()->copy()->startOfMonth();
        $currentMonthEnd   = now()->copy()->endOfMonth();

        $prevMonthStart = now()->copy()->subMonthNoOverflow()->startOfMonth();
        $prevMonthEnd   = now()->copy()->subMonthNoOverflow()->endOfMonth();

        $prevYearMonthStart = now()->copy()->subYear()->startOfMonth();
        $prevYearMonthEnd   = now()->copy()->subYear()->endOfMonth();

        // ===== KPI Aggregate =====
        $now = now();
        $resolvedWindowStart = $now->copy()->subDay();
        $slaRiskWindowEnd = $now->copy()->addMinutes(59);

        $currentKpi = $this->ticketKpiAggregate(
            null,
            null,
            $resolvedWindowStart,
            $now,
            $now,
            $slaRiskWindowEnd
        );

        $previousMonthKpi = $this->ticketKpiAggregate(
            $prevMonthStart,
            $prevMonthEnd,
            $prevMonthStart,
            $prevMonthEnd,
            $prevMonthStart,
            $prevMonthEnd
        );

        $previousYearKpi = $this->ticketKpiAggregate(
            $prevYearMonthStart,
            $prevYearMonthEnd,
            $prevYearMonthStart,
            $prevYearMonthEnd,
            $prevYearMonthStart,
            $prevYearMonthEnd
        );

        $total = $currentKpi['total'];
        $new = $currentKpi['new'];
        $inProgress = $currentKpi['in_progress'];
        $resolved = $currentKpi['resolved'];
        $slaRisk = $currentKpi['sla_risk'];

        $totalPrevMonth = $previousMonthKpi['total'];
        $newPrevMonth = $previousMonthKpi['new'];
        $inProgressPrevMonth = $previousMonthKpi['in_progress'];
        $resolvedPrevMonth = $previousMonthKpi['resolved'];
        $slaRiskPrevMonth = $previousMonthKpi['sla_risk'];

        $totalPrevYear = $previousYearKpi['total'];
        $newPrevYear = $previousYearKpi['new'];
        $inProgressPrevYear = $previousYearKpi['in_progress'];
        $resolvedPrevYear = $previousYearKpi['resolved'];
        $slaRiskPrevYear = $previousYearKpi['sla_risk'];

        // ===== Growth =====
        $totalMoM      = $this->calculateGrowth($total, $totalPrevMonth);
        $newMoM        = $this->calculateGrowth($new, $newPrevMonth);
        $inProgressMoM = $this->calculateGrowth($inProgress, $inProgressPrevMonth);
        $resolvedMoM   = $this->calculateGrowth($resolved, $resolvedPrevMonth);
        $slaRiskMoM    = $this->calculateGrowth($slaRisk, $slaRiskPrevMonth);

        $totalYoY      = $this->calculateGrowth($total, $totalPrevYear);
        $newYoY        = $this->calculateGrowth($new, $newPrevYear);
        $inProgressYoY = $this->calculateGrowth($inProgress, $inProgressPrevYear);
        $resolvedYoY   = $this->calculateGrowth($resolved, $resolvedPrevYear);
        $slaRiskYoY    = $this->calculateGrowth($slaRisk, $slaRiskPrevYear);

        // ===== Today's Focus =====
        $focusAggregate = $this->ticketFocusAggregate($now);
        $focusSla = $slaRisk;
        $focusDueToday = $focusAggregate['due_today'];
        $focusPendingUser = $focusAggregate['pending_user'];

        $focusReopened = TicketStatusHistory::where('to_status', 'in_progress')
            ->whereNotNull('from_status')
            ->where('from_status', 'resolved')
            ->whereBetween('changed_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])
            ->count();

        // ===== Filters =====
        $priority    = $request->query('priority', 'all');
        $status      = $this->normalizeDashboardStatusFilter($request->query('status', 'all'));
        $sla         = $request->query('sla', 'all');
        $sort        = $request->query('sort', 'latest');
        $inboxPeriod = $request->query('inbox_period', 'all');

        $inboxStart = match ($inboxPeriod) {
            'today' => now()->copy()->startOfDay(),
            '7d'    => now()->copy()->subDays(7),
            '30d'   => now()->copy()->subDays(30),
            'all'   => null,
            default => null,
        };

        // ===== My Tickets for CS =====
        $myTickets = $request->user()->isCS()
            ? Ticket::query()
                ->with(['creator', 'holder', 'teamMaster', 'priorityMaster'])
                ->where('created_by', $request->user()->id)
                ->whereIn('status', ['new', 'in_progress'])
                ->latest()
                ->take(10)
                ->get()
            : collect();

        // ===== Active Tickets for CS monitoring =====
        // CS can monitor all currently active tickets created by any CS/Admin.
        $activeQ = Ticket::query()
            ->with(['creator', 'holder', 'teamMaster', 'priorityMaster'])
            ->whereIn('status', ['new', 'in_progress', 'waiting_info']);

        $this->scopeActiveTicketsForDashboard($activeQ, $request->user());

        if ($priority !== 'all') {
            $activeQ->where('priority', $priority);
        }

        if ($status !== null) {
            $activeQ->where('status', $status);
        }

        if ($sla !== 'all') {
            if ($sla === 'lt_1h') {
                $activeQ->whereNotNull('sla_deadline_at')
                    ->whereBetween('sla_deadline_at', [now(), now()->copy()->addHour()]);
            } elseif ($sla === '1h_4h') {
                $activeQ->whereNotNull('sla_deadline_at')
                    ->whereBetween('sla_deadline_at', [now()->copy()->addHour(), now()->copy()->addHours(4)]);
            } elseif ($sla === 'gt_4h') {
                $activeQ->whereNotNull('sla_deadline_at')
                    ->where('sla_deadline_at', '>', now()->copy()->addHours(4));
            }
        }

        $activeQ->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc');
        $activeTickets = $activeQ->take(10)->get();

        // ===== Resolver Inbox for CS =====
        $resolverInboxQ = ResolverMessage::query()
            ->with(['ticket', 'sender', 'recipient'])
            ->latest();

        $this->scopeResolverInboxForDashboard($resolverInboxQ, $request->user());

        if ($inboxStart) {
            $resolverInboxQ->where('created_at', '>=', $inboxStart);
        }

        $resolverInbox = $resolverInboxQ->take(30)->get();

        // ===== IT Dashboard data =====
        $itMyQueue = $request->user()->isSupervisor()
            ? collect()
            : Ticket::query()
                ->forTeamCode('it')
                ->where('holder_id', $request->user()->id)
                ->whereIn('status', ['new', 'in_progress', 'waiting_info'])
                ->latest()
                ->take(10)
                ->get();

        $itTeamNew = Ticket::query()
            ->forTeamCode('it')
            ->where('status', 'new')
            ->latest()
            ->take(10)
            ->get();

        // ===== Trend Chart (7 hari terakhir) =====
        $trendLabels     = [];
        $trendIT         = [];
        $trendFinance    = [];
        $trendCompliance = [];

        $start = now()->copy()->subDays(6)->startOfDay();
        $end   = now()->copy()->endOfDay();

        $rows = Ticket::query()
            ->leftJoin('teams', 'teams.id', '=', 'tickets.team_id')
            ->whereBetween('tickets.created_at', [$start, $end])
            ->selectRaw("
                DATE(tickets.created_at) as d,
                LOWER(COALESCE(teams.code, NULLIF(tickets.team, ''), '-')) as team_code,
                COUNT(*) as c
            ")
            ->groupByRaw("DATE(tickets.created_at), LOWER(COALESCE(teams.code, NULLIF(tickets.team, ''), '-'))")
            ->get();

        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $day = (clone $start)->addDays($i);
            $days[] = $day->format('Y-m-d');
            $trendLabels[] = $day->format('d M');
        }

        $map = [
            'it'         => array_fill(0, 7, 0),
            'finance'    => array_fill(0, 7, 0),
            'compliance' => array_fill(0, 7, 0),
        ];

        foreach ($rows as $r) {
            $idx = array_search($r->d, $days, true);
            if ($idx !== false && isset($map[$r->team_code])) {
                $map[$r->team_code][$idx] = (int) $r->c;
            }
        }

        $trendIT         = $map['it'];
        $trendFinance    = $map['finance'];
        $trendCompliance = $map['compliance'];

        // ===== Top Cases =====
        $topCases = $this->topCases();

        return [
            'role' => $role,
            'kpi'  => [
                'total' => [
                    'value'      => $total,
                    'prev_month' => $totalPrevMonth,
                    'prev_year'  => $totalPrevYear,
                    'mom'        => $totalMoM,
                    'yoy'        => $totalYoY,
                ],
                'new' => [
                    'value'      => $new,
                    'prev_month' => $newPrevMonth,
                    'prev_year'  => $newPrevYear,
                    'mom'        => $newMoM,
                    'yoy'        => $newYoY,
                ],
                'in_progress' => [
                    'value'      => $inProgress,
                    'prev_month' => $inProgressPrevMonth,
                    'prev_year'  => $inProgressPrevYear,
                    'mom'        => $inProgressMoM,
                    'yoy'        => $inProgressYoY,
                ],
                'resolved' => [
                    'value'      => $resolved,
                    'prev_month' => $resolvedPrevMonth,
                    'prev_year'  => $resolvedPrevYear,
                    'mom'        => $resolvedMoM,
                    'yoy'        => $resolvedYoY,
                ],
                'sla_risk' => [
                    'value'      => $slaRisk,
                    'prev_month' => $slaRiskPrevMonth,
                    'prev_year'  => $slaRiskPrevYear,
                    'mom'        => $slaRiskMoM,
                    'yoy'        => $slaRiskYoY,
                ],
            ],
            'focus' => [
                'sla'          => $focusSla,
                'due_today'    => $focusDueToday,
                'pending_user' => $focusPendingUser,
                'reopened'     => $focusReopened,
            ],
            'my_tickets'     => TicketResource::collection($myTickets),
            'active_tickets' => TicketResource::collection($activeTickets),
            'resolver_inbox' => $resolverInbox,
            'it_my_queue'    => TicketResource::collection($itMyQueue),
            'it_team_new'    => TicketResource::collection($itTeamNew),
            'trend'          => [
                'labels'     => $trendLabels,
                'it'         => $trendIT,
                'finance'    => $trendFinance,
                'compliance' => $trendCompliance,
            ],
            'top_cases' => $topCases,
        ];
    }

    /**
     * Calculates dashboard growth without presenting an undefined percentage when the baseline is zero.
     */
    private function calculateGrowth(int $current, int $previous): array
    {
        if ($previous === 0) {
            if ($current === 0) {
                return [
                    'value'     => 0,
                    'label'     => '0%',
                    'direction' => 'flat',
                ];
            }

            return [
                'value'     => null,
                'label'     => 'New',
                'direction' => 'new',
            ];
        }

        $rounded = round((($current - $previous) / $previous) * 100, 1);

        return [
            'value'     => $rounded,
            'label'     => ($rounded > 0 ? '+' : '') . $rounded . '%',
            'direction' => $rounded > 0 ? 'up' : ($rounded < 0 ? 'down' : 'flat'),
        ];
    }

    private function scopeActiveTicketsForDashboard(Builder $query, User $user): void
    {
        if ($user->isAdmin() || $user->isSupervisor() || $user->isHeadCS() || $user->isCS()) {
            return;
        }

        if ($user->isIT()) {
            $query->forTeamCode('it')
                ->where(function (Builder $query) use ($user) {
                    $query->whereNull('holder_id')
                        ->orWhere('holder_id', $user->id);
                });
        }
    }

    private function scopeResolverInboxForDashboard(Builder $query, User $user): void
    {
        if ($user->isAdmin() || $user->isSupervisor() || $user->isHeadCS()) {
            return;
        }

        $query->where('to_user_id', $user->id);
    }

    private function normalizeDashboardStatusFilter(?string $status): ?string
    {
        if ($status === null || $status === '' || strtolower(trim($status)) === 'all') {
            return null;
        }

        $normalized = strtolower(trim($status));
        $normalized = str_replace(['-', ' '], '_', $normalized);

        return match ($normalized) {
            'new' => 'new',
            'ongoing', 'on_going', 'in_progress' => 'in_progress',
            'waiting', 'waiting_info', 'waiting_for_info' => 'waiting_info',
            'resolved' => 'resolved',
            'closed' => 'closed',
            default => null,
        };
    }

    private function ticketKpiAggregate(
        mixed $createdFrom = null,
        mixed $createdTo = null,
        mixed $resolvedFrom = null,
        mixed $resolvedTo = null,
        mixed $slaFrom = null,
        mixed $slaTo = null
    ): array {
        [$createdCondition, $createdBindings] = $this->dateRangeCondition('tickets.created_at', $createdFrom, $createdTo);
        [$resolvedCondition, $resolvedBindings] = $this->dateRangeCondition('tickets.resolved_at', $resolvedFrom, $resolvedTo);
        [$slaCondition, $slaBindings] = $this->dateRangeCondition('tickets.sla_deadline_at', $slaFrom, $slaTo);

        $summary = Ticket::query()
            ->selectRaw("
                SUM(CASE WHEN 1 = 1{$createdCondition} THEN 1 ELSE 0 END) as total,
                SUM(CASE WHEN tickets.status = 'new'{$createdCondition} THEN 1 ELSE 0 END) as new_count,
                SUM(CASE WHEN tickets.status = 'in_progress'{$createdCondition} THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN tickets.resolved_at IS NOT NULL{$resolvedCondition} THEN 1 ELSE 0 END) as resolved_count,
                SUM(CASE
                    WHEN tickets.status IN ('new', 'in_progress', 'waiting_info')
                    AND tickets.sla_deadline_at IS NOT NULL{$slaCondition}
                    THEN 1 ELSE 0
                END) as sla_risk_count
            ", [
                ...$createdBindings,
                ...$createdBindings,
                ...$createdBindings,
                ...$resolvedBindings,
                ...$slaBindings,
            ])
            ->first();

        return [
            'total'       => (int) ($summary?->total ?? 0),
            'new'         => (int) ($summary?->new_count ?? 0),
            'in_progress' => (int) ($summary?->in_progress_count ?? 0),
            'resolved'    => (int) ($summary?->resolved_count ?? 0),
            'sla_risk'    => (int) ($summary?->sla_risk_count ?? 0),
        ];
    }

    private function dateRangeCondition(string $column, mixed $from = null, mixed $to = null): array
    {
        if (! $from || ! $to) {
            return ['', []];
        }

        return [" AND {$column} BETWEEN ? AND ?", [$from, $to]];
    }

    private function ticketFocusAggregate(mixed $now): array
    {
        $dueToday = Ticket::query()
            ->whereIn('status', ['new', 'in_progress', 'waiting_info'])
            ->whereBetween('sla_deadline_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])
            ->count();

        $pendingUser = Ticket::query()
            ->where('status', 'waiting_info')
            ->count();

        return [
            'due_today'    => $dueToday,
            'pending_user' => $pendingUser,
        ];
    }

    private function topCases(): array
    {
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $issueKeyExpression = "
                CASE
                    WHEN tickets.issue_type_id IS NOT NULL THEN CONCAT('id:', tickets.issue_type_id)
                    ELSE CONCAT('snapshot:', COALESCE(NULLIF(tickets.issue_type, ''), 'unknown'))
                END
            ";
        } else {
            $issueKeyExpression = "
                CASE
                    WHEN tickets.issue_type_id IS NOT NULL THEN 'id:' || CAST(tickets.issue_type_id AS TEXT)
                    ELSE 'snapshot:' || COALESCE(NULLIF(tickets.issue_type, ''), 'unknown')
                END
            ";
        }

        $issueLabelExpression = "COALESCE(issue_types.name, NULLIF(tickets.issue_type, ''), 'Unknown Issue')";
        $teamCodeExpression = "LOWER(COALESCE(teams.code, NULLIF(tickets.team, ''), '-'))";

        $rows = Ticket::query()
            ->leftJoin('issue_types', 'issue_types.id', '=', 'tickets.issue_type_id')
            ->leftJoin('teams', 'teams.id', '=', 'tickets.team_id')
            ->where(function ($query) {
                $query->whereNotNull('tickets.issue_type_id')
                    ->orWhere(function ($query) {
                        $query->whereNotNull('tickets.issue_type')
                            ->where('tickets.issue_type', '<>', '');
                    });
            })
            ->selectRaw("{$issueKeyExpression} as issue_key")
            ->selectRaw("{$issueLabelExpression} as issue_type")
            ->selectRaw("{$teamCodeExpression} as team_code")
            ->selectRaw('COUNT(*) as total')
            ->groupByRaw("{$issueKeyExpression}, {$issueLabelExpression}, {$teamCodeExpression}")
            ->get();

        return $rows
            ->groupBy('issue_key')
            ->map(function ($group) {
                $teamCounts = $group
                    ->groupBy('team_code')
                    ->map(fn ($teamRows) => $teamRows->sum(fn ($row) => (int) $row->total));

                return [
                    'issue_type' => (string) ($group->first()?->issue_type ?: 'Unknown Issue'),
                    'count'      => (int) $group->sum(fn ($row) => (int) $row->total),
                    'top_team'   => (string) ($teamCounts->sortDesc()->keys()->first() ?: '-'),
                ];
            })
            ->sortByDesc('count')
            ->take(7)
            ->values()
            ->all();
    }
}