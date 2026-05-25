<?php

namespace App\Http\Resources;

use App\Support\HumanDuration;
use App\Support\TicketLabels;
use App\Support\TicketStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $responseSeconds = null;

        if ($this->isTeamCode('it') && $this->claimed_at) {
            $responseSeconds = Carbon::parse($this->created_at)->diffInSeconds(Carbon::parse($this->claimed_at));
        }

        return [
            'id' => $this->id,
            'ticket_code' => TicketLabels::code($this->resource),
            'status' => $this->status,
            'status_label' => TicketStatus::label($this->status),
            'team' => strtoupper($this->displayTeamCode() ?: '-'),
            'sla_time' => $this->sla_time ?? '-',
            'response_time' => $this->isTeamCode('it') ? HumanDuration::fromSeconds($responseSeconds) : 'N/A',
            'result' => $this->sla_result ?? '-',
        ];
    }
}
