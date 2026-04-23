<?php

namespace App\Http\Controllers\Api;

use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportsApiController extends BaseApiController
{
    public function index(Request $request)
    {
        [$start, $end] = $this->resolveRange($request);

        $scope = (string) $request->query('scope', 'my');
        if (!in_array($scope, ['my', 'team', 'all'], true)) {
            $scope = 'my';
        }

        $user = $request->user();

        $baseTickets = Ticket::query()->with(['creator', 'holder']);
        $this->applyScope($baseTickets, $scope, $user);

        // Resolved / Closed in selected range
        $resolvedCount = (clone $baseTickets)
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($qq) use ($start, $end) {
                    $qq->whereNotNull('resolved_at')
                        ->whereBetween('resolved_at', [$start, $end]);
                })->orWhere(function ($qq) use ($start, $end) {
                    $qq->whereNull('resolved_at')
                        ->whereNotNull('closed_at')
                        ->whereBetween('closed_at', [$start, $end]);
                });
            })
            ->whereIn('status', ['resolved', 'closed'])
            ->count();

        // Avg response = created_at -> claimed_at
        $avgResponseSeconds = (clone $baseTickets)
            ->whereNotNull('claimed_at')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, claimed_at)) as avg_seconds')
            ->value('avg_seconds');

        $avgResponseSeconds = (int) round($avgResponseSeconds ?? 0);

        // Reopened = resolved -> in_progress or waiting_info in selected range
        $reopenedTicketIds = TicketStatusHistory::query()
            ->whereIn('to_status', ['in_progress', 'waiting_info'])
            ->where('from_status', 'resolved')
            ->whereBetween('changed_at', [$start, $end])
            ->pluck('ticket_id')
            ->unique();

        $reopenedCount = (clone $baseTickets)
            ->whereIn('id', $reopenedTicketIds)
            ->count();

        $reopenRate = $resolvedCount > 0
            ? round(($reopenedCount / $resolvedCount) * 100, 1)
            : 0;

        // SLA Risk / Breach in selected range
        $slaRiskCount = (clone $baseTickets)
            ->whereNotNull('sla_deadline_at')
            ->whereBetween('sla_deadline_at', [$start, $end])
            ->where(function ($q) {
                $q->where(function ($qq) {
                    $qq->whereIn('status', ['new', 'in_progress', 'waiting_info'])
                        ->where('sla_deadline_at', '<', now());
                })->orWhere(function ($qq) {
                    $qq->whereNotNull('resolved_at')
                        ->whereColumn('resolved_at', '>', 'sla_deadline_at');
                })->orWhere(function ($qq) {
                    $qq->whereNull('resolved_at')
                        ->whereNotNull('closed_at')
                        ->whereColumn('closed_at', '>', 'sla_deadline_at');
                });
            })
            ->count();

        // Trend: resolved per day
        $range = (string) $request->query('range', 'this_week');

        $trendLabels = [];
        $trendValues = [];

        if ($range === 'one_year') {
            $trendRows = (clone $baseTickets)
                ->whereIn('status', ['resolved', 'closed'])
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween(DB::raw('COALESCE(resolved_at, closed_at)'), [$start, $end]);
                })
                ->selectRaw('MONTH(COALESCE(resolved_at, closed_at)) as resolved_month, COUNT(*) as total')
                ->groupBy('resolved_month')
                ->orderBy('resolved_month')
                ->get()
                ->keyBy('resolved_month');

            for ($month = 1; $month <= 12; $month++) {
                $trendLabels[] = Carbon::create(null, $month, 1)->format('M');
                $trendValues[] = (int) ($trendRows[$month]->total ?? 0);
            }
        } else {
            $trendDays = $this->makeDayRange($start, $end);

            $trendRows = (clone $baseTickets)
                ->whereIn('status', ['resolved', 'closed'])
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween(DB::raw('COALESCE(resolved_at, closed_at)'), [$start, $end]);
                })
                ->selectRaw('DATE(COALESCE(resolved_at, closed_at)) as resolved_day, COUNT(*) as total')
                ->groupBy('resolved_day')
                ->orderBy('resolved_day')
                ->get()
                ->keyBy('resolved_day');

            foreach ($trendDays as $day) {
                $key = $day->format('Y-m-d');
                $trendLabels[] = $day->format('d M');
                $trendValues[] = (int) ($trendRows[$key]->total ?? 0);
            }
        }

        // Table rows
        $rows = (clone $baseTickets)
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($ticket) {
                $responseSeconds = null;
                if ($ticket->claimed_at) {
                    $responseSeconds = Carbon::parse($ticket->created_at)
                        ->diffInSeconds(Carbon::parse($ticket->claimed_at));
                }

                $slaSeconds = null;
                if ($ticket->sla_deadline_at) {
                    $slaSeconds = now()->diffInSeconds(Carbon::parse($ticket->sla_deadline_at), false);
                }

                $result = 'Open';

                if (in_array($ticket->status, ['resolved', 'closed'], true)) {
                    $breached = false;

                    if ($ticket->resolved_at && $ticket->sla_deadline_at) {
                        $breached = Carbon::parse($ticket->resolved_at)
                            ->gt(Carbon::parse($ticket->sla_deadline_at));
                    } elseif ($ticket->closed_at && $ticket->sla_deadline_at) {
                        $breached = Carbon::parse($ticket->closed_at)
                            ->gt(Carbon::parse($ticket->sla_deadline_at));
                    }

                    $result = $breached ? 'Breach' : 'OK';
                }

                return [
                    'id' => $ticket->id,
                    'ticket_code' => $ticket->ticket_code ? '#T-' . $ticket->ticket_code : '#T-' . $ticket->id,
                    'status' => $ticket->status,
                    'team' => strtoupper((string) $ticket->team),
                    'sla_time' => $this->formatSecondsAsClock($slaSeconds),
                    'response_time' => $this->formatSecondsAsClock($responseSeconds),
                    'result' => $result,
                ];
            })
            ->values();

        return $this->success([
            'cards' => [
                'resolved' => $resolvedCount,
                'avg_response_seconds' => $avgResponseSeconds,
                'avg_response_label' => $this->formatAvgResponse($avgResponseSeconds),
                'reopen_rate' => $reopenRate,
                'sla_risk' => $slaRiskCount,
            ],
            'trend' => [
                'labels' => $trendLabels,
                'values' => $trendValues,
            ],
            'rows' => $rows,
            'meta' => [
                'scope' => $scope,
                'range' => [
                    'start' => $start->toDateString(),
                    'end' => $end->toDateString(),
                ],
            ],
        ], 'Reports loaded');
    }

    public function export(Request $request)
    {
        $data = $this->index($request)->getData(true);
        $rows = $data['data']['rows'] ?? [];

        $filename = 'reports_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Ticket', 'Status', 'Team', 'SLA Time', 'Response Time', 'Result']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['ticket_code'] ?? '',
                    $row['status'] ?? '',
                    $row['team'] ?? '',
                    $row['sla_time'] ?? '',
                    $row['response_time'] ?? '',
                    $row['result'] ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function resolveRange(Request $request): array
    {
        $range = (string) $request->query('range', 'this_week');
        $customFrom = (string) $request->query('date_from', '');
        $customTo   = (string) $request->query('date_to', '');

        return match ($range) {
            '7d' => [now()->copy()->subDays(6)->startOfDay(), now()->copy()->endOfDay()],
            '30d' => [now()->copy()->subDays(29)->startOfDay(), now()->copy()->endOfDay()],
            'this_month' => [now()->copy()->startOfMonth(), now()->copy()->endOfDay()],
            'one_year' => [now()->copy()->startOfYear(), now()->copy()->endOfDay()],
            'custom' => [
                $customFrom !== '' ? Carbon::parse($customFrom)->startOfDay() : now()->copy()->startOfWeek(),
                $customTo !== '' ? Carbon::parse($customTo)->endOfDay() : now()->copy()->endOfDay(),
            ],
            default => [now()->copy()->startOfWeek(), now()->copy()->endOfDay()],
        };
    }

    protected function applyScope($query, string $scope, $user): void
    {
        if ($scope === 'all' && $user->role === 'admin') {
            return;
        }

        if ($scope === 'team') {
            if ($user->role === 'it') {
                $query->where('team', 'it');
                return;
            }

            if (in_array($user->role, ['cs', 'admin'], true)) {
                return;
            }
        }

        if ($user->role === 'it') {
            $query->where('holder_id', $user->id);
            return;
        }

        $query->where('created_by', $user->id);
    }

    protected function makeDayRange(Carbon $start, Carbon $end): Collection
    {
        $days = collect();
        $cursor = $start->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $days->push($cursor->copy());
            $cursor->addDay();
        }

        return $days;
    }

    protected function formatAvgResponse(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0m';
        }

        $minutes = round($seconds / 60);

        if ($minutes < 60) {
            return $minutes . 'm';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return $hours . 'h';
        }

        return $hours . 'h ' . $remainingMinutes . 'm';
    }

    protected function formatSecondsAsClock(?int $seconds): string
    {
        if ($seconds === null) {
            return '-';
        }

        $negative = $seconds < 0;
        $seconds = abs($seconds);

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $formatted = str_pad((string) $hours, 2, '0', STR_PAD_LEFT)
            . ':'
            . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT);

        return $negative ? '-' . $formatted : $formatted;
    }
}
