<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ExportDataJob;
use App\Queries\TicketHistoryQuery;
use App\Models\Ticket;
use App\Services\TicketWorkflowService;
use App\Services\Tickets\TicketQueuePayloadService;
use App\Http\Resources\TicketResource;
use App\Support\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Support\ExportBatchAccess;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Bus;

/**
 * Exposes queue and history endpoints used by IT dashboard, My Queue, Team Queue, and export flows.
 */
class ITQueueApiController extends BaseApiController
{
    public function __construct(
        private TicketWorkflowService $ticketWorkflowService,
        private TicketHistoryQuery $ticketHistoryQuery,
        private TicketQueuePayloadService $queuePayloadService
    ) {
    }

    // ========= QUEUE PAYLOADS =========

    public function myQueue(Request $request)
    {
        Gate::authorize('viewAny', Ticket::class);

        return $this->success(
            $this->queuePayloadService->myQueue($request->user(), $request),
            'My queue loaded'
        );
    }

    public function teamQueue(Request $request)
    {
        Gate::authorize('viewAny', Ticket::class);

        return $this->success(
            $this->queuePayloadService->teamQueue($request),
            'Team queue loaded'
        );
    }

    // ========= HISTORY AND EXPORT =========

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
            return $this->validationError([], 'Invalid export format');
        }

        $extension = $format === 'pdf' ? 'pdf' : ($format === 'csv' ? 'csv' : 'xls');
        $filename = 'ticket-history-' . now()->format('Ymd-His') . '-' . Str::lower(Str::random(6)) . '.' . $extension;
        $storagePath = 'exports/ticket-history/' . $filename;
        $user = $request->user();

        $batch = Bus::batch([
            new ExportDataJob('ticket_history_' . ($format === 'xls' ? 'excel' : $format), $user->id, $this->ticketHistoryQuery->filtersFromRequest($request), $filename),
        ])->name(ExportBatchAccess::batchName('ticket-history', $user->id, $storagePath, $filename))->dispatch();

        return $this->acceptedResponse([
            'queued' => true,
            'batch_id' => $batch->id,
            'filename' => $filename,
            'storage_disk' => 'local',
            'storage_path' => $storagePath,
        ], 'Ticket history export has been queued.');
    }

    // ========= QUEUE ACTIONS =========

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


}