<?php

namespace App\Services\Tickets;

use App\Http\Resources\TicketDetailResource;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Services\TicketWorkflowService;
use App\Support\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

/**
 * Builds the ticket detail payload with related data, attachments, and permission-aware action flags.
 */
final class TicketDetailPayloadService
{
    public function __construct(
        private TicketWorkflowService $ticketWorkflowService
    ) {
    }

    public function build(Ticket $ticket, Request $request): array
    {
        $ticket->load([
            'creator',
            'holder',
            'client',
            'teamMaster',
            'categoryMaster',
            'issueTypeMaster',
            'priorityMaster',
        ]);

        $history = $ticket->statusHistories()
            ->with('changer')
            ->latest('changed_at')
            ->paginate($this->boundedPerPage($request, 'history_per_page', 10, 50), ['*'], 'history_page');

        $messages = $ticket->resolverMessages()
            ->with(['sender', 'recipient'])
            ->latest()
            ->paginate($this->boundedPerPage($request, 'messages_per_page', 10, 50), ['*'], 'messages_page');

        $attachments = $ticket->attachments()
            ->with('uploader')
            ->latest()
            ->paginate($this->boundedPerPage($request, 'attachments_per_page', 10, 50), ['*'], 'attachments_page');

        $user = $request->user();
        $data = (new TicketDetailResource($ticket))->toArray($request);
        $data['viewer_role'] = $user->role;
        $data['viewer_id'] = $user->id;
        $data['actions'] = $this->actions($ticket, $user);
        $data['status_options'] = $this->statusOptions($ticket);
        $data['handoff'] = $this->handoffPayload($ticket, $user);
        $data['relations'] = [
            'history' => $this->relationPage($history),
            'messages' => $this->relationPage($messages),
            'attachments' => $this->attachmentRelationPage($attachments, $ticket),
        ];

        return $data;
    }

    private function statusOptions(Ticket $ticket): array
    {
        $currentStatus = $this->ticketWorkflowService->normalizeStatus((string) $ticket->status);
        $transitionMap = $this->ticketWorkflowService->transitionMap();

        return collect($transitionMap[$currentStatus] ?? [])
            ->map(fn (string $status) => [
                'value' => $status,
                'label' => TicketStatus::label($status),
            ])
            ->values()
            ->all();
    }

    private function actions(Ticket $ticket, User $user): array
    {
        $isCurrentCsOwner = $user->isCS() && (int) $ticket->created_by === (int) $user->id;
        $isCurrentItHolder = $user->isIT() && (int) $ticket->holder_id === (int) $user->id;

        return [
            'can_update_ticket' => Gate::forUser($user)->allows('update', $ticket),
            'can_update_status' => Gate::forUser($user)->allows('updateStatus', $ticket),
            'can_claim' => Gate::forUser($user)->allows('claim', $ticket),
            'can_escalate' => Gate::forUser($user)->allows('transferHolder', $ticket),
            'can_send_resolver_message' => $user->isAdmin() || $user->isHeadCS() || $isCurrentCsOwner || $isCurrentItHolder,
        ];
    }

    private function handoffPayload(Ticket $ticket, User $user): array
    {
        if (! Gate::forUser($user)->allows('transferHolder', $ticket)) {
            return [
                'mode' => null,
                'current_owner_id' => null,
                'eligible_users' => [],
            ];
        }

        $mode = $user->isCsStaffOrHead() ? 'cs' : 'it';
        $currentOwnerId = $mode === 'cs' ? $ticket->created_by : $ticket->holder_id;

        if ($user->isAdmin()) {
            $mode = $ticket->isTeamCode('it') && $ticket->holder_id ? 'it' : 'cs';
            $currentOwnerId = $mode === 'cs' ? $ticket->created_by : $ticket->holder_id;
        }

        $users = User::query()
            ->where('is_active', true)
            ->where('role', $mode)
            ->whereKeyNot((int) $currentOwnerId)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'is_active'])
            ->map(fn (User $row) => [
                'id' => $row->id,
                'name' => $row->name,
                'email' => $row->email,
                'role' => $row->role,
                'is_active' => (bool) $row->is_active,
            ])
            ->values();

        return [
            'mode' => $mode,
            'current_owner_id' => $currentOwnerId,
            'eligible_users' => $users,
        ];
    }

    private function boundedPerPage(Request $request, string $key, int $default, int $max): int
    {
        $value = (int) $request->query($key, $default);

        if ($value <= 0) {
            return $default;
        }

        return min($value, $max);
    }

    private function attachmentRelationPage(LengthAwarePaginator $paginator, Ticket $ticket): array
    {
        return [
            'data' => collect($paginator->items())->map(fn (TicketAttachment $attachment) => [
                'id' => $attachment->id,
                'ticket_id' => $attachment->ticket_id,
                'file_name' => $attachment->file_name,
                'file_type' => $attachment->file_type,
                'file_size' => $attachment->file_size,
                'uploaded_by' => $attachment->uploaded_by,
                'uploader' => $attachment->relationLoaded('uploader') ? $attachment->uploader : null,
                'created_at' => optional($attachment->created_at)?->toISOString(),
                'download_url' => url('/api/tickets/' . $ticket->id . '/attachments/' . $attachment->id . '/download'),
            ])->values(),
            'meta' => $this->paginationMeta($paginator),
            'links' => $this->paginationLinks($paginator),
        ];
    }

    private function relationPage(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => $paginator->items(),
            'meta' => $this->paginationMeta($paginator),
            'links' => $this->paginationLinks($paginator),
        ];
    }

    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    private function paginationLinks(LengthAwarePaginator $paginator): array
    {
        return [
            'next' => $paginator->nextPageUrl(),
            'prev' => $paginator->previousPageUrl(),
        ];
    }
}
