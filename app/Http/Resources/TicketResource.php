<?php

namespace App\Http\Resources;

use App\Support\TicketLabels;
use App\Support\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
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
            'priority' => $this->displayPriorityCode(),
            'priority_label' => TicketLabels::title($this->displayPriorityCode()),
            'team' => $this->displayTeamCode(),
            'team_label' => strtoupper($this->displayTeamCode() ?: '-'),
            'category' => $this->category,
            'category_label' => TicketLabels::title($this->category),
            'issue_type' => $this->issue_type,
            'issue_type_label' => TicketLabels::title($this->issue_type),
            'created_by' => $this->created_by,
            'holder_id' => $this->holder_id,
            'client_id' => $this->client_id,
            'sla_deadline_at' => optional($this->sla_deadline_at)?->toISOString(),
            'claimed_at' => optional($this->claimed_at)?->toISOString(),
            'resolved_at' => optional($this->resolved_at)?->toISOString(),
            'closed_at' => optional($this->closed_at)?->toISOString(),
            'created_at' => optional($this->created_at)?->toISOString(),
            'updated_at' => optional($this->updated_at)?->toISOString(),
            'created_at_label' => TicketLabels::dateTime($this->created_at),
            'creator' => UserSummaryResource::make($this->whenLoaded('creator')),
            'holder' => UserSummaryResource::make($this->whenLoaded('holder')),
            'client' => $this->whenLoaded('client'),
            'team_master' => $this->whenLoaded('teamMaster'),
            'category_master' => $this->whenLoaded('categoryMaster'),
            'issue_type_master' => $this->whenLoaded('issueTypeMaster'),
            'priority_master' => $this->whenLoaded('priorityMaster'),
            'similarity_score' => $this->when(isset($this->similarity_score), $this->similarity_score),
        ];
    }
}
