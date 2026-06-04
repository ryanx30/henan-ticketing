<?php

namespace App\Services\Tickets;

use App\Http\Resources\TicketDetailResource;
use App\Models\Ticket;
use App\Models\TicketAttachment;
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
        $data['viewer_role'] = $request->user()->role;
        $data['viewer_id'] = $request->user()->id;
        $data['relations'] = [
            'history' => $this->relationPage($history),
            'messages' => $this->relationPage($messages),
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

    private function attachmentRelationPage($paginator, Ticket $ticket): array
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

    private function relationPage($paginator): array
    {
        return [
            'data' => $paginator->items(),
            'meta' => $this->paginationMeta($paginator),
            'links' => $this->paginationLinks($paginator),
        ];
    }

    private function paginationMeta($paginator): array
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

    private function paginationLinks($paginator): array
    {
        return [
            'next' => $paginator->nextPageUrl(),
            'prev' => $paginator->previousPageUrl(),
        ];
    }
}
