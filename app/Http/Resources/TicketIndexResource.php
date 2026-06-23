<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Compact payload for the ticket repository/list page.
 *
 * Keep this response intentionally light: labels and badge text are generated in the frontend,
 * while full relations and detailed fields are available from /api/tickets/{ticket}.
 */
class TicketIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_code' => $this->ticket_code,
            'title' => $this->title,
            'status' => $this->status,
            'priority' => $this->displayPriorityCode(),
            'team' => $this->displayTeamCode(),
            'category' => $this->category,
            'issue_type' => $this->issue_type,
            'created_at' => optional($this->created_at)?->toISOString(),
        ];
    }
}
