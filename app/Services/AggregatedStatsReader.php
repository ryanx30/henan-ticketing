<?php

namespace App\Services;

use App\Models\DailyTicketStat;
use App\Models\Priority;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

/**
 * Reads pre-aggregated ticket stats and falls back to live queries when needed.
 */
class AggregatedStatsReader
{
    /**
     * Determine whether the requested range can use snapshots.
     */
    public function canUsePreAggregated(Carbon $start, Carbon $end): bool
    {
        $latestSnapshotDate = now()->subDay()->toDateString();
        $rangeDays = $start->diffInDays($end);

        if ($rangeDays < 7) {
            return false;
        }

        if ($end->toDateString() > $latestSnapshotDate) {
            return false;
        }

        return $this->snapshotCoverage($start, $end) >= 0.8;
    }

    /**
     * Return snapshot coverage ratio for the range.
     */
    public function snapshotCoverage(Carbon $start, Carbon $end): float
    {
        $totalDays = max(1, $start->diffInDays($end) + 1);

        $coveredDays = DailyTicketStat::query()
            ->whereBetween('stat_date', [$start->toDateString(), $end->toDateString()])
            ->whereNull('team_id')
            ->whereNull('priority_id')
            ->distinct('stat_date')
            ->count('stat_date');

        return $coveredDays / $totalDays;
    }

    // -------------------------------------------------------------------------
    // Summary (KPI cards)
    // -------------------------------------------------------------------------

    public function summary(
        Carbon $start,
        Carbon $end,
        ?int $teamId = null,
        ?int $priorityId = null
    ): array {
        if ($this->canUsePreAggregated($start, $end)) {
            return $this->summaryFromStats($start, $end, $teamId, $priorityId);
        }

        return $this->summaryLive($start, $end, $teamId, $priorityId);
    }

    /**
     * Build chart trend data grouped by day or month.
     *
     * @return array<int, array{label: string, created: int, resolved: int, closed: int}>
     */
    public function trend(
        Carbon $start,
        Carbon $end,
        string $groupBy = 'day',
        ?int $teamId = null,
        ?int $priorityId = null
    ): array {
        if ($this->canUsePreAggregated($start, $end)) {
            return $this->trendFromStats($start, $end, $groupBy, $teamId, $priorityId);
        }

        return $this->trendLive($start, $end, $groupBy, $teamId, $priorityId);
    }

    // -------------------------------------------------------------------------
    // Pre-aggregated implementations
    // -------------------------------------------------------------------------

    private function summaryFromStats(
        Carbon $start,
        Carbon $end,
        ?int $teamId,
        ?int $priorityId
    ): array {
        $rows = DailyTicketStat::query()
            ->whereBetween('stat_date', [$start->toDateString(), $end->toDateString()])
            ->where('team_id', $teamId)
            ->where('priority_id', $priorityId)
            ->get([
                'tickets_created',
                'tickets_resolved',
                'tickets_closed',
                'tickets_reopened',
                'sla_breached',
                'sla_met',
                'first_response_seconds_sum',
                'first_response_count',
                'resolution_seconds_sum',
                'resolution_count',
            ]);

        $frCount = (int) $rows->sum('first_response_count');
        $resCount = (int) $rows->sum('resolution_count');

        return [
            'tickets_created'            => (int) $rows->sum('tickets_created'),
            'tickets_resolved'           => (int) $rows->sum('tickets_resolved'),
            'tickets_closed'             => (int) $rows->sum('tickets_closed'),
            'tickets_reopened'           => (int) $rows->sum('tickets_reopened'),
            'sla_breached'               => (int) $rows->sum('sla_breached'),
            'sla_met'                    => (int) $rows->sum('sla_met'),
            'avg_first_response_seconds' => $frCount > 0
                ? (int) round($rows->sum('first_response_seconds_sum') / $frCount)
                : null,
            'avg_resolution_seconds'     => $resCount > 0
                ? (int) round($rows->sum('resolution_seconds_sum') / $resCount)
                : null,
            'source'                     => 'pre_aggregated',
        ];
    }

    private function trendFromStats(
        Carbon $start,
        Carbon $end,
        string $groupBy,
        ?int $teamId,
        ?int $priorityId
    ): array {
        $rows = DailyTicketStat::query()
            ->whereBetween('stat_date', [$start->toDateString(), $end->toDateString()])
            ->where('team_id', $teamId)
            ->where('priority_id', $priorityId)
            ->get(['stat_date', 'tickets_created', 'tickets_resolved', 'tickets_closed']);

        return $rows
            ->groupBy(fn (DailyTicketStat $stat) => $this->periodLabel(Carbon::parse($stat->stat_date), $groupBy))
            ->map(fn ($group, string $label) => [
                'label' => $label,
                'created' => (int) $group->sum('tickets_created'),
                'resolved' => (int) $group->sum('tickets_resolved'),
                'closed' => (int) $group->sum('tickets_closed'),
            ])
            ->sortKeys()
            ->values()
            ->all();
    }

    // -------------------------------------------------------------------------
    // Live query fallback
    // -------------------------------------------------------------------------

    private function summaryLive(
        Carbon $start,
        Carbon $end,
        ?int $teamId,
        ?int $priorityId
    ): array {
        $base = Ticket::query()->withoutGlobalScopes();
        $this->applyDimension($base, $teamId, $priorityId);

        $created  = (clone $base)->whereBetween('created_at', [$start, $end])->count();
        $resolved = (clone $base)->where('status', 'resolved')->whereBetween('resolved_at', [$start, $end])->count();
        $closed   = (clone $base)->where('status', 'closed')->whereBetween('closed_at', [$start, $end])->count();

        $reopened = TicketStatusHistory::query()
            ->whereIn('to_status', ['in_progress', 'waiting_info'])
            ->where('from_status', 'resolved')
            ->whereBetween('changed_at', [$start, $end])
            ->distinct('ticket_id')->count('ticket_id');

        $slaBreached = (clone $base)
            ->whereNotNull('sla_deadline_at')
            ->whereIn('status', ['resolved', 'closed'])
            ->whereColumn('resolved_at', '>', 'sla_deadline_at')
            ->where(fn($q) => $q
                ->whereBetween('resolved_at', [$start, $end])
                ->orWhereBetween('closed_at', [$start, $end]))
            ->count();

        $slaMet = (clone $base)
            ->whereNotNull('sla_deadline_at')
            ->whereIn('status', ['resolved', 'closed'])
            ->where(fn($q) => $q->whereNull('resolved_at')->orWhereColumn('resolved_at', '<=', 'sla_deadline_at'))
            ->where(fn($q) => $q
                ->whereBetween('resolved_at', [$start, $end])
                ->orWhereBetween('closed_at', [$start, $end]))
            ->count();

        $frRaw = (clone $base)
            ->whereNotNull('claimed_at')
            ->whereBetween('created_at', [$start, $end])
            ->get(['created_at', 'claimed_at'])
            ->avg(fn (Ticket $ticket) => Carbon::parse($ticket->created_at)->diffInSeconds(Carbon::parse($ticket->claimed_at)));

        $resRaw = (clone $base)
            ->whereIn('status', ['resolved', 'closed'])
            ->where(fn($q) => $q
                ->whereBetween('resolved_at', [$start, $end])
                ->orWhereBetween('closed_at', [$start, $end]))
            ->get(['created_at', 'resolved_at', 'closed_at'])
            ->avg(function (Ticket $ticket) {
                $completedAt = $ticket->resolved_at ?: $ticket->closed_at;

                return $completedAt
                    ? Carbon::parse($ticket->created_at)->diffInSeconds(Carbon::parse($completedAt))
                    : null;
            });

        return [
            'tickets_created'            => $created,
            'tickets_resolved'           => $resolved,
            'tickets_closed'             => $closed,
            'tickets_reopened'           => $reopened,
            'sla_breached'               => $slaBreached,
            'sla_met'                    => $slaMet,
            'avg_first_response_seconds' => $frRaw !== null ? (int) round($frRaw) : null,
            'avg_resolution_seconds'     => $resRaw !== null ? (int) round($resRaw) : null,
            'source'                     => 'live',
        ];
    }

    private function trendLive(
        Carbon $start,
        Carbon $end,
        string $groupBy,
        ?int $teamId,
        ?int $priorityId
    ): array {
        $base = Ticket::query()->withoutGlobalScopes();
        $this->applyDimension($base, $teamId, $priorityId);

        $tickets = (clone $base)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end])
                    ->orWhereBetween('resolved_at', [$start, $end])
                    ->orWhereBetween('closed_at', [$start, $end]);
            })
            ->get(['status', 'created_at', 'resolved_at', 'closed_at']);

        $buckets = [];

        foreach ($tickets as $ticket) {
            if ($ticket->created_at && Carbon::parse($ticket->created_at)->betweenIncluded($start, $end)) {
                $label = $this->periodLabel(Carbon::parse($ticket->created_at), $groupBy);
                $buckets[$label]['created'] = ($buckets[$label]['created'] ?? 0) + 1;
            }

            if ($ticket->status === 'resolved' && $ticket->resolved_at && Carbon::parse($ticket->resolved_at)->betweenIncluded($start, $end)) {
                $label = $this->periodLabel(Carbon::parse($ticket->resolved_at), $groupBy);
                $buckets[$label]['resolved'] = ($buckets[$label]['resolved'] ?? 0) + 1;
            }

            if ($ticket->status === 'closed' && $ticket->closed_at && Carbon::parse($ticket->closed_at)->betweenIncluded($start, $end)) {
                $label = $this->periodLabel(Carbon::parse($ticket->closed_at), $groupBy);
                $buckets[$label]['closed'] = ($buckets[$label]['closed'] ?? 0) + 1;
            }
        }

        ksort($buckets);

        return collect($buckets)
            ->map(fn (array $row, string $label) => [
                'label' => $label,
                'created' => (int) ($row['created'] ?? 0),
                'resolved' => (int) ($row['resolved'] ?? 0),
                'closed' => (int) ($row['closed'] ?? 0),
            ])
            ->values()
            ->all();
    }

    private function periodLabel(Carbon $date, string $groupBy): string
    {
        return $groupBy === 'month'
            ? $date->format('Y-m')
            : $date->format('Y-m-d');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function applyDimension(Builder $query, ?int $teamId, ?int $priorityId): void
    {
        if ($teamId !== null) {
            $team = Team::find($teamId);
            $query->where(function ($q) use ($teamId, $team) {
                $q->where('team_id', $teamId);
                if ($team !== null) {
                    $q->orWhere(fn($q2) => $q2->whereNull('team_id')->where('team', $team->code));
                }
            });
        }

        if ($priorityId !== null) {
            $priority = Priority::find($priorityId);
            $query->where(function ($q) use ($priorityId, $priority) {
                $q->where('priority_id', $priorityId);
                if ($priority !== null) {
                    $q->orWhere(fn($q2) => $q2->whereNull('priority_id')->where('priority', $priority->code));
                }
            });
        }
    }
}
