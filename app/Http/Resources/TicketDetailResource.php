<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Support\TicketLabels;
use App\Support\TicketStatus;

class TicketDetailResource extends JsonResource
{
    /**
     * Return the ticket detail without heavy relations; paginated relation payloads are injected separately.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_code' => $this->ticket_code,
            'ticket_label' => TicketLabels::code($this->resource),
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => TicketStatus::label($this->status),
            'priority' => $this->priority,
            'team' => $this->team,
            'category' => $this->category,
            'issue_type' => $this->issue_type,
            'created_by' => $this->created_by,
            'holder_id' => $this->holder_id,
            'client_id' => $this->client_id,
            'client_name' => $this->client_name,
            'client_contact' => $this->client_contact,
            'client_email' => $this->client_email,
            'platform_type' => $this->platform_type,
            'amount' => $this->amount,
            'flow_type' => $this->flow_type,
            'request_time' => optional($this->request_time)?->toISOString(),
            'internal_notes' => $this->internal_notes,
            'sla_deadline_at' => optional($this->sla_deadline_at)?->toISOString(),
            'claimed_at' => optional($this->claimed_at)?->toISOString(),
            'resolved_at' => optional($this->resolved_at)?->toISOString(),
            'closed_at' => optional($this->closed_at)?->toISOString(),
            'created_at' => optional($this->created_at)?->toISOString(),
            'created_at_label' => TicketLabels::dateTime($this->created_at),
            'updated_at' => optional($this->updated_at)?->toISOString(),
            'creator' => $this->whenLoaded('creator'),
            'holder' => $this->whenLoaded('holder'),
            'client' => $this->whenLoaded('client'),
            'team_master' => $this->whenLoaded('teamMaster'),
            'category_master' => $this->whenLoaded('categoryMaster'),
            'issue_type_master' => $this->whenLoaded('issueTypeMaster'),
            'priority_master' => $this->whenLoaded('priorityMaster'),
        ];
    }
}
