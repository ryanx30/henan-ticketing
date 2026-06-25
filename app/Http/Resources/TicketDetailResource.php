<?php

namespace App\Http\Resources;

use App\Support\TicketLabels;
use App\Support\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketDetailResource extends JsonResource
{
    /**
     * Return a clean, grouped ticket detail payload for the detail/edit pages.
     * Heavy relation pages are injected separately by TicketDetailPayloadService.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => [
                'raw' => $this->ticket_code,
                'label' => TicketLabels::code($this->resource),
            ],
            'title' => $this->title,
            'description' => $this->description,
            'status' => [
                'code' => $this->status,
                'label' => TicketStatus::label($this->status),
            ],
            'classification' => [
                'priority' => $this->priorityPayload(),
                'team' => $this->teamPayload(),
                'category' => $this->categoryPayload(),
                'issue_type' => $this->issueTypePayload(),
            ],
            'client' => $this->clientPayload(),
            'assignment' => [
                'creator' => $this->userPayload($this->resource->relationLoaded('creator') ? $this->creator : null),
                'holder' => $this->userPayload($this->resource->relationLoaded('holder') ? $this->holder : null),
            ],
            'timestamps' => [
                'request_time' => optional($this->request_time)?->toISOString(),
                'sla_deadline_at' => optional($this->sla_deadline_at)?->toISOString(),
                'claimed_at' => optional($this->claimed_at)?->toISOString(),
                'resolved_at' => optional($this->resolved_at)?->toISOString(),
                'closed_at' => optional($this->closed_at)?->toISOString(),
                'created_at' => optional($this->created_at)?->toISOString(),
                'created_at_label' => TicketLabels::dateTime($this->created_at),
                'updated_at' => optional($this->updated_at)?->toISOString(),
            ],
            'internal_notes' => $this->internal_notes,
            'viewer' => [
                'id' => $request->user()?->id,
                'role' => $request->user()?->role,
            ],
        ];
    }

    private function priorityPayload(): array
    {
        $priority = $this->resource->relationLoaded('priorityMaster') ? $this->priorityMaster : null;

        return [
            'id' => $priority?->id,
            'code' => $priority?->code ?: $this->priority,
            'name' => $priority?->name ?: TicketLabels::title($this->priority),
            'code_num' => $priority?->code_num,
        ];
    }

    private function teamPayload(): array
    {
        $team = $this->resource->relationLoaded('teamMaster') ? $this->teamMaster : null;

        return [
            'id' => $team?->id,
            'code' => $team?->code ?: $this->team,
            'name' => $team?->name ?: strtoupper((string) ($this->team ?: '-')),
            'code_num' => $team?->code_num,
        ];
    }

    private function categoryPayload(): array
    {
        $category = $this->resource->relationLoaded('categoryMaster') ? $this->categoryMaster : null;

        return [
            'id' => $category?->id,
            'slug' => $category?->slug,
            'name' => $category?->name ?: TicketLabels::title($this->category),
            'code_num' => $category?->code_num,
        ];
    }

    private function issueTypePayload(): array
    {
        $issueType = $this->resource->relationLoaded('issueTypeMaster') ? $this->issueTypeMaster : null;

        return [
            'id' => $issueType?->id,
            'category_id' => $issueType?->category_id,
            'slug' => $issueType?->slug,
            'name' => $issueType?->name ?: TicketLabels::title($this->issue_type),
            'code_num' => $issueType?->code_num,
        ];
    }

    private function clientPayload(): ?array
    {
        $client = $this->resource->relationLoaded('client') ? $this->client : null;

        if (!$client && !$this->client_id && !$this->client_name && !$this->client_contact && !$this->client_email) {
            return null;
        }

        return [
            'id' => $client?->id ?: $this->client_id,
            'name' => $client?->name ?: $this->client_name,
            'contact' => $client?->contact ?: $this->client_contact,
            'email' => $client?->email ?: $this->client_email,
        ];
    }

    private function userPayload(?\Illuminate\Database\Eloquent\Model $user): ?array
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
}
