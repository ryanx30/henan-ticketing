<?php

namespace App\Services\Tickets;

use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Builds grouped queue payloads for My Queue and Team Queue screens.
 */
final class TicketQueuePayloadService
{
    private const DEFAULT_QUEUE_LIMIT = 50;
    private const MAX_QUEUE_LIMIT = 100;
    private const RESOLVED_PREVIEW_LIMIT = 5;

    public function myQueue(User $user, Request $request): array
    {
        $limit = $this->limit($request);

        $baseQuery = $this->baseItQueueQuery()
            ->where('holder_id', $user->id);

        return $this->payload([
            'new_tickets' => $this->baseItQueueQuery()
                ->where('status', 'new')
                ->whereNull('holder_id'),
            'ongoing_tickets' => (clone $baseQuery)->where('status', 'in_progress'),
            'waiting_tickets' => (clone $baseQuery)->where('status', 'waiting_info'),
            'resolved_tickets' => (clone $baseQuery)->where('status', 'resolved'),
        ], $limit, self::RESOLVED_PREVIEW_LIMIT);
    }

    public function teamQueue(Request $request): array
    {
        $limit = $this->limit($request);
        $baseQuery = $this->baseItQueueQuery();

        return $this->payload([
            'new_tickets' => (clone $baseQuery)
                ->where('status', 'new')
                ->whereNull('holder_id'),
            'ongoing_tickets' => (clone $baseQuery)->where('status', 'in_progress'),
            'waiting_tickets' => (clone $baseQuery)->where('status', 'waiting_info'),
            'resolved_tickets' => (clone $baseQuery)->where('status', 'resolved'),
        ], $limit, self::RESOLVED_PREVIEW_LIMIT);
    }

    public function historyExportFilters(Request $request): array
    {
        return [
            'q' => (string) $request->query('q', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'sort_by' => (string) $request->query('sort_by', 'resolved_at'),
            'sort_dir' => (string) $request->query('sort_dir', 'desc'),
        ];
    }

    private function baseItQueueQuery(): Builder
    {
        return Ticket::with(['creator', 'holder', 'teamMaster', 'priorityMaster'])
            ->forTeamCode('it');
    }

    private function payload(array $sections, int $limit, ?int $resolvedLimit = null): array
    {
        $data = collect($sections)
            ->map(function (Builder $query, string $section) use ($limit, $resolvedLimit) {
                $sectionLimit = $section === 'resolved_tickets' && $resolvedLimit !== null
                    ? $resolvedLimit
                    : $limit;

                return TicketResource::collection(
                    $query->latest()->take($sectionLimit)->get()
                );
            })
            ->all();

        $data['meta'] = [
            'per_section_limit' => $limit,
            'resolved_preview_limit' => $resolvedLimit,
        ];

        return $data;
    }

    private function limit(Request $request): int
    {
        $limit = (int) $request->query('limit', self::DEFAULT_QUEUE_LIMIT);

        if ($limit <= 0) {
            return self::DEFAULT_QUEUE_LIMIT;
        }

        return min($limit, self::MAX_QUEUE_LIMIT);
    }
}
