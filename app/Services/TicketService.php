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
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Coordinates ticket creation and update rules that are not tied to a single controller action.
 */
class TicketService
{
    public function __construct(
        private TicketCodeService $ticketCodeService,
        private TicketWorkflowService $ticketWorkflowService
    ) {
    }

    public function create(array $validated, User $user, ?UploadedFile $attachment = null): Ticket
    {
        [$team, $category, $issueType, $priority] = $this->resolveMasterDataFromIds($validated);

        $slaHours = $this->getSlaHoursForTicket($team, $priority);
        $isItTeam = $this->isItTeam($team);
        $status = $isItTeam ? 'new' : 'closed';

        return DB::transaction(function () use ($validated, $user, $attachment, $team, $category, $issueType, $priority, $slaHours, $status, $isItTeam) {
            $client = Client::resolveForTicket($validated);
            $completedAt = $status === 'closed' ? now() : null;
            $slaDeadline = $isItTeam && $slaHours ? now()->addHours($slaHours) : null;

            $ticket = Ticket::create([
                'ticket_code'     => $this->ticketCodeService->generate($team, $category, $issueType, $priority),
                'title'           => $validated['title'],
                'description'     => $validated['description'],
                'priority'        => $priority->code,
                'team'            => $this->normalizeTeamCode($team),
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
                // Non-IT tickets are direct-closed historical records.
                // They need both completion timestamps so reports/history do not treat them as active work.
                'resolved_at'     => $completedAt,
                'closed_at'       => $completedAt,
            ]);

            $initialStatusNote = $isItTeam
                ? 'Initial status on ticket creation'
                : 'Ticket auto-closed because it is routed to a non-IT team.';

            $this->ticketWorkflowService->transition(
                $ticket,
                $status,
                $user,
                $initialStatusNote,
                [
                    'action' => 'created_status',
                    'description' => 'Created initial ticket status.',
                    'force_initial_history' => true,
                ]
            );

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

        return DB::transaction(function () use ($ticket, $validated, $user, $team, $category, $issueType, $priority, $slaHours) {
            /** @var Ticket $freshTicket */
            $freshTicket = Ticket::query()
                ->whereKey($ticket->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldTeam = $freshTicket->team;
            $oldPriority = $freshTicket->priority;

            $newTeam = $this->normalizeTeamCode($team);
            $newPriority = $priority->code;
            $newStatus = $this->ticketWorkflowService->normalizeStatus($validated['status']);

            if ($newTeam !== 'it') {
                $newStatus = TicketWorkflowService::STATUS_CLOSED;
                $freshTicket->sla_deadline_at = null;
                $freshTicket->holder_id = null;
                $freshTicket->claimed_at = null;
            }

            if (
                $newTeam === 'it' &&
                in_array($newStatus, [
                    TicketWorkflowService::STATUS_NEW,
                    TicketWorkflowService::STATUS_IN_PROGRESS,
                    TicketWorkflowService::STATUS_WAITING_INFO,
                ], true) &&
                ($oldTeam !== $newTeam || $oldPriority !== $newPriority || !$freshTicket->sla_deadline_at)
            ) {
                $freshTicket->sla_deadline_at = $slaHours ? now()->addHours($slaHours) : null;
            }

            $freshTicket->fill([
                'title'         => $validated['title'],
                'description'   => $validated['description'],
                'priority'      => $newPriority,
                'team'          => $newTeam,
                'team_id'       => $team->id,
                'category_id'   => $category->id,
                'issue_type_id' => $issueType->id,
                'priority_id'   => $priority->id,
                'category'      => $category->name,
                'issue_type'    => $issueType->name,
            ]);

            $freshTicket->save();

            if ($this->ticketWorkflowService->normalizeStatus((string) $freshTicket->status) !== $newStatus) {
                $freshTicket = $this->ticketWorkflowService->transition(
                    $freshTicket,
                    $newStatus,
                    $user,
                    'Status updated from ticket edit.',
                    [
                        'action' => 'status_changed',
                        'description' => 'Changed ticket status from edit page.',
                    ]
                );
            }

            return $freshTicket->fresh(['creator', 'holder', 'client', 'attachments']);
        });
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
                'status' => false,
                'message' => 'Selected team is inactive or not found.',
            ], 422));
        }

        $category = Category::query()
            ->whereKey($validated['category_id'])
            ->where('is_active', true)
            ->first();

        if (!$category) {
            abort(response()->json([
                'status' => false,
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
                'status' => false,
                'message' => 'Selected issue type is invalid for the selected category.',
            ], 422));
        }

        $priority = Priority::query()
            ->whereKey($validated['priority_id'])
            ->where('is_active', true)
            ->first();

        if (!$priority) {
            abort(response()->json([
                'status' => false,
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


    private function normalizeTeamCode(Team $team): string
    {
        return str((string) ($team->code ?: $team->name))
            ->trim()
            ->lower()
            ->replace(['-', ' '], '_')
            ->toString();
    }

    private function isItTeam(Team $team): bool
    {
        return $this->normalizeTeamCode($team) === 'it';
    }

    private function storeAttachment(Ticket $ticket, UploadedFile $file, User $user): void
    {
        // Store uploads on the private local disk. Files must be downloaded through
        // an authorized controller endpoint instead of being exposed from /storage.
        $path = $file->store('ticket-attachments/' . $ticket->id, 'local');

        TicketAttachment::create([
            'ticket_id'   => $ticket->id,
            'file_name'   => Str::limit($file->getClientOriginalName(), 180, ''),
            'file_path'   => $path,
            'file_type'   => $file->getMimeType() ?: $file->getClientMimeType(),
            'file_size'   => $file->getSize(),
            'uploaded_by' => $user->id,
        ]);
    }
}
