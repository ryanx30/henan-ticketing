<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Client;
use App\Models\IssueType;
use App\Models\Priority;
use App\Models\SlaRule;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function __construct(
        private TicketCodeService $ticketCodeService
    ) {
    }

    public function create(array $validated, User $user, ?UploadedFile $attachment = null): Ticket
    {
        [$team, $category, $issueType, $priority] = $this->resolveMasterDataFromIds($validated);

        $slaHours = $this->getSlaHoursForTicket($team, $priority);
        $status = $team->code === 'it' ? 'new' : 'closed';

        return DB::transaction(function () use ($validated, $user, $attachment, $team, $category, $issueType, $priority, $slaHours, $status) {
            $client = Client::resolveForTicket($validated);
            $slaDeadline = $team->code === 'it' && $slaHours ? now()->addHours($slaHours) : null;

            $ticket = Ticket::create([
                'ticket_code'     => $this->ticketCodeService->generate($team, $category, $issueType, $priority),
                'title'           => $validated['title'],
                'description'     => $validated['description'],
                'priority'        => $priority->code,
                'team'            => $team->code,
                'team_id'         => $team->id,
                'category_id'     => $category->id,
                'issue_type_id'   => $issueType->id,
                'priority_id'     => $priority->id,
                'status'          => $status,
                'created_by'      => $user->id,
                'client_id'       => $client?->id,
                'sla_deadline_at' => $slaDeadline,
                'category'        => $category->name,
                'issue_type'      => $issueType->name,
                'client_name'     => $validated['client_name'] ?? null,
                'client_contact'  => $validated['client_contact'] ?? null,
                'client_email'    => $validated['client_email'] ?? null,
                'platform_type'   => $validated['platform_type'] ?? null,
                'amount'          => $validated['amount'] ?? null,
                'flow_type'       => $validated['flow_type'] ?? null,
                'request_time'    => $validated['request_time'] ?? null,
                'internal_notes'  => $validated['internal_notes'] ?? null,
                'resolved_at'     => $status === 'resolved' ? now() : null,
                'closed_at'       => $status === 'closed' ? now() : null,
            ]);

            TicketStatusHistory::create([
                'ticket_id'   => $ticket->id,
                'from_status' => null,
                'to_status'   => $status,
                'changed_by'  => $user->id,
                'changed_at'  => now(),
                'note'        => 'Initial status on ticket creation',
            ]);

            if ($attachment) {
                $this->storeAttachment($ticket, $attachment, $user);
            }

            return $ticket->load(['creator', 'holder', 'client', 'attachments']);
        });
    }

    public function update(Ticket $ticket, array $validated, User $user): Ticket
    {
        [$team, $category, $issueType, $priority] = $this->resolveMasterDataFromIds($validated);
        $slaHours = $this->getSlaHoursForTicket($team, $priority);

        DB::transaction(function () use ($ticket, $validated, $user, $team, $category, $issueType, $priority, $slaHours) {
            $oldStatus = $ticket->status;
            $oldTeam = $ticket->team;
            $oldPriority = $ticket->priority;

            $newTeam = $team->code;
            $newPriority = $priority->code;
            $newStatus = $validated['status'];

            if ($newTeam !== 'it') {
                $newStatus = 'closed';
                $ticket->sla_deadline_at = null;
                $ticket->holder_id = null;
                $ticket->claimed_at = null;
            }

            if (
                $newTeam === 'it' &&
                in_array($newStatus, ['new', 'in_progress', 'waiting_info'], true) &&
                ($oldTeam !== $newTeam || $oldPriority !== $newPriority || !$ticket->sla_deadline_at)
            ) {
                $ticket->sla_deadline_at = $slaHours ? now()->addHours($slaHours) : null;
            }

            $ticket->fill([
                'title'         => $validated['title'],
                'description'   => $validated['description'],
                'priority'      => $newPriority,
                'team'          => $newTeam,
                'team_id'       => $team->id,
                'category_id'   => $category->id,
                'issue_type_id' => $issueType->id,
                'priority_id'   => $priority->id,
                'status'        => $newStatus,
                'category'      => $category->name,
                'issue_type'    => $issueType->name,
            ]);

            if ($newStatus === 'resolved' && !$ticket->resolved_at) {
                $ticket->resolved_at = now();
            }

            if ($newStatus === 'closed' && !$ticket->closed_at) {
                $ticket->closed_at = now();
            }

            $ticket->save();

            if ($oldStatus !== $newStatus) {
                TicketStatusHistory::create([
                    'ticket_id'   => $ticket->id,
                    'from_status' => $oldStatus,
                    'to_status'   => $newStatus,
                    'changed_by'  => $user->id,
                    'changed_at'  => now(),
                    'note'        => 'Status updated from API',
                ]);
            }
        });

        return $ticket->fresh(['creator', 'holder', 'client', 'attachments']);
    }

    public function snapshot(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'ticket_code' => $ticket->ticket_code,
            'title' => $ticket->title,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'team' => $ticket->team,
            'category' => $ticket->category,
            'issue_type' => $ticket->issue_type,
            'team_id' => $ticket->team_id,
            'category_id' => $ticket->category_id,
            'issue_type_id' => $ticket->issue_type_id,
            'priority_id' => $ticket->priority_id,
            'holder_id' => $ticket->holder_id,
            'created_by' => $ticket->created_by,
            'client_id' => $ticket->client_id,
            'sla_deadline_at' => optional($ticket->sla_deadline_at)?->toISOString(),
            'resolved_at' => optional($ticket->resolved_at)?->toISOString(),
            'closed_at' => optional($ticket->closed_at)?->toISOString(),
        ];
    }

    public function resolveMasterDataFromIds(array $validated): array
    {
        $team = Team::query()
            ->whereKey($validated['team_id'])
            ->where('is_active', true)
            ->first();

        if (!$team) {
            abort(response()->json([
                'success' => false,
                'message' => 'Selected team is inactive or not found.',
            ], 422));
        }

        $category = Category::query()
            ->whereKey($validated['category_id'])
            ->where('is_active', true)
            ->first();

        if (!$category) {
            abort(response()->json([
                'success' => false,
                'message' => 'Selected category is inactive or not found.',
            ], 422));
        }

        $issueType = IssueType::query()
            ->whereKey($validated['issue_type_id'])
            ->where('category_id', $category->id)
            ->where('is_active', true)
            ->first();

        if (!$issueType) {
            abort(response()->json([
                'success' => false,
                'message' => 'Selected issue type is invalid for the selected category.',
            ], 422));
        }

        $priority = Priority::query()
            ->whereKey($validated['priority_id'])
            ->where('is_active', true)
            ->first();

        if (!$priority) {
            abort(response()->json([
                'success' => false,
                'message' => 'Selected priority is inactive or not found.',
            ], 422));
        }

        return [$team, $category, $issueType, $priority];
    }

    public function getSlaHoursForTicket(Team $team, Priority $priority): ?int
    {
        $rule = SlaRule::query()
            ->where('team_id', $team->id)
            ->where('priority_id', $priority->id)
            ->where('is_active', true)
            ->first();

        if ($rule) {
            return (int) $rule->hours;
        }

        return match ($priority->code) {
            'critical' => 2,
            'high' => 6,
            'medium' => 12,
            'low' => 24,
            default => null,
        };
    }

    private function storeAttachment(Ticket $ticket, UploadedFile $file, User $user): void
    {
        $path = $file->store('ticket-attachments', 'public');

        TicketAttachment::create([
            'ticket_id'   => $ticket->id,
            'file_name'   => $file->getClientOriginalName(),
            'file_path'   => $path,
            'file_type'   => $file->getClientMimeType(),
            'file_size'   => $file->getSize(),
            'uploaded_by' => $user->id,
        ]);
    }
}
