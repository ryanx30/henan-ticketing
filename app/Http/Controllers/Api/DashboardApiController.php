<?php

namespace App\Http\Controllers\Api;

use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\ResolverMessage;
use Illuminate\Http\Request;

class DashboardApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $role = $request->user()->role;

        $currentMonthStart = now()->copy()->startOfMonth();
        $currentMonthEnd   = now()->copy()->endOfMonth();

        $prevMonthStart = now()->copy()->subMonthNoOverflow()->startOfMonth();
        $prevMonthEnd   = now()->copy()->subMonthNoOverflow()->endOfMonth();

        $prevYearMonthStart = now()->copy()->subYear()->startOfMonth();
        $prevYearMonthEnd   = now()->copy()->subYear()->endOfMonth();

        $calcGrowth = function ($current, $previous) {
            if ((int) $previous === 0) {
                if ((int) $current === 0) {
                    return [
                        'value' => 0,
                        'label' => '0%',
                        'direction' => 'flat',
                    ];
                }

                return [
                    'value' => 100,
                    'label' => '+100%',
                    'direction' => 'up',
                ];
            }

            $diff = (($current - $previous) / $previous) * 100;
            $rounded = round($diff, 1);

            return [
                'value' => $rounded,
                'label' => ($rounded > 0 ? '+' : '') . $rounded . '%',
                'direction' => $rounded > 0 ? 'up' : ($rounded < 0 ? 'down' : 'flat'),
            ];
        };

        // ===== KPI Current =====
        $total = Ticket::count();

        $new = Ticket::where('status', 'new')->count();

        $inProgress = Ticket::where('status', 'in_progress')->count();

        $resolved = Ticket::whereNotNull('resolved_at')
            ->whereBetween('resolved_at', [now()->copy()->subDay(), now()])
            ->count();

        $slaRisk = Ticket::whereIn('status', ['new', 'in_progress', 'waiting_info'])
            ->whereNotNull('sla_deadline_at')
            ->whereBetween('sla_deadline_at', [now(), now()->copy()->addMinutes(59)])
            ->count();

        // ===== KPI Previous Month =====
        $totalPrevMonth = Ticket::whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])->count();

        $newPrevMonth = TicketStatusHistory::where('to_status', 'new')
            ->whereBetween('changed_at', [$prevMonthStart, $prevMonthEnd])
            ->count();

        $inProgressPrevMonth = TicketStatusHistory::where('to_status', 'in_progress')
            ->whereBetween('changed_at', [$prevMonthStart, $prevMonthEnd])
            ->count();

        $resolvedPrevMonth = Ticket::whereNotNull('resolved_at')
            ->whereBetween('resolved_at', [$prevMonthStart, $prevMonthEnd])
            ->count();

        $slaRiskPrevMonth = Ticket::whereNotNull('sla_deadline_at')
            ->whereBetween('sla_deadline_at', [$prevMonthStart, $prevMonthEnd])
            ->count();

        // ===== KPI Previous Year =====
        $totalPrevYear = Ticket::whereBetween('created_at', [$prevYearMonthStart, $prevYearMonthEnd])->count();

        $newPrevYear = TicketStatusHistory::where('to_status', 'new')
            ->whereBetween('changed_at', [$prevYearMonthStart, $prevYearMonthEnd])
            ->count();

        $inProgressPrevYear = TicketStatusHistory::where('to_status', 'in_progress')
            ->whereBetween('changed_at', [$prevYearMonthStart, $prevYearMonthEnd])
            ->count();

        $resolvedPrevYear = Ticket::whereNotNull('resolved_at')
            ->whereBetween('resolved_at', [$prevYearMonthStart, $prevYearMonthEnd])
            ->count();

        $slaRiskPrevYear = Ticket::whereNotNull('sla_deadline_at')
            ->whereBetween('sla_deadline_at', [$prevYearMonthStart, $prevYearMonthEnd])
            ->count();

        // ===== SLA Risk =====
        $slaRisk = Ticket::whereIn('status', ['new', 'in_progress', 'waiting_info'])
            ->whereNotNull('sla_deadline_at')
            ->whereBetween('sla_deadline_at', [now(), now()->copy()->addMinutes(59)])
            ->count();

        $slaRiskPrevMonth = Ticket::whereIn('status', ['new', 'in_progress', 'waiting_info'])
            ->whereNotNull('sla_deadline_at')
            ->whereBetween('sla_deadline_at', [$prevMonthStart, $prevMonthEnd])
            ->count();

        $slaRiskPrevYear = Ticket::whereIn('status', ['new', 'in_progress', 'waiting_info'])
            ->whereNotNull('sla_deadline_at')
            ->whereBetween('sla_deadline_at', [$prevYearMonthStart, $prevYearMonthEnd])
            ->count();

        // ===== Growth =====
        $totalMoM      = $calcGrowth($total, $totalPrevMonth);
        $newMoM        = $calcGrowth($new, $newPrevMonth);
        $inProgressMoM = $calcGrowth($inProgress, $inProgressPrevMonth);
        $resolvedMoM   = $calcGrowth($resolved, $resolvedPrevMonth);
        $slaRiskMoM    = $calcGrowth($slaRisk, $slaRiskPrevMonth);

        $totalYoY      = $calcGrowth($total, $totalPrevYear);
        $newYoY        = $calcGrowth($new, $newPrevYear);
        $inProgressYoY = $calcGrowth($inProgress, $inProgressPrevYear);
        $resolvedYoY   = $calcGrowth($resolved, $resolvedPrevYear);
        $slaRiskYoY    = $calcGrowth($slaRisk, $slaRiskPrevYear);

        // ===== Today's Focus =====
        $focusSla = $slaRisk;

        $focusDueToday = Ticket::whereIn('status', ['new', 'in_progress', 'waiting_info'])
            ->whereDate('sla_deadline_at', now()->toDateString())
            ->count();

        $focusPendingUser = Ticket::where('status', 'waiting_info')->count();

        $focusReopened = TicketStatusHistory::where('to_status', 'in_progress')
            ->whereNotNull('from_status')
            ->where('from_status', 'resolved')
            ->whereDate('changed_at', now()->toDateString())
            ->count();

        // ===== Filters =====
        $priority = $request->query('priority', 'all');
        $status   = $request->query('status', 'all');
        $sla      = $request->query('sla', 'all');
        $sort     = $request->query('sort', 'latest');
        $inboxPeriod = $request->query('inbox_period', 'today');

        $inboxStart = match ($inboxPeriod) {
            'today' => now()->copy()->startOfDay(),
            '7d'    => now()->copy()->subDays(7),
            '30d'   => now()->copy()->subDays(30),
            'all'   => null,
            default => now()->copy()->startOfDay(),
        };

        // ===== Active Tickets for CS =====
        $activeQ = Ticket::query()
            ->with(['creator', 'holder'])
            ->whereIn('status', ['new', 'in_progress']);

        if ($priority !== 'all') {
            $activeQ->where('priority', $priority);
        }

        if ($status !== 'all') {
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
        $activeTickets = $activeQ->take(6)->get();

        // ===== Resolver Inbox for CS =====
        $resolverInboxQ = ResolverMessage::query()
            ->with(['ticket', 'sender', 'recipient'])
            ->latest();

        if ($inboxStart) {
            $resolverInboxQ->where('created_at', '>=', $inboxStart);
        }

        $resolverInbox = $resolverInboxQ->take(6)->get();

        // ===== IT Dashboard data =====
        $itMyQueue = Ticket::where('team', 'it')
            ->where('holder_id', $request->user()->id)
            ->whereIn('status', ['new', 'in_progress', 'waiting_info'])
            ->latest()
            ->take(6)
            ->get();

        $itTeamNew = Ticket::where('team', 'it')
            ->where('status', 'new')
            ->latest()
            ->take(6)
            ->get();

        // ===== Trend Chart =====
        $trendLabels     = [];
        $trendIT         = [];
        $trendFinance    = [];
        $trendCompliance = [];

        $start = now()->copy()->subDays(6)->startOfDay();
        $end   = now()->copy()->endOfDay();

        $rows = Ticket::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("DATE(created_at) as d, team, COUNT(*) as c")
            ->groupBy('d', 'team')
            ->orderBy('d')
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
            if ($idx !== false && isset($map[$r->team])) {
                $map[$r->team][$idx] = (int) $r->c;
            }
        }

        $trendIT         = $map['it'];
        $trendFinance    = $map['finance'];
        $trendCompliance = $map['compliance'];

        // ===== Top Cases =====
        $topCases = [];
        $topIssueTypes = Ticket::query()
            ->whereNotNull('issue_type')
            ->where('issue_type', '<>', '')
            ->selectRaw('issue_type, COUNT(*) as total')
            ->groupBy('issue_type')
            ->orderByDesc('total')
            ->limit(7)
            ->get();

        foreach ($topIssueTypes as $row) {
            $topTeamRow = Ticket::query()
                ->where('issue_type', $row->issue_type)
                ->selectRaw('team, COUNT(*) as c')
                ->groupBy('team')
                ->orderByDesc('c')
                ->first();

            $topCases[] = [
                'issue_type' => $row->issue_type,
                'count'      => (int) $row->total,
                'top_team'   => $topTeamRow?->team ?? '-',
            ];
        }

        $payload = [
            'role' => $role,
            'kpi' => [
                'total' => [
                    'value' => $total,
                    'prev_month' => $totalPrevMonth,
                    'prev_year' => $totalPrevYear,
                    'mom' => $totalMoM,
                    'yoy' => $totalYoY,
                ],
                'new' => [
                    'value' => $new,
                    'prev_month' => $newPrevMonth,
                    'prev_year' => $newPrevYear,
                    'mom' => $newMoM,
                    'yoy' => $newYoY,
                ],
                'in_progress' => [
                    'value' => $inProgress,
                    'prev_month' => $inProgressPrevMonth,
                    'prev_year' => $inProgressPrevYear,
                    'mom' => $inProgressMoM,
                    'yoy' => $inProgressYoY,
                ],
                'resolved' => [
                    'value' => $resolved,
                    'prev_month' => $resolvedPrevMonth,
                    'prev_year' => $resolvedPrevYear,
                    'mom' => $resolvedMoM,
                    'yoy' => $resolvedYoY,
                ],
                'sla_risk' => [
                    'value' => $slaRisk,
                    'prev_month' => $slaRiskPrevMonth,
                    'prev_year' => $slaRiskPrevYear,
                    'mom' => $slaRiskMoM,
                    'yoy' => $slaRiskYoY,
                ],
            ],
            'focus' => [
                'sla' => $focusSla,
                'due_today' => $focusDueToday,
                'pending_user' => $focusPendingUser,
                'reopened' => $focusReopened,
            ],
            'active_tickets' => $activeTickets,
            'resolver_inbox' => $resolverInbox,
            'it_my_queue' => $itMyQueue,
            'it_team_new' => $itTeamNew,
            'trend' => [
                'labels' => $trendLabels,
                'it' => $trendIT,
                'finance' => $trendFinance,
                'compliance' => $trendCompliance,
            ],
            'top_cases' => $topCases,
        ];

        return $this->success($payload, 'Dashboard data loaded');
    }
}
