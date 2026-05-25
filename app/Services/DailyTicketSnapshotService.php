<?php

namespace App\Services;

use App\Models\DailyTicketStat;
use App\Models\Priority;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DailyTicketSnapshotService
{
    /**
     * Generate (atau re-generate) snapshot untuk satu tanggal.
     * Aman dijalankan berkali-kali — memakai updateOrCreate (upsert).
     *
     * @return int Jumlah baris dimensi yang diproses
     */
    public function snapshot(Carbon $date): int
    {
        $dayStart = $date->copy()->startOfDay();
        $dayEnd   = $date->copy()->endOfDay();

        $teams      = Team::all()->keyBy('id');
        $priorities = Priority::all()->keyBy('id');

        $dimensions = $this->buildDimensions($teams, $priorities);
        $count = 0;

        foreach ($dimensions as [$teamId, $priorityId]) {
            $row = $this->computeRow($dayStart, $dayEnd, $teamId, $priorityId);

            DailyTicketStat::updateOrCreate(
                [
                    'stat_date'   => $date->toDateString(),
                    'team_id'     => $teamId,
                    'priority_id' => $priorityId,
                ],
                $row
            );

            $count++;
        }

        return $count;
    }

    // -------------------------------------------------------------------------
    // Private: dimensi kombinasi team × priority
    // -------------------------------------------------------------------------

    /**
     * @return array<int, array{0: int|null, 1: int|null}>
     */
    private function buildDimensions(Collection $teams, Collection $priorities): array
    {
        $dimensions = [];

        // Aggregate: semua team, semua priority
        $dimensions[] = [null, null];

        // Per team, semua priority
        foreach ($teams as $team) {
            $dimensions[] = [$team->id, null];
        }

        // Semua team, per priority
        foreach ($priorities as $priority) {
            $dimensions[] = [null, $priority->id];
        }

        // Per team × per priority
        foreach ($teams as $team) {
            foreach ($priorities as $priority) {
                $dimensions[] = [$team->id, $priority->id];
            }
        }

        return $dimensions;
    }

    // -------------------------------------------------------------------------
    // Private: hitung satu baris snapshot
    // -------------------------------------------------------------------------

    private function computeRow(
        Carbon $dayStart,
        Carbon $dayEnd,
        ?int $teamId,
        ?int $priorityId
    ): array {
        // --- Tickets created hari ini ---
        $created = $this->baseQuery($teamId, $priorityId)
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->count();

        // --- Tickets resolved hari ini ---
        $resolved = $this->baseQuery($teamId, $priorityId)
            ->where('status', 'resolved')
            ->whereBetween('resolved_at', [$dayStart, $dayEnd])
            ->count();

        // --- Tickets closed hari ini ---
        $closed = $this->baseQuery($teamId, $priorityId)
            ->where('status', 'closed')
            ->whereBetween('closed_at', [$dayStart, $dayEnd])
            ->count();

        // --- Auto-closed: closed oleh system (changed_by null di status history) ---
        $autoClosed = $this->baseQuery($teamId, $priorityId)
            ->where('status', 'closed')
            ->whereBetween('closed_at', [$dayStart, $dayEnd])
            ->whereHas('statusHistories', function ($q) use ($dayStart, $dayEnd) {
                $q->where('to_status', 'closed')
                    ->whereNull('changed_by')
                    ->whereBetween('changed_at', [$dayStart, $dayEnd]);
            })
            ->count();

        // --- Reopened: resolved → in_progress/waiting_info hari ini ---
        $reopenedQuery = TicketStatusHistory::query()
            ->whereIn('to_status', ['in_progress', 'waiting_info'])
            ->where('from_status', 'resolved')
            ->whereBetween('changed_at', [$dayStart, $dayEnd]);

        if ($teamId !== null) {
            $reopenedQuery->whereHas('ticket', fn($tq) => $tq->where('team_id', $teamId));
        }

        if ($priorityId !== null) {
            $reopenedQuery->whereHas('ticket', fn($tq) => $tq->where('priority_id', $priorityId));
        }

        $reopened = $reopenedQuery->distinct('ticket_id')->count('ticket_id');

        // --- SLA breached: selesai hari ini tapi lewat deadline ---
        $slaBreached = $this->baseQuery($teamId, $priorityId)
            ->whereNotNull('sla_deadline_at')
            ->whereIn('status', ['resolved', 'closed'])
            ->where(function ($q) use ($dayStart, $dayEnd) {
                $q->whereBetween('resolved_at', [$dayStart, $dayEnd])
                    ->orWhereBetween('closed_at', [$dayStart, $dayEnd]);
            })
            ->whereColumn('resolved_at', '>', 'sla_deadline_at')
            ->count();

        // --- SLA met: selesai hari ini dalam deadline ---
        $slaMet = $this->baseQuery($teamId, $priorityId)
            ->whereNotNull('sla_deadline_at')
            ->whereIn('status', ['resolved', 'closed'])
            ->where(function ($q) use ($dayStart, $dayEnd) {
                $q->whereBetween('resolved_at', [$dayStart, $dayEnd])
                    ->orWhereBetween('closed_at', [$dayStart, $dayEnd]);
            })
            ->where(function ($q) {
                $q->whereNull('resolved_at')
                    ->orWhereColumn('resolved_at', '<=', 'sla_deadline_at');
            })
            ->count();

        // --- First response time (created → claimed) ---
        $firstResponseRows = $this->baseQuery($teamId, $priorityId)
            ->whereNotNull('claimed_at')
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->get(['created_at', 'claimed_at']);

        $firstResponseSecondsSum = (int) $firstResponseRows
            ->sum(fn (Ticket $ticket) => Carbon::parse($ticket->created_at)->diffInSeconds(Carbon::parse($ticket->claimed_at)));
        $firstResponseCount = $firstResponseRows->count();

        // --- Resolution time (created → resolved/closed) ---
        $resolutionRows = $this->baseQuery($teamId, $priorityId)
            ->whereIn('status', ['resolved', 'closed'])
            ->where(function ($q) use ($dayStart, $dayEnd) {
                $q->whereBetween('resolved_at', [$dayStart, $dayEnd])
                    ->orWhereBetween('closed_at', [$dayStart, $dayEnd]);
            })
            ->get(['created_at', 'resolved_at', 'closed_at']);

        $resolutionSecondsSum = (int) $resolutionRows
            ->sum(function (Ticket $ticket) {
                $completedAt = $ticket->resolved_at ?: $ticket->closed_at;

                return $completedAt
                    ? Carbon::parse($ticket->created_at)->diffInSeconds(Carbon::parse($completedAt))
                    : 0;
            });
        $resolutionCount = $resolutionRows->count();

        // --- Open at end of day ---
        $openAtEndOfDay = $this->baseQuery($teamId, $priorityId)
            ->whereNotIn('status', ['closed'])
            ->where('created_at', '<=', $dayEnd)
            ->count();

        return [
            'tickets_created'            => $created,
            'tickets_resolved'           => $resolved,
            'tickets_closed'             => $closed,
            'tickets_auto_closed'        => $autoClosed,
            'tickets_reopened'           => $reopened,
            'sla_breached'               => $slaBreached,
            'sla_met'                    => $slaMet,
            'first_response_seconds_sum' => $firstResponseSecondsSum,
            'first_response_count'       => $firstResponseCount,
            'resolution_seconds_sum'     => $resolutionSecondsSum,
            'resolution_count'           => $resolutionCount,
            'open_at_end_of_day'         => $openAtEndOfDay,
        ];
    }

    // -------------------------------------------------------------------------
    // Private: base query dengan filter dimensi
    // -------------------------------------------------------------------------

    private function baseQuery(?int $teamId, ?int $priorityId)
    {
        $query = Ticket::query()->withoutGlobalScopes();

        if ($teamId !== null) {
            $team = Team::find($teamId);
            $query->where(function ($q) use ($teamId, $team) {
                $q->where('team_id', $teamId);
                if ($team !== null) {
                    $q->orWhere(function ($q2) use ($team) {
                        $q2->whereNull('team_id')->where('team', $team->code);
                    });
                }
            });
        }

        if ($priorityId !== null) {
            $priority = Priority::find($priorityId);
            $query->where(function ($q) use ($priorityId, $priority) {
                $q->where('priority_id', $priorityId);
                if ($priority !== null) {
                    $q->orWhere(function ($q2) use ($priority) {
                        $q2->whereNull('priority_id')->where('priority', $priority->code);
                    });
                }
            });
        }

        return $query;
    }
}
