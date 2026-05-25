<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Team;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CaseAnalyticsService
{
    public function export(string $timeRange, string $team, string $format)
    {
        [$currentStart, $currentEnd] = $this->currentPeriodOnly($timeRange);
        $payload = $this->analyticsPayload($timeRange, $team);
        $fileBaseName = 'case-analytics-' . $team . '-' . $timeRange . '-' . now()->format('Ymd-His');

        if (in_array($format, ['excel', 'xls'], true)) {
            return $this->exportExcel($payload, $team, $currentStart, $currentEnd, $fileBaseName . '.xls');
        }

        return $this->exportPdf($payload, $fileBaseName . '.pdf');
    }


    public function exportTicketCount(string $timeRange, string $team): int
    {
        [$start, $end] = $this->currentPeriodOnly($timeRange);

        return $this->ticketVolume($team, $start, $end);
    }

    public function analyticsPayload(string $timeRange, string $team): array
    {
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

        return [
            'filters' => [
                'time_ranges' => $this->timeRangeOptions(),
                'teams' => $this->teamOptions(),
                'selected' => [
                    'time_range' => $timeRange,
                    'team' => $team,
                    'time_range_label' => $this->timeRangeLabel($timeRange),
                    'team_label' => $this->teamLabel($team),
                    'date_from' => $currentStart->toDateString(),
                    'date_to' => $currentEnd->toDateString(),
                    'date_range_label' => $this->periodDisplay($currentStart, $currentEnd),
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
            'top_issue_types' => $this->topIssueTypes($team, $currentStart, $currentEnd),
            'agent_performance_leaderboard' => $this->agentLeaderboard($team, $currentStart, $currentEnd),
            'top_teams' => $this->topTeams($team, $currentStart, $currentEnd),
            'peak_time_ticket_volume' => $this->peakTimeVolume($team, $currentStart, $currentEnd),
        ];
    }

    public function exportViewData(array $payload): array
    {
        return [
            'payload' => $payload,
            'metricRows' => $this->metricExportRows($payload['metrics'] ?? []),
            'topCategoryRows' => $this->topCategoryExportRows($payload['top_issues_by_category']['items'] ?? []),
            'topIssueRows' => $this->topIssueExportRows($payload['top_issue_types']['items'] ?? []),
            'leaderboardRows' => $this->leaderboardExportRows($payload['agent_performance_leaderboard'] ?? []),
            'topTeamRows' => $this->topTeamExportRows($payload['top_teams']['items'] ?? []),
            'trendRows' => $this->trendExportRows($payload['ticket_volume_trend'] ?? []),
            'peakTimeRows' => $this->peakTimeExportRows($payload['peak_time_ticket_volume'] ?? []),
            'generatedAt' => now()->format('d M Y, H:i:s'),
        ];
    }

    private function currentPeriodOnly(string $timeRange): array
    {
        [$currentStart, $currentEnd] = $this->resolvePeriod($timeRange);

        return [$currentStart, $currentEnd];
    }

    private function timeRangeOptions(): array
    {
        return [
            ['label' => '1 Month', 'value' => '1m'],
            ['label' => '3 Months', 'value' => '3m'],
            ['label' => '6 Months', 'value' => '6m'],
            ['label' => '1 Year', 'value' => '1y'],
            ['label' => 'All Time', 'value' => 'all_time'],
        ];
    }

    private function teamOptions(): array
    {
        return [
            ['id' => 'it', 'name' => 'IT'],
            ['id' => 'finance', 'name' => 'Finance'],
            ['id' => 'compliance', 'name' => 'Compliance'],
        ];
    }

    private function timeRangeLabel(string $timeRange): string
    {
        return collect($this->timeRangeOptions())
            ->firstWhere('value', $timeRange)['label'] ?? '1 Year';
    }

    private function teamLabel(string $team): string
    {
        if ($team === 'all') {
            return 'All Teams';
        }

        return collect($this->teamOptions())
            ->firstWhere('id', $team)['name'] ?? strtoupper($team);
    }

    private function periodDisplay(Carbon $start, Carbon $end): string
    {
        return $start->format('d M Y') . ' - ' . $end->format('d M Y');
    }

    private function exportExcel(array $payload, string $team, Carbon $start, Carbon $end, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($payload, $team, $start, $end) {
            echo '<html>';
            echo '<head>';
            echo '<meta charset="UTF-8">';
            echo '<style>';
            echo 'body{font-family:Arial,sans-serif;font-size:12px;color:#1e293b;}';
            echo 'h1{font-size:22px;margin:0 0 8px 0;color:#051823;}';
            echo 'h2{font-size:16px;margin:22px 0 8px 0;color:#051823;}';
            echo 'p{margin:4px 0;color:#475569;}';
            echo 'table{width:100%;border-collapse:collapse;margin-bottom:14px;}';
            echo 'th{background:#051823;color:#ffffff;font-weight:700;text-align:left;}';
            echo 'th,td{border:1px solid #cbd5e1;padding:8px;vertical-align:top;}';
            echo 'tr:nth-child(even){background:#eef3f7;}';
            echo '</style>';
            echo '</head>';
            echo '<body>';

            echo '<h1>Case Analytics Report</h1>';
            echo '<p><strong>Date Range:</strong> ' . e($payload['filters']['selected']['date_range_label']) . '</p>';
            echo '<p><strong>Time Range:</strong> ' . e($payload['filters']['selected']['time_range_label']) . '</p>';
            echo '<p><strong>Team:</strong> ' . e($payload['filters']['selected']['team_label']) . '</p>';
            echo '<p><strong>Generated:</strong> ' . e(now()->format('d M Y, H:i:s')) . '</p>';

            $this->excelTable('Summary KPI', ['Metric', 'Current', 'Previous', 'Change', 'Trend'], $this->metricExportRows($payload['metrics'] ?? []));
            $this->excelTable('Top Categories', ['Category', 'Tickets', 'Team with Most Tickets'], $this->topCategoryExportRows($payload['top_issues_by_category']['items'] ?? []));
            $this->excelTable('Top Issues', ['Issue Type', 'Category', 'Tickets', 'Team with Most Tickets'], $this->topIssueExportRows($payload['top_issue_types']['items'] ?? []));
            $this->excelTable('Agent Performance', ['Rank', 'Agent', 'Resolved', 'Avg. Resolution Time', 'CSAT'], $this->leaderboardExportRows($payload['agent_performance_leaderboard'] ?? []));
            $this->excelTable('Top Teams', ['Rank', 'Team', 'Tickets', 'Resolved', 'Avg. Resolution Time'], $this->topTeamExportRows($payload['top_teams']['items'] ?? []));
            $this->excelTable('Monthly Trend', ['Month', 'Incoming', 'Resolved'], $this->trendExportRows($payload['ticket_volume_trend'] ?? []));
            $this->excelTable('Peak Time Ticket Volume', ['Hour', 'Tickets'], $this->peakTimeExportRows($payload['peak_time_ticket_volume'] ?? []));
            $this->excelRawAnalyticsTable('Raw Analytics Data', $this->rawExportHeaders(), $team, $start, $end);

            echo '</body>';
            echo '</html>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    private function exportPdf(array $payload, string $filename)
    {
        $pdf = Pdf::loadView(
            'exports.case-analytics-pdf',
            $this->exportViewData($payload)
        )->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    private function excelTable(string $title, array $headers, array $rows): void
    {
        echo '<h2>' . e($title) . '</h2>';
        echo '<table>';
        echo '<thead><tr>';
        foreach ($headers as $header) {
            echo '<th>' . e($header) . '</th>';
        }
        echo '</tr></thead>';
        echo '<tbody>';

        if (count($rows) === 0) {
            echo '<tr><td colspan="' . count($headers) . '">No data available</td></tr>';
        } else {
            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($row as $cell) {
                    echo '<td>' . e((string) $cell) . '</td>';
                }
                echo '</tr>';
            }
        }

        echo '</tbody>';
        echo '</table>';
    }

    private function metricExportRows(array $metrics): array
    {
        return collect($metrics)->map(function (array $metric) {
            return [
                $metric['title'] ?? '-',
                $metric['value_display'] ?? '-',
                $metric['previous_value_display'] ?? '-',
                ($metric['trend'] ?? 'flat') === 'flat'
                    ? '0.0%'
                    : (($metric['trend'] ?? 'up') === 'up' ? '+' : '-') . abs((float) ($metric['change_pct'] ?? 0)) . '%',
                ($metric['improved'] ?? null) === null
                    ? ucfirst((string) ($metric['trend'] ?? 'flat'))
                    : ($metric['improved'] ? 'Improved' : 'Needs Attention'),
            ];
        })->toArray();
    }

    private function topCategoryExportRows(array $items): array
    {
        return collect($items)->map(fn (array $item) => [
            $this->labelText($item['category'] ?? '-'),
            (int) ($item['count'] ?? 0),
            strtoupper((string) ($item['top_team'] ?? '-')),
        ])->toArray();
    }

    private function topIssueExportRows(array $items): array
    {
        return collect($items)->map(fn (array $item) => [
            $this->labelText($item['issue_type'] ?? '-'),
            $this->labelText($item['category'] ?? '-'),
            (int) ($item['count'] ?? 0),
            strtoupper((string) ($item['top_team'] ?? '-')),
        ])->toArray();
    }

    private function leaderboardExportRows(array $rows): array
    {
        return collect($rows)->map(fn (array $row) => [
            $row['rank'] ?? '-',
            $row['agent_name'] ?? '-',
            (int) ($row['resolved_count'] ?? 0),
            $row['avg_resolution_display'] ?? '-',
            ($row['csat'] ?? null) !== null ? number_format((float) $row['csat'], 2) : '-',
        ])->toArray();
    }

    private function topTeamExportRows(array $rows): array
    {
        return collect($rows)->map(fn (array $row) => [
            $row['rank'] ?? '-',
            $row['team_name'] ?? '-',
            (int) ($row['tickets_count'] ?? 0),
            (int) ($row['resolved_count'] ?? 0),
            $row['avg_resolution_display'] ?? '-',
        ])->toArray();
    }

    private function trendExportRows(array $trend): array
    {
        $labels = $trend['labels'] ?? [];
        $incoming = $trend['datasets'][0]['data'] ?? [];
        $resolved = $trend['datasets'][1]['data'] ?? [];

        $rows = [];
        foreach ($labels as $index => $label) {
            $rows[] = [
                $label,
                (int) ($incoming[$index] ?? 0),
                (int) ($resolved[$index] ?? 0),
            ];
        }

        return $rows;
    }

    private function peakTimeExportRows(array $peakTime): array
    {
        $labels = $peakTime['labels'] ?? [];
        $values = $peakTime['values'] ?? [];

        $rows = [];
        foreach ($labels as $index => $label) {
            $rows[] = [$label, (int) ($values[$index] ?? 0)];
        }

        return $rows;
    }

    private function rawExportHeaders(): array
    {
        return [
            'Ticket',
            'Title',
            'Created At',
            'Resolved/Closed At',
            'Team',
            'Priority',
            'Status',
            'Category',
            'Issue Type',
            'Assigned To',
            'Resolution Time',
            'SLA Status',
        ];
    }

    private function excelRawAnalyticsTable(string $title, array $headers, string $team, Carbon $start, Carbon $end): void
    {
        echo '<h2>' . e($title) . '</h2>';
        echo '<table>';
        echo '<thead><tr>';

        foreach ($headers as $header) {
            echo '<th>' . e($header) . '</th>';
        }

        echo '</tr></thead>';
        echo '<tbody>';

        $hasRows = false;

        $this->rawAnalyticsQuery($team, $start, $end)
            ->chunk(500, function (Collection $tickets) use (&$hasRows) {
                foreach ($tickets as $ticket) {
                    $hasRows = true;

                    echo '<tr>';

                    foreach ($this->rawAnalyticsRow($ticket) as $cell) {
                        echo '<td>' . e((string) $cell) . '</td>';
                    }

                    echo '</tr>';
                }
            });

        if (!$hasRows) {
            echo '<tr><td colspan="' . count($headers) . '">No data available</td></tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }

    private function rawAnalyticsQuery(string $team, Carbon $start, Carbon $end)
    {
        return (clone $this->baseTicketQuery($team))
            ->with(['holder', 'teamMaster', 'priorityMaster', 'categoryMaster', 'issueTypeMaster'])
            ->whereBetween('created_at', [$start, $end])
            ->latest('created_at');
    }

    private function rawAnalyticsRow(Ticket $ticket): array
    {
        return [
            $this->ticketLabel($ticket),
            $ticket->title ?? '-',
            optional($ticket->created_at)?->format('Y-m-d H:i:s') ?? '-',
            $this->resolvedAtLabel($ticket),
            strtoupper($ticket->displayTeamCode() ?: '-'),
            $this->labelText($ticket->displayPriorityCode() ?: '-'),
            $this->statusText($ticket->status ?? '-'),
            $this->labelText($ticket->displayCategoryName() ?: '-'),
            $this->labelText($ticket->displayIssueTypeName() ?: '-'),
            $ticket->holder?->name ?? '-',
            $this->resolutionDurationText($ticket),
            $this->slaStatusText($ticket),
        ];
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

    private function teamIdByCode(string $code): ?int
    {
        return Team::query()
            ->where('code', $code)
            ->value('id');
    }

    private function baseTicketQuery(string $team = 'all'): Builder
    {
        return Ticket::query()
            ->when($team !== 'all', function (Builder $query) use ($team) {
                $query->forTeamCode($team);
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
        $average = (clone $this->baseTicketQuery($team))
            ->whereNotNull('claimed_at')
            ->where(function (Builder $query) use ($start, $end) {
                $query->whereBetween('resolved_at', [$start, $end])
                    ->orWhereBetween('closed_at', [$start, $end]);
            })
            ->selectRaw("AVG(TIMESTAMPDIFF(MINUTE, claimed_at, COALESCE(resolved_at, closed_at))) as avg_minutes")
            ->value('avg_minutes');

        return round((float) ($average ?? 0), 1);
    }


    private function firstResponseMinutes(string $team, Carbon $start, Carbon $end): float
    {
        $average = (clone $this->baseTicketQuery($team))
            ->whereNotNull('claimed_at')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, claimed_at)) as avg_minutes')
            ->value('avg_minutes');

        return round((float) ($average ?? 0), 1);
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
                $teamId = $this->teamIdByCode($team);

                $query->where(function ($query) use ($team, $teamId) {
                    if ($teamId) {
                        $query->where('tickets.team_id', $teamId)
                            ->orWhere('tickets.team', $team);

                        return;
                    }

                    $query->where('tickets.team', $team);
                });
            })
            ->distinct()
            ->count('a.ticket_id');

        return round(($reopenedCount / $total) * 100, 1);
    }

    private function slaBreachRate(string $team, Carbon $start, Carbon $end): float
    {
        $summary = (clone $this->baseTicketQuery($team))
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('sla_deadline_at')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE
                    WHEN COALESCE(resolved_at, closed_at, NOW()) > sla_deadline_at
                    THEN 1 ELSE 0
                END) as breached
            ")
            ->first();

        $total = (int) ($summary?->total ?? 0);

        if ($total === 0) {
            return 0;
        }

        return round((((int) ($summary?->breached ?? 0)) / $total) * 100, 1);
    }


    private function ticketVolumeTrend(string $team, Carbon $start, Carbon $end, array $labels): array
    {
        $incomingMap = (clone $this->baseTicketQuery($team))
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, COUNT(*) as total")
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->pluck('total', 'month_key')
            ->all();

        $resolvedMap = (clone $this->baseTicketQuery($team))
            ->whereIn('status', ['resolved', 'closed'])
            ->where(function (Builder $query) use ($start, $end) {
                $query->whereBetween('resolved_at', [$start, $end])
                    ->orWhereBetween('closed_at', [$start, $end]);
            })
            ->selectRaw("DATE_FORMAT(COALESCE(resolved_at, closed_at), '%Y-%m') as month_key, COUNT(*) as total")
            ->groupByRaw("DATE_FORMAT(COALESCE(resolved_at, closed_at), '%Y-%m')")
            ->pluck('total', 'month_key')
            ->all();

        $incomingSeries = [];
        $resolvedSeries = [];

        $cursor = $start->copy()->startOfMonth();
        $endMonth = $end->copy()->startOfMonth();

        while ($cursor->lte($endMonth)) {
            $key = $cursor->format('Y-m');
            $incomingSeries[] = (int) ($incomingMap[$key] ?? 0);
            $resolvedSeries[] = (int) ($resolvedMap[$key] ?? 0);
            $cursor->addMonth();
        }

        $axisMax = $this->roundedAxisMax(array_merge($incomingSeries, $resolvedSeries));

        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'Incoming', 'data' => $incomingSeries],
                ['label' => 'Resolved', 'data' => $resolvedSeries],
            ],
            'incoming' => $incomingSeries,
            'resolved' => $resolvedSeries,
            'y_axis_max' => $axisMax,
            'step_size' => $axisMax <= 10 ? 2 : 10,
        ];
    }



    private function topIssuesByCategory(string $team, Carbon $start, Carbon $end): array
    {
        $categoryKeyExpression = "COALESCE(CAST(tickets.category_id AS CHAR), CONCAT('snapshot:', COALESCE(NULLIF(tickets.category, ''), 'uncategorized')))";
        $categoryLabelExpression = "COALESCE(categories.name, NULLIF(tickets.category, ''), 'Uncategorized')";
        $teamCodeExpression = "LOWER(COALESCE(teams.code, NULLIF(tickets.team, ''), '-'))";

        $rows = (clone $this->baseTicketQuery($team))
            ->leftJoin('categories', 'categories.id', '=', 'tickets.category_id')
            ->leftJoin('teams', 'teams.id', '=', 'tickets.team_id')
            ->whereBetween('tickets.created_at', [$start, $end])
            ->where(function (Builder $query) {
                $query->whereNotNull('tickets.category_id')
                    ->orWhere(function (Builder $query) {
                        $query->whereNotNull('tickets.category')
                            ->where('tickets.category', '<>', '');
                    });
            })
            ->selectRaw("{$categoryKeyExpression} as category_key")
            ->selectRaw("{$categoryLabelExpression} as category")
            ->selectRaw("{$teamCodeExpression} as team_code")
            ->selectRaw('COUNT(*) as total')
            ->groupByRaw("{$categoryKeyExpression}, {$categoryLabelExpression}, {$teamCodeExpression}")
            ->get();

        $items = $rows
            ->groupBy('category_key')
            ->map(function (Collection $group) {
                $teamCounts = $group
                    ->groupBy('team_code')
                    ->map(fn (Collection $teamRows) => $teamRows->sum(fn ($row) => (int) $row->total));

                return [
                    'category' => (string) ($group->first()?->category ?: 'Uncategorized'),
                    'count' => (int) $group->sum(fn ($row) => (int) $row->total),
                    'top_team' => (string) ($teamCounts->sortDesc()->keys()->first() ?: '-'),
                ];
            })
            ->sortByDesc('count')
            ->take(7)
            ->values();

        $counts = $items->pluck('count')->map(fn ($value) => (int) $value)->toArray();
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
            'labels' => $items->pluck('category')->toArray(),
            'values' => $counts,
            'items' => $items->toArray(),
            'y_axis_max' => $axisMax,
            'step_size' => $stepSize,
        ];
    }


    private function topIssueTypes(string $team, Carbon $start, Carbon $end): array
    {
        $issueKeyExpression = "COALESCE(CAST(tickets.issue_type_id AS CHAR), CONCAT('snapshot:', COALESCE(NULLIF(tickets.issue_type, ''), 'unknown')))";
        $issueLabelExpression = "COALESCE(issue_types.name, NULLIF(tickets.issue_type, ''), 'Unknown Issue')";
        $categoryLabelExpression = "COALESCE(categories.name, NULLIF(tickets.category, ''), '-')";
        $teamCodeExpression = "LOWER(COALESCE(teams.code, NULLIF(tickets.team, ''), '-'))";

        $rows = (clone $this->baseTicketQuery($team))
            ->leftJoin('issue_types', 'issue_types.id', '=', 'tickets.issue_type_id')
            ->leftJoin('categories', 'categories.id', '=', 'tickets.category_id')
            ->leftJoin('teams', 'teams.id', '=', 'tickets.team_id')
            ->whereBetween('tickets.created_at', [$start, $end])
            ->where(function (Builder $query) {
                $query->whereNotNull('tickets.issue_type_id')
                    ->orWhere(function (Builder $query) {
                        $query->whereNotNull('tickets.issue_type')
                            ->where('tickets.issue_type', '<>', '');
                    });
            })
            ->selectRaw("{$issueKeyExpression} as issue_key")
            ->selectRaw("{$issueLabelExpression} as issue_type")
            ->selectRaw("{$categoryLabelExpression} as category")
            ->selectRaw("{$teamCodeExpression} as team_code")
            ->selectRaw('COUNT(*) as total')
            ->groupByRaw("{$issueKeyExpression}, {$issueLabelExpression}, {$categoryLabelExpression}, {$teamCodeExpression}")
            ->get();

        $items = $rows
            ->groupBy('issue_key')
            ->map(function (Collection $group) {
                $teamCounts = $group
                    ->groupBy('team_code')
                    ->map(fn (Collection $teamRows) => $teamRows->sum(fn ($row) => (int) $row->total));

                return [
                    'issue_type' => (string) ($group->first()?->issue_type ?: 'Unknown Issue'),
                    'category' => (string) ($group->first()?->category ?: '-'),
                    'count' => (int) $group->sum(fn ($row) => (int) $row->total),
                    'top_team' => (string) ($teamCounts->sortDesc()->keys()->first() ?: '-'),
                ];
            })
            ->sortByDesc('count')
            ->take(7)
            ->values();

        return [
            'labels' => $items->pluck('issue_type')->toArray(),
            'values' => $items->pluck('count')->toArray(),
            'items' => $items->toArray(),
        ];
    }


    private function agentLeaderboard(string $team, Carbon $start, Carbon $end): array
    {
        $rows = (clone $this->baseTicketQuery($team))
            ->leftJoin('users as holders', 'holders.id', '=', 'tickets.holder_id')
            ->whereNotNull('tickets.holder_id')
            ->whereNotNull('tickets.claimed_at')
            ->where(function (Builder $query) use ($start, $end) {
                $query->whereBetween('tickets.resolved_at', [$start, $end])
                    ->orWhereBetween('tickets.closed_at', [$start, $end]);
            })
            ->selectRaw("
                tickets.holder_id,
                COALESCE(holders.name, 'Unassigned') as agent_name,
                COUNT(*) as resolved_count,
                AVG(TIMESTAMPDIFF(MINUTE, tickets.claimed_at, COALESCE(tickets.resolved_at, tickets.closed_at))) as avg_resolution_minutes
            ")
            ->groupBy('tickets.holder_id', 'holders.name')
            ->orderByDesc('resolved_count')
            ->orderBy('avg_resolution_minutes')
            ->limit(10)
            ->get();

        return $rows
            ->values()
            ->map(function ($row, int $index) {
                $average = round((float) ($row->avg_resolution_minutes ?? 0), 1);

                return [
                    'agent_name' => (string) ($row->agent_name ?: 'Unassigned'),
                    'resolved_count' => (int) $row->resolved_count,
                    'avg_resolution_minutes' => $average,
                    'rank' => $index + 1,
                    'avg_resolution_display' => $this->minutesToHuman($average),
                    'csat' => null,
                ];
            })
            ->toArray();
    }



    private function topTeams(string $team, Carbon $start, Carbon $end): array
    {
        $teamKeyExpression = "COALESCE(CAST(tickets.team_id AS CHAR), CONCAT('snapshot:', LOWER(COALESCE(NULLIF(tickets.team, ''), 'unknown'))))";
        $teamCodeExpression = "LOWER(COALESCE(teams.code, NULLIF(tickets.team, ''), 'unknown'))";
        $teamNameExpression = "COALESCE(teams.name, NULLIF(tickets.team, ''), 'Unknown Team')";

        $items = (clone $this->baseTicketQuery($team))
            ->leftJoin('teams', 'teams.id', '=', 'tickets.team_id')
            ->whereBetween('tickets.created_at', [$start, $end])
            ->selectRaw("{$teamKeyExpression} as team_key")
            ->selectRaw("{$teamCodeExpression} as team_code")
            ->selectRaw("{$teamNameExpression} as team_name")
            ->selectRaw('COUNT(*) as tickets_count')
            ->selectRaw("SUM(CASE WHEN tickets.resolved_at IS NOT NULL OR tickets.closed_at IS NOT NULL THEN 1 ELSE 0 END) as resolved_count")
            ->selectRaw("AVG(CASE WHEN tickets.claimed_at IS NOT NULL AND (tickets.resolved_at IS NOT NULL OR tickets.closed_at IS NOT NULL) THEN TIMESTAMPDIFF(MINUTE, tickets.claimed_at, COALESCE(tickets.resolved_at, tickets.closed_at)) ELSE NULL END) as avg_resolution_minutes")
            ->groupByRaw("{$teamKeyExpression}, {$teamCodeExpression}, {$teamNameExpression}")
            ->orderByDesc('tickets_count')
            ->orderByDesc('resolved_count')
            ->orderBy('avg_resolution_minutes')
            ->limit(5)
            ->get()
            ->values()
            ->map(function ($row, int $index) {
                $average = round((float) ($row->avg_resolution_minutes ?? 0), 1);

                return [
                    'team_code' => (string) ($row->team_code ?: 'unknown'),
                    'team_name' => $row->team_name ? $this->labelText((string) $row->team_name) : 'Unknown Team',
                    'tickets_count' => (int) $row->tickets_count,
                    'resolved_count' => (int) $row->resolved_count,
                    'avg_resolution_minutes' => $average,
                    'rank' => $index + 1,
                    'avg_resolution_display' => $average > 0 ? $this->minutesToHuman($average) : '-',
                ];
            });

        $values = $items->pluck('tickets_count')->map(fn ($value) => (int) $value)->toArray();

        return [
            'labels' => $items->pluck('team_name')->toArray(),
            'values' => $values,
            'items' => $items->toArray(),
            'y_axis_max' => $this->roundedAxisMax($values),
            'step_size' => max(1, min(10, $this->roundedAxisMax($values) <= 10 ? 2 : 10)),
        ];
    }



    private function peakTimeVolume(string $team, Carbon $start, Carbon $end): array
    {
        $map = (clone $this->baseTicketQuery($team))
            ->whereBetween('created_at', [$start, $end])
            ->whereRaw('HOUR(created_at) BETWEEN 8 AND 17')
            ->selectRaw('HOUR(created_at) as hour_key, COUNT(*) as total')
            ->groupByRaw('HOUR(created_at)')
            ->pluck('total', 'hour_key')
            ->all();

        $labels = [];
        $values = [];

        for ($hour = 8; $hour <= 17; $hour++) {
            $labels[] = str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00';
            $values[] = (int) ($map[$hour] ?? 0);
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

    private function ticketLabel(Ticket $ticket): string
    {
        $rawCode = $ticket->ticket_code ?: $ticket->id;
        $cleanCode = preg_replace('/[\s#]+/', '', (string) $rawCode);
        $cleanCode = preg_replace('/^T-?/i', '', (string) $cleanCode);

        return $cleanCode ? 'T-' . $cleanCode : '-';
    }

    private function resolvedAtLabel(Ticket $ticket): string
    {
        $value = $ticket->resolved_at ?: $ticket->closed_at;

        return $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : '-';
    }

    private function resolutionDurationText(Ticket $ticket): string
    {
        if (!$ticket->created_at) {
            return '-';
        }

        $endValue = $ticket->resolved_at ?: $ticket->closed_at;
        if (!$endValue) {
            return '-';
        }

        $start = Carbon::parse($ticket->created_at);
        $end = Carbon::parse($endValue);
        $seconds = abs($start->diffInSeconds($end));

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($days > 0) {
            return $days . 'd ' . $hours . 'h ' . $minutes . 'm ' . $remainingSeconds . 's';
        }

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm ' . $remainingSeconds . 's';
        }

        if ($minutes > 0) {
            return $minutes . 'm ' . $remainingSeconds . 's';
        }

        return $remainingSeconds . 's';
    }

    private function slaStatusText(Ticket $ticket): string
    {
        if (!$ticket->sla_deadline_at) {
            return '-';
        }

        $endValue = $ticket->resolved_at ?: $ticket->closed_at ?: now();

        return Carbon::parse($endValue)->lte(Carbon::parse($ticket->sla_deadline_at))
            ? 'Met'
            : 'Breached';
    }

    private function statusText(string $status): string
    {
        return str($status)->replace('_', ' ')->title()->toString();
    }

    private function labelText(?string $value): string
    {
        if (!$value) {
            return '-';
        }

        return str($value)->replace('_', ' ')->title()->toString();
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
