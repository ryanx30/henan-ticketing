<?php

namespace App\Services;

use App\Http\Resources\TicketDetailResource;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * Builds the ticket detail payload with related data, attachments, and permission-aware action flags.
 */
final class TicketDetailPayloadService
{
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

        $data = (new TicketDetailResource($ticket))->toArray($request);
        $data['relations'] = [
            'history' => $this->historyRelationPage($history),
            'messages' => $this->messageRelationPage($messages, $ticket),
            'attachments' => $this->attachmentRelationPage($attachments, $ticket),
        ];

        return $data;
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
                'uploader' => $this->userSummary($attachment->relationLoaded('uploader') ? $attachment->uploader : null),
                'created_at' => optional($attachment->created_at)?->toISOString(),
                'download_url' => url('/api/tickets/' . $ticket->id . '/attachments/' . $attachment->id . '/download'),
            ])->values(),
            'meta' => $this->paginationMeta($paginator),
            'links' => $this->paginationLinks($paginator),
        ];
    }

    private function historyRelationPage(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => collect($paginator->items())->map(fn ($history) => [
                'id' => $history->id,
                'ticket_id' => $history->ticket_id,
                'from_status' => $history->from_status,
                'to_status' => $history->to_status,
                'changed_at' => optional($history->changed_at)?->toISOString(),
                'note' => $history->note,
                'changer' => $this->userSummary($history->relationLoaded('changer') ? $history->changer : null),
            ])->values(),
            'meta' => $this->paginationMeta($paginator),
            'links' => $this->paginationLinks($paginator),
        ];
    }

    private function messageRelationPage(LengthAwarePaginator $paginator, Ticket $ticket): array
    {
        return [
            'data' => collect($paginator->items())->map(fn ($message) => [
                'id' => $message->id,
                'ticket_id' => $message->ticket_id,
                'from_user_id' => $message->from_user_id,
                'to_user_id' => $message->to_user_id,
                'subject' => $message->subject,
                'body' => $message->body,
                'attachment_name' => $message->attachment_name,
                'is_read' => (bool) $message->is_read,
                'read_at' => optional($message->read_at)?->toISOString(),
                'created_at' => optional($message->created_at)?->toISOString(),
                'sender' => $this->userSummary($message->relationLoaded('sender') ? $message->sender : null),
                'recipient' => $this->userSummary($message->relationLoaded('recipient') ? $message->recipient : null),
                'download_url' => $message->attachment_path
                    ? url('/api/resolver-inbox/' . $message->id . '/attachment')
                    : null,
            ])->values(),
            'meta' => $this->paginationMeta($paginator),
            'links' => $this->paginationLinks($paginator),
        ];
    }

    private function userSummary(?\App\Models\User $user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
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
