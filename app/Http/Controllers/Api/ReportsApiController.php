<?php

namespace App\Http\Controllers\Api;

use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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

        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        $user = $request->user();

        $baseTickets = Ticket::query()->with(['creator', 'holder']);
        $this->applyScope($baseTickets, $scope, $user);

        $resolvedClosedCount = (clone $baseTickets)
            ->whereIn('status', ['resolved', 'closed'])
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
            ->count();

        $itTickets = (clone $baseTickets)->where('team', 'it');

        $avgResponseSeconds = (clone $itTickets)
            ->whereNotNull('claimed_at')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, claimed_at)) as avg_seconds')
            ->value('avg_seconds');

        $avgResponseSeconds = (int) round($avgResponseSeconds ?? 0);

        $reopenedTicketIds = TicketStatusHistory::query()
            ->whereIn('to_status', ['in_progress', 'waiting_info'])
            ->where('from_status', 'resolved')
            ->whereBetween('changed_at', [$start, $end])
            ->pluck('ticket_id')
            ->unique();

        $resolvedItCount = (clone $itTickets)
            ->whereIn('status', ['resolved', 'closed'])
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
            ->count();

        $reopenedCount = (clone $itTickets)
            ->whereIn('id', $reopenedTicketIds)
            ->count();

        $reopenRate = $resolvedItCount > 0
            ? round(($reopenedCount / $resolvedItCount) * 100, 1)
            : 0;

        $slaRiskCount = (clone $itTickets)
            ->whereNotNull('sla_deadline_at')
            ->where(function ($q) {
                $q->whereIn('status', ['new', 'in_progress', 'waiting_info'])
                    ->where('sla_deadline_at', '<=', now());
            })
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $trendLabels = [];
        $trendValues = [];

        $range = (string) $request->query('range', 'this_week');

        if ($range === 'one_year') {
            $months = $this->makeMonthRange($start, $end);

            $trendRows = (clone $baseTickets)
                ->whereIn('status', ['resolved', 'closed'])
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween(DB::raw('COALESCE(resolved_at, closed_at)'), [$start, $end]);
                })
                ->selectRaw("DATE_FORMAT(COALESCE(resolved_at, closed_at), '%Y-%m') as resolved_month, COUNT(*) as total")
                ->groupBy('resolved_month')
                ->orderBy('resolved_month')
                ->get()
                ->keyBy('resolved_month');

            foreach ($months as $month) {
                $key = $month->format('Y-m');
                $trendLabels[] = $month->format('M Y');
                $trendValues[] = (int) ($trendRows[$key]->total ?? 0);
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

        $rowsQuery = (clone $baseTickets)
            ->whereBetween('created_at', [$start, $end])
            ->latest();

        $rowsPaginator = $rowsQuery
            ->paginate($perPage)
            ->withQueryString();

        $rows = $rowsPaginator->getCollection()
            ->map(fn ($ticket) => $this->mapTicketRow($ticket))
            ->values();

        return $this->success([
            'cards' => [
                'resolved' => $resolvedClosedCount,
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
        ], 'Reports loaded');
    }

    public function export(Request $request)
    {
        [$start, $end] = $this->resolveRange($request);

        $scope = (string) $request->query('scope', 'my');
        if (!in_array($scope, ['my', 'team', 'all'], true)) {
            $scope = 'my';
        }

        $user = $request->user();

        $baseTickets = Ticket::query()->with(['creator', 'holder']);
        $this->applyScope($baseTickets, $scope, $user);

        $exportQuery = (clone $baseTickets)
            ->whereBetween('created_at', [$start, $end])
            ->latest();

        $filename = 'reports_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($exportQuery) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Ticket',
                'Status',
                'Team',
                'SLA Remaining / Outcome',
                'Response Time',
                'SLA Result',
            ]);

            $exportQuery->chunk(500, function (Collection $tickets) use ($handle) {
                foreach ($tickets as $ticket) {
                    $row = $this->mapTicketRow($ticket);

                    fputcsv($handle, [
                        $row['ticket_code'] ?? '',
                        $row['status'] ?? '',
                        $row['team'] ?? '',
                        $row['sla_time'] ?? '',
                        $row['response_time'] ?? '',
                        $row['result'] ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function mapTicketRow(Ticket $ticket): array
    {
        $responseSeconds = null;

        if ($ticket->team === 'it' && $ticket->claimed_at) {
            $responseSeconds = Carbon::parse($ticket->created_at)
                ->diffInSeconds(Carbon::parse($ticket->claimed_at));
        }

        $slaSnapshot = $this->buildSlaSnapshot($ticket);

        return [
            'id' => $ticket->id,
            'ticket_code' => $ticket->ticket_code ? 'T-' . $ticket->ticket_code : 'T-' . $ticket->id,
            'status' => $ticket->status,
            'team' => strtoupper((string) $ticket->team),
            'sla_time' => $slaSnapshot['sla_time'],
            'response_time' => $ticket->team === 'it'
                ? $this->formatHumanDuration($responseSeconds)
                : 'N/A',
            'result' => $slaSnapshot['result'],
        ];
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
            'one_year' => [now()->copy()->subMonths(11)->startOfMonth(), now()->copy()->endOfDay()],
            'custom' => [
                $customFrom !== '' ? Carbon::parse($customFrom)->startOfDay() : now()->copy()->startOfWeek(),
                $customTo !== '' ? Carbon::parse($customTo)->endOfDay() : now()->copy()->endOfDay(),
            ],
            default => [now()->copy()->startOfWeek(), now()->copy()->endOfDay()],
        };
    }

    protected function applyScope(Builder $query, string $scope, User $user): void
    {
        if ($user->role === 'it') {
            if ($scope === 'team') {
                $query->where('team', 'it');
                return;
            }

            if ($scope === 'all' && in_array($user->role, ['admin', 'supervisor'], true)) {
                return;
            }

            $query->where('holder_id', $user->id);
            return;
        }

        if ($scope === 'all' && in_array($user->role, ['admin', 'supervisor'], true)) {
            return;
        }

        if ($scope === 'team') {
            $csUserIds = User::query()
                ->where('role', 'cs')
                ->pluck('id');

            $query->whereIn('created_by', $csUserIds);
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
        if ($ticket->team !== 'it') {
            if (in_array($ticket->status, ['resolved', 'closed'], true)) {
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

        if (!$ticket->sla_deadline_at) {
            return [
                'sla_time' => 'No SLA',
                'result' => 'Open',
            ];
        }

        $deadline = Carbon::parse($ticket->sla_deadline_at);
        $completedAt = $this->resolveCompletedAt($ticket);
        $isCompleted = in_array($ticket->status, ['resolved', 'closed'], true) && $completedAt !== null;

        if ($isCompleted) {
            $diffSeconds = $completedAt->diffInSeconds($deadline, false);

            if ($diffSeconds >= 0) {
                return [
                    'sla_time' => 'Met by ' . $this->formatHumanDuration($diffSeconds),
                    'result' => 'OK',
                ];
            }

            return [
                'sla_time' => 'Breached by ' . $this->formatHumanDuration(abs($diffSeconds)),
                'result' => 'Breach',
            ];
        }

        $remainingSeconds = now()->diffInSeconds($deadline, false);

        if ($remainingSeconds >= 0) {
            return [
                'sla_time' => $this->formatHumanDuration($remainingSeconds) . ' left',
                'result' => 'Open',
            ];
        }

        return [
            'sla_time' => 'Overdue ' . $this->formatHumanDuration(abs($remainingSeconds)),
            'result' => 'Breach',
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

        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $remainingMinutes = $minutes % 60;

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

        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $remainingMinutes = $minutes % 60;

        if ($days > 0) {
            if ($hours > 0) {
                return $days . 'd ' . $hours . 'h';
            }

            return $days . 'd';
        }

        if ($hours > 0) {
            if ($remainingMinutes > 0) {
                return $hours . 'h ' . $remainingMinutes . 'm';
            }

            return $hours . 'h';
        }

        return $remainingMinutes . 'm';
    }
}