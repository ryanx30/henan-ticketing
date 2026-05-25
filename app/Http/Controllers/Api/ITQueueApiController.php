<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ExportDataJob;
use App\Queries\TicketHistoryQuery;
use App\Models\Ticket;
use App\Services\TicketWorkflowService;
use App\Http\Resources\TicketResource;
use App\Support\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Bus;

class ITQueueApiController extends BaseApiController
{
    private const PDF_EXPORT_LIMIT = 1000;
    private const PDF_ASYNC_EXPORT_LIMIT = 10000;
    private const DEFAULT_QUEUE_LIMIT = 50;
    private const MAX_QUEUE_LIMIT = 100;

    public function __construct(
        private TicketWorkflowService $ticketWorkflowService,
        private TicketHistoryQuery $ticketHistoryQuery
    ) {
    }

    public function myQueue(Request $request)
    {
        Gate::authorize('viewAny', Ticket::class);

        $userId = $request->user()->id;

        $limit = $this->queueLimit($request);

        $baseQuery = Ticket::with(['creator', 'holder', 'teamMaster', 'priorityMaster'])
            ->forTeamCode('it')
            ->where('holder_id', $userId);

        // My Queue keeps an action-oriented unclaimed section so IT can claim new tickets
        // without leaving the page. Claimed work still remains scoped by holder_id below.
        $newTickets = Ticket::with(['creator', 'holder', 'teamMaster', 'priorityMaster'])
            ->forTeamCode('it')
            ->where('status', 'new')
            ->whereNull('holder_id')
            ->latest()
            ->take($limit)
            ->get();

        $ongoingTickets = (clone $baseQuery)
            ->where('status', 'in_progress')
            ->latest()
            ->take($limit)
            ->get();

        $waitingTickets = (clone $baseQuery)
            ->where('status', 'waiting_info')
            ->latest()
            ->take($limit)
            ->get();

        $resolvedTickets = (clone $baseQuery)
            ->whereIn('status', ['resolved', 'closed'])
            ->latest()
            ->take($limit)
            ->get();

        return $this->success([
            'new_tickets' => TicketResource::collection($newTickets),
            'ongoing_tickets' => TicketResource::collection($ongoingTickets),
            'waiting_tickets' => TicketResource::collection($waitingTickets),
            'resolved_tickets' => TicketResource::collection($resolvedTickets),
            'meta' => [
                'per_section_limit' => $limit,
            ],
        ], 'My queue loaded');
    }

    public function teamQueue(Request $request)
    {
        Gate::authorize('viewAny', Ticket::class);

        $limit = $this->queueLimit($request);

        $baseQuery = Ticket::with(['creator', 'holder', 'teamMaster', 'priorityMaster'])
            ->forTeamCode('it');

        $newTickets = (clone $baseQuery)
            ->where('status', 'new')
            ->whereNull('holder_id')
            ->latest()
            ->take($limit)
            ->get();

        $ongoingTickets = (clone $baseQuery)
            ->where('status', 'in_progress')
            ->latest()
            ->take($limit)
            ->get();

        $waitingTickets = (clone $baseQuery)
            ->where('status', 'waiting_info')
            ->latest()
            ->take($limit)
            ->get();

        $resolvedTickets = (clone $baseQuery)
            ->whereIn('status', ['resolved', 'closed'])
            ->latest()
            ->take($limit)
            ->get();

        return $this->success([
            'new_tickets' => TicketResource::collection($newTickets),
            'ongoing_tickets' => TicketResource::collection($ongoingTickets),
            'waiting_tickets' => TicketResource::collection($waitingTickets),
            'resolved_tickets' => TicketResource::collection($resolvedTickets),
            'meta' => [
                'per_section_limit' => $limit,
            ],
        ], 'Team queue loaded');
    }

    private function queueLimit(Request $request): int
    {
        $limit = (int) $request->query('limit', self::DEFAULT_QUEUE_LIMIT);

        if ($limit <= 0) {
            return self::DEFAULT_QUEUE_LIMIT;
        }

        return min($limit, self::MAX_QUEUE_LIMIT);
    }

    public function history(Request $request)
    {
        Gate::authorize('viewAny', Ticket::class);

        $perPage = (int) $request->query('per_page', 10);

        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        $tickets = $this->ticketHistoryQuery->build($request)
            ->paginate($perPage)
            ->withQueryString();

        $tickets->setCollection(
            TicketResource::collection($tickets->getCollection())->collection
        );

        return $this->paginated($tickets, 'History loaded');
    }

    public function exportHistory(Request $request)
    {
        Gate::authorize('viewAny', Ticket::class);

        $format = strtolower((string) $request->query('format', 'csv'));

        if (!in_array($format, ['csv', 'excel', 'xls', 'pdf'], true)) {
            return $this->error('Invalid export format', 422);
        }

        $extension = $format === 'pdf' ? 'pdf' : ($format === 'csv' ? 'csv' : 'xls');
        $filename = 'ticket-history-' . now()->format('Ymd-His') . '-' . Str::lower(Str::random(6)) . '.' . $extension;

        $batch = Bus::batch([
            new ExportDataJob('ticket_history_' . ($format === 'xls' ? 'excel' : $format), $request->user()->id, $this->historyExportFilters($request), $filename),
        ])->name('ticket-history-export-' . $filename)->dispatch();

        return $this->success([
            'queued' => true,
            'batch_id' => $batch->id,
            'filename' => $filename,
            'storage_disk' => 'local',
            'storage_path' => 'exports/ticket-history/' . $filename,
        ], 'Ticket history export has been queued.', 202);
    }

    private function historyExportFilters(Request $request): array
    {
        return [
            'q' => (string) $request->query('q', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'sort_by' => (string) $request->query('sort_by', 'resolved_at'),
            'sort_dir' => (string) $request->query('sort_dir', 'desc'),
        ];
    }

    public function claim(Request $request, Ticket $ticket)
    {
        Gate::authorize('claim', $ticket);

        $freshTicket = $this->ticketWorkflowService->claim(
            $ticket,
            $request->user(),
            'Ticket claimed by IT.',
            [
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]
        );

        return $this->success(
            TicketResource::make($freshTicket),
            'Ticket claimed successfully'
        );
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        Gate::authorize('updateStatus', $ticket);

        $validated = $request->validate([
            'status' => ['required', TicketStatus::validationRule()],
            'note' => ['nullable', 'string'],
        ]);

        $freshTicket = $this->ticketWorkflowService->transition(
            $ticket,
            $validated['status'],
            $request->user(),
            $validated['note'] ?? 'Status updated by IT.',
            [
                'action' => 'status_changed',
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]
        );

        return $this->success(
            TicketResource::make($freshTicket),
            'Ticket status updated successfully'
        );
    }

    protected function snapshotTicket(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'ticket_code' => $ticket->ticket_code,
            'title' => $ticket->title,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'team' => $ticket->team,
            'holder_id' => $ticket->holder_id,
            'claimed_at' => optional($ticket->claimed_at)?->toISOString(),
            'resolved_at' => optional($ticket->resolved_at)?->toISOString(),
            'closed_at' => optional($ticket->closed_at)?->toISOString(),
        ];
    }

    public function ticketLabel(Ticket $ticket): string
    {
        $ticketNumber = $ticket->ticket_code ?: $ticket->id;

        $cleanCode = preg_replace('/[\s#]+/', '', (string) $ticketNumber);
        $cleanCode = preg_replace('/^T-?/i', '', $cleanCode);

        return $cleanCode ? 'T-' . $cleanCode : '-';
    }

    public function resolvedLabel(Ticket $ticket): string
    {
        $value = $ticket->resolved_at ?: $ticket->closed_at ?: $ticket->updated_at ?: $ticket->created_at;

        if (!$value) {
            return '-';
        }

        return \Carbon\Carbon::parse($value)->format('d M Y, H:i');
    }

    public function categoryLabel(Ticket $ticket): string
    {
        if (!$ticket->category) {
            return '-';
        }

        return str($ticket->category)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function resolutionLabel(Ticket $ticket): string
    {
        if ($ticket->issue_type) {
            return str($ticket->issue_type)
                ->replace('_', ' ')
                ->title()
                ->toString();
        }

        return $ticket->title ?: '-';
    }

    public function durationText(Ticket $ticket): string
    {
        $start = $ticket->created_at ? \Carbon\Carbon::parse($ticket->created_at) : null;
        $endValue = $ticket->resolved_at ?: $ticket->closed_at ?: $ticket->updated_at;

        if (!$start || !$endValue) {
            return '-';
        }

        $end = \Carbon\Carbon::parse($endValue);
        $seconds = abs($start->diffInSeconds($end));

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($days > 0) {
            return "{$days}d {$hours}h {$minutes}m {$remainingSeconds}s";
        }

        if ($hours > 0) {
            return "{$hours}h {$minutes}m {$remainingSeconds}s";
        }

        if ($minutes > 0) {
            return "{$minutes}m {$remainingSeconds}s";
        }

        return "{$remainingSeconds}s";
    }

    public function slaBadge(Ticket $ticket): string
    {
        $endValue = $ticket->resolved_at ?: $ticket->closed_at;

        if (!$ticket->sla_deadline_at || !$endValue) {
            return '';
        }

        return \Carbon\Carbon::parse($endValue)->lte(\Carbon\Carbon::parse($ticket->sla_deadline_at))
            ? 'Met'
            : 'Breached';
    }
}