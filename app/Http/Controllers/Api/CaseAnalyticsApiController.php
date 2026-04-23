<?php

namespace App\Http\Controllers\Api;

use App\Models\Ticket;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CaseAnalyticsApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'time_range' => ['nullable', 'in:1m,3m,6m,1y,all_time'],
            'team' => ['nullable', 'in:all,it,finance,compliance'],
        ]);

        $timeRange = $validated['time_range'] ?? '1y';
        $team = $validated['team'] ?? 'all';

        [$currentStart, $currentEnd, $previousStart, $previousEnd, $labels] = $this->resolvePeriod($timeRange);

        $totalCurrent = $this->ticketVolume($team, $currentStart, $currentEnd);
        $totalPrevious = $this->ticketVolume($team, $previousStart, $previousEnd);

        $avgResolutionCurrent = $this->avgResolutionMinutes($team, $currentStart, $currentEnd);
        $avgResolutionPrevious = $this->avgResolutionMinutes($team, $previousStart, $previousEnd);

        $firstResponseCurrent = $this->firstResponseMinutes($team, $currentStart, $currentEnd);
        $firstResponsePrevious = $this->firstResponseMinutes($team, $previousStart, $previousEnd);

        $reopenCurrent = $this->reopenRate($team, $currentStart, $currentEnd);
        $reopenPrevious = $this->reopenRate($team, $previousStart, $previousEnd);

        $slaCurrent = $this->slaBreachRate($team, $currentStart, $currentEnd);
        $slaPrevious = $this->slaBreachRate($team, $previousStart, $previousEnd);

        return $this->success([
            'filters' => [
                'time_ranges' => [
                    ['label' => '1 Month', 'value' => '1m'],
                    ['label' => '3 Months', 'value' => '3m'],
                    ['label' => '6 Months', 'value' => '6m'],
                    ['label' => '1 Year', 'value' => '1y'],
                    ['label' => 'All Time', 'value' => 'all_time'],
                ],
                'teams' => [
                    ['id' => 'it', 'name' => 'IT'],
                    ['id' => 'finance', 'name' => 'Finance'],
                    ['id' => 'compliance', 'name' => 'Compliance'],
                ],
                'selected' => [
                    'time_range' => $timeRange,
                    'team' => $team,
                ],
            ],

            'metrics' => [
                $this->buildMetric(
                    'total_tickets_volume',
                    'Total Tickets Volume',
                    $totalCurrent,
                    $totalPrevious,
                    'number',
                    'higher_better'
                ),
                $this->buildMetric(
                    'avg_resolution_time',
                    'Avg. Resolution Time',
                    $avgResolutionCurrent,
                    $avgResolutionPrevious,
                    'minutes',
                    'lower_better'
                ),
                $this->buildMetric(
                    'first_response_time',
                    'First Response Time',
                    $firstResponseCurrent,
                    $firstResponsePrevious,
                    'minutes',
                    'lower_better'
                ),
                $this->buildMetric(
                    're_open_rate',
                    'Re-Open Rate',
                    $reopenCurrent,
                    $reopenPrevious,
                    'percentage',
                    'lower_better'
                ),
                $this->buildMetric(
                    'sla_breach_rate',
                    'SLA Breach Rate',
                    $slaCurrent,
                    $slaPrevious,
                    'percentage',
                    'lower_better'
                ),
            ],

            'ticket_volume_trend' => $this->ticketVolumeTrend($team, $currentStart, $currentEnd, $labels),
            'top_issues_by_category' => $this->topIssuesByCategory($team, $currentStart, $currentEnd),
            'agent_performance_leaderboard' => $this->agentLeaderboard($team, $currentStart, $currentEnd),
            'peak_time_ticket_volume' => $this->peakTimeVolume($team, $currentStart, $currentEnd),
        ], 'Case analytics loaded');
    }

    private function resolvePeriod(string $timeRange): array
    {
        $now = now();

        switch ($timeRange) {
            case '1m':
                $currentStart = $now->copy()->startOfMonth();
                $currentEnd = $now->copy()->endOfDay();
                $previousStart = $currentStart->copy()->subMonth()->startOfMonth();
                $previousEnd = $currentStart->copy()->subSecond();
                break;

            case '3m':
                $currentStart = $now->copy()->startOfMonth()->subMonths(2);
                $currentEnd = $now->copy()->endOfDay();
                $previousStart = $currentStart->copy()->subMonths(3);
                $previousEnd = $currentStart->copy()->subSecond();
                break;

            case '6m':
                $currentStart = $now->copy()->startOfMonth()->subMonths(5);
                $currentEnd = $now->copy()->endOfDay();
                $previousStart = $currentStart->copy()->subMonths(6);
                $previousEnd = $currentStart->copy()->subSecond();
                break;

            case 'all_time':
                $firstTicketDate = Ticket::query()->min('created_at');
                $currentStart = $firstTicketDate
                    ? Carbon::parse($firstTicketDate)->startOfMonth()
                    : $now->copy()->startOfMonth();
                $currentEnd = $now->copy()->endOfDay();

                $monthsSpan = max(1, $currentStart->diffInMonths($currentEnd) + 1);
                $previousStart = $currentStart->copy()->subMonths($monthsSpan);
                $previousEnd = $currentStart->copy()->subSecond();
                break;

            case '1y':
            default:
                $currentStart = $now->copy()->startOfMonth()->subMonths(11);
                $currentEnd = $now->copy()->endOfDay();
                $previousStart = $currentStart->copy()->subYear();
                $previousEnd = $currentStart->copy()->subSecond();
                break;
        }

        $labels = [];
        $period = CarbonPeriod::create(
            $currentStart->copy()->startOfMonth(),
            '1 month',
            $currentEnd->copy()->startOfMonth()
        );

        foreach ($period as $date) {
            $labels[] = $date->format('M');
        }

        return [$currentStart, $currentEnd, $previousStart, $previousEnd, $labels];
    }

    private function baseTicketQuery(string $team = 'all')
    {
        return Ticket::query()
            ->when($team !== 'all', function ($query) use ($team) {
                $query->where('team', $team);
            });
    }

    private function ticketVolume(string $team, Carbon $start, Carbon $end): int
    {
        return (clone $this->baseTicketQuery($team))
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    private function avgResolutionMinutes(string $team, Carbon $start, Carbon $end): float
    {
        $value = (clone $this->baseTicketQuery($team))
            ->whereNotNull('claimed_at')
            ->where(function ($query) {
                $query->whereNotNull('resolved_at')
                    ->orWhereNotNull('closed_at');
            })
            ->whereRaw('COALESCE(resolved_at, closed_at) BETWEEN ? AND ?', [$start, $end])
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, claimed_at, COALESCE(resolved_at, closed_at))) as avg_minutes')
            ->value('avg_minutes');

        return round((float) ($value ?? 0), 1);
    }

    private function firstResponseMinutes(string $team, Carbon $start, Carbon $end): float
    {
        $value = (clone $this->baseTicketQuery($team))
            ->whereNotNull('claimed_at')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, claimed_at)) as avg_minutes')
            ->value('avg_minutes');

        return round((float) ($value ?? 0), 1);
    }

    private function reopenRate(string $team, Carbon $start, Carbon $end): float
    {
        if (!Schema::hasTable('ticket_status_histories')) {
            return 0;
        }

        $total = (clone $this->baseTicketQuery($team))
            ->whereBetween('created_at', [$start, $end])
            ->count();

        if ($total === 0) {
            return 0;
        }

        $reopenedCount = DB::table('ticket_status_histories as a')
            ->join('ticket_status_histories as b', 'a.ticket_id', '=', 'b.ticket_id')
            ->join('tickets', 'tickets.id', '=', 'a.ticket_id')
            ->whereBetween('tickets.created_at', [$start, $end])
            ->whereIn('a.to_status', ['resolved', 'closed'])
            ->whereIn('b.to_status', ['new', 'in_progress', 'waiting_info'])
            ->whereColumn('b.changed_at', '>', 'a.changed_at')
            ->when($team !== 'all', function ($query) use ($team) {
                $query->where('tickets.team', $team);
            })
            ->distinct()
            ->count('a.ticket_id');

        return round(($reopenedCount / $total) * 100, 1);
    }

    private function slaBreachRate(string $team, Carbon $start, Carbon $end): float
    {
        $base = (clone $this->baseTicketQuery($team))
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('sla_deadline_at');

        $total = (clone $base)->count();

        if ($total === 0) {
            return 0;
        }

        $breached = (clone $base)
            ->whereRaw('COALESCE(resolved_at, closed_at, NOW()) > sla_deadline_at')
            ->count();

        return round(($breached / $total) * 100, 1);
    }

    private function ticketVolumeTrend(string $team, Carbon $start, Carbon $end, array $labels): array
    {
        $incomingRows = (clone $this->baseTicketQuery($team))
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('YEAR(created_at) as year_num, MONTH(created_at) as month_num, COUNT(*) as total')
            ->groupBy('year_num', 'month_num')
            ->orderBy('year_num')
            ->orderBy('month_num')
            ->get();

        $resolvedRows = (clone $this->baseTicketQuery($team))
            ->whereIn('status', ['resolved', 'closed'])
            ->where(function ($query) {
                $query->whereNotNull('resolved_at')
                    ->orWhereNotNull('closed_at');
            })
            ->whereRaw('COALESCE(resolved_at, closed_at) BETWEEN ? AND ?', [$start, $end])
            ->selectRaw('YEAR(COALESCE(resolved_at, closed_at)) as year_num, MONTH(COALESCE(resolved_at, closed_at)) as month_num, COUNT(*) as total')
            ->groupBy('year_num', 'month_num')
            ->orderBy('year_num')
            ->orderBy('month_num')
            ->get();

        $incomingMap = [];
        foreach ($incomingRows as $row) {
            $incomingMap[sprintf('%04d-%02d', $row->year_num, $row->month_num)] = (int) $row->total;
        }

        $resolvedMap = [];
        foreach ($resolvedRows as $row) {
            $resolvedMap[sprintf('%04d-%02d', $row->year_num, $row->month_num)] = (int) $row->total;
        }

        $incomingSeries = [];
        $resolvedSeries = [];

        $period = CarbonPeriod::create(
            $start->copy()->startOfMonth(),
            '1 month',
            $end->copy()->startOfMonth()
        );

        foreach ($period as $date) {
            $key = $date->format('Y-m');
            $incomingSeries[] = $incomingMap[$key] ?? 0;
            $resolvedSeries[] = $resolvedMap[$key] ?? 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'Incoming', 'data' => $incomingSeries],
                ['label' => 'Resolved', 'data' => $resolvedSeries],
            ],
            'y_axis_max' => $this->roundedAxisMax(array_merge($incomingSeries, $resolvedSeries)),
            'step_size' => 10,
        ];
    }

    private function topIssuesByCategory(string $team, Carbon $start, Carbon $end): array
    {
        $rows = (clone $this->baseTicketQuery($team))
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(7)
            ->get();

        $items = [];

        foreach ($rows as $row) {
            $topTeamRow = Ticket::query()
                ->whereBetween('created_at', [$start, $end])
                ->where('category', $row->category)
                ->selectRaw('team, COUNT(*) as c')
                ->groupBy('team')
                ->orderByDesc('c')
                ->first();

            $items[] = [
                'category' => $row->category,
                'count' => (int) $row->total,
                'top_team' => $topTeamRow?->team ?? '-',
            ];
        }

        $counts = collect($items)->pluck('count')->toArray();
        $max = max($counts ?: [0]);

        if ($max <= 10) {
            $axisMax = 15;
            $stepSize = 5;
        } elseif ($max <= 20) {
            $axisMax = (int) ceil(($max + 5) / 5) * 5;
            $stepSize = 5;
        } else {
            $axisMax = (int) ceil(($max + 5) / 5) * 5;
            $stepSize = 10;
        }

        return [
            'labels' => collect($items)->pluck('category')->toArray(),
            'values' => $counts,
            'items' => $items,
            'y_axis_max' => $axisMax,
            'step_size' => $stepSize,
        ];
    }

    private function agentLeaderboard(string $team, Carbon $start, Carbon $end): array
    {
        $rows = (clone $this->baseTicketQuery($team))
            ->join('users', 'users.id', '=', 'tickets.holder_id')
            ->whereNotNull('tickets.holder_id')
            ->whereNotNull('tickets.claimed_at')
            ->where(function ($query) {
                $query->whereNotNull('tickets.resolved_at')
                    ->orWhereNotNull('tickets.closed_at');
            })
            ->whereRaw('COALESCE(tickets.resolved_at, tickets.closed_at) BETWEEN ? AND ?', [$start, $end])
            ->selectRaw('
            users.id,
            users.name,
            COUNT(tickets.id) as resolved_count,
            AVG(TIMESTAMPDIFF(MINUTE, tickets.claimed_at, COALESCE(tickets.resolved_at, tickets.closed_at))) as avg_resolution_minutes
        ')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('resolved_count')
            ->orderBy('avg_resolution_minutes')
            ->limit(10)
            ->get();

        $rank = 1;

        return $rows->map(function ($row) use (&$rank) {
            return [
                'rank' => $rank++,
                'agent_name' => $row->name,
                'resolved_count' => (int) $row->resolved_count,
                'avg_resolution_minutes' => round((float) ($row->avg_resolution_minutes ?? 0), 1),
                'avg_resolution_display' => $this->minutesToHuman((float) ($row->avg_resolution_minutes ?? 0)),
                'csat' => null,
            ];
        })->values()->toArray();
    }

    private function peakTimeVolume(string $team, Carbon $start, Carbon $end): array
    {
        $rows = (clone $this->baseTicketQuery($team))
            ->whereBetween('created_at', [$start, $end])
            ->whereRaw('HOUR(created_at) BETWEEN 8 AND 17')
            ->selectRaw('HOUR(created_at) as hour_num, COUNT(*) as total')
            ->groupBy('hour_num')
            ->orderBy('hour_num')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->hour_num] = (int) $row->total;
        }

        $labels = [];
        $values = [];

        for ($hour = 8; $hour <= 17; $hour++) {
            $labels[] = str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00';
            $values[] = $map[$hour] ?? 0;
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'y_axis_max' => $this->roundedAxisMax($values),
            'step_size' => 10,
        ];
    }

    private function buildMetric(string $key, string $title, float|int $current, float|int $previous, string $type, string $semantic = 'neutral'): array
    {
        $changePct = $this->percentageChange($current, $previous);

        $trend = 'flat';
        if ($current > $previous) {
            $trend = 'up';
        } elseif ($current < $previous) {
            $trend = 'down';
        }

        $improved = match ($semantic) {
            'lower_better' => $current <= $previous,
            'higher_better' => $current >= $previous,
            default => null,
        };

        return [
            'key' => $key,
            'title' => $title,
            'value_raw' => $current,
            'value_display' => $this->formatMetricValue($current, $type),
            'previous_value_raw' => $previous,
            'previous_value_display' => $this->formatMetricValue($previous, $type),
            'change_pct' => $changePct,
            'trend' => $trend,
            'semantic' => $semantic,
            'improved' => $improved,
        ];
    }

    private function formatMetricValue(float|int $value, string $type): string
    {
        return match ($type) {
            'minutes' => $this->minutesToHuman((float) $value),
            'percentage' => number_format((float) $value, 1) . '%',
            default => number_format((float) $value, 0),
        };
    }

    private function minutesToHuman(float $minutes): string
    {
        $minutes = max(0, (int) round($minutes));

        if ($minutes < 60) {
            return $minutes . 'm';
        }

        $hours = intdiv($minutes, 60);

        if ($hours < 24) {
            $remainingMinutes = $minutes % 60;
            return $remainingMinutes > 0
                ? $hours . 'h ' . $remainingMinutes . 'm'
                : $hours . 'h';
        }

        $days = intdiv($hours, 24);
        $remainingHours = $hours % 24;

        return $remainingHours > 0
            ? $days . 'd ' . $remainingHours . 'h'
            : $days . 'd';
    }

    private function percentageChange(float|int $current, float|int $previous): float
    {
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0 ? 0.0 : 100.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function roundedAxisMax(array $values): int
    {
        $max = max($values ?: [0]);

        return $max <= 0 ? 10 : (int) ceil($max / 10) * 10;
    }
}
