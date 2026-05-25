<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\TicketStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketWorkflowService
{
    public const STATUS_NEW          = TicketStatus::NEW;
    public const STATUS_IN_PROGRESS  = TicketStatus::IN_PROGRESS;
    public const STATUS_WAITING_INFO = TicketStatus::WAITING_INFO;
    public const STATUS_RESOLVED     = TicketStatus::RESOLVED;
    public const STATUS_CLOSED       = TicketStatus::CLOSED;

    /**
     * Canonical status transition map.
     * Backend stores "in_progress". UI labels render it as "Ongoing".
     */
    private const TRANSITION_MAP = [
        self::STATUS_NEW => [
            self::STATUS_IN_PROGRESS,
            self::STATUS_WAITING_INFO,
            self::STATUS_RESOLVED,
            self::STATUS_CLOSED,
        ],
        self::STATUS_IN_PROGRESS => [
            self::STATUS_WAITING_INFO,
            self::STATUS_RESOLVED,
            self::STATUS_CLOSED,
        ],
        self::STATUS_WAITING_INFO => [
            self::STATUS_IN_PROGRESS,
            self::STATUS_RESOLVED,
            self::STATUS_CLOSED,
        ],
        self::STATUS_RESOLVED => [
            self::STATUS_IN_PROGRESS,
            self::STATUS_WAITING_INFO,
            self::STATUS_CLOSED,
        ],
        self::STATUS_CLOSED => [],
    ];

    public function __construct(
        private DashboardCacheService $dashboardCache
    ) {
    }

    public function allowedStatuses(): array
    {
        return array_keys(self::TRANSITION_MAP);
    }

    public function transitionMap(): array
    {
        return self::TRANSITION_MAP;
    }

    public function normalizeStatus(string $status): string
    {
        return TicketStatus::normalize($status);
    }

    public function canTransition(?string $fromStatus, string $toStatus): bool
    {
        $toStatus = $this->normalizeStatus($toStatus);

        if ($fromStatus === null) {
            return in_array($toStatus, $this->allowedStatuses(), true);
        }

        $fromStatus = $this->normalizeStatus($fromStatus);

        if ($fromStatus === $toStatus) {
            return true;
        }

        return in_array($toStatus, self::TRANSITION_MAP[$fromStatus] ?? [], true);
    }

    public function transition(
        Ticket $ticket,
        string $targetStatus,
        ?User $actor = null,
        ?string $note = null,
        array $context = []
    ): Ticket {
        $targetStatus = $this->normalizeStatus($targetStatus);
        $this->assertValidStatus($targetStatus);

        $result = DB::transaction(function () use ($ticket, $targetStatus, $actor, $note, $context) {
            /** @var Ticket|null $freshTicket */
            $freshTicket = Ticket::query()
                ->whereKey($ticket->id)
                ->lockForUpdate()
                ->first();

            if (!$freshTicket) {
                throw ValidationException::withMessages([
                    'ticket' => 'Ticket not found.',
                ]);
            }

            $oldStatus = $this->normalizeStatus((string) $freshTicket->status);
            $before    = $this->snapshotTicket($freshTicket);

            if (isset($context['guard']) && is_callable($context['guard'])) {
                $context['guard']($freshTicket);
            }

            if (!$this->canTransition($oldStatus, $targetStatus)) {
                throw ValidationException::withMessages([
                    'status' => "Invalid status transition from {$oldStatus} to {$targetStatus}.",
                ]);
            }

            $updates = $context['updates'] ?? [];
            unset($updates['status']);

            foreach ($updates as $key => $value) {
                $freshTicket->{$key} = $value;
            }

            if ($oldStatus !== $targetStatus) {
                $freshTicket->status = $targetStatus;
                $this->applyLifecycleTimestamps($freshTicket, $targetStatus);
            }

            $freshTicket->save();

            $forceInitialHistory = (bool) ($context['force_initial_history'] ?? false);

            if ($oldStatus !== $targetStatus || $forceInitialHistory) {
                TicketStatusHistory::create([
                    'ticket_id'  => $freshTicket->id,
                    'from_status' => $forceInitialHistory ? null : $oldStatus,
                    'to_status'  => $targetStatus,
                    'changed_by' => $actor?->id,
                    'changed_at' => now(),
                    'note'       => $note ?: 'Ticket status updated.',
                ]);
            }

            $freshTicket = $freshTicket->fresh(['creator', 'holder', 'teamMaster', 'priorityMaster']);

            if ($oldStatus !== $targetStatus || $forceInitialHistory || $before != $this->snapshotTicket($freshTicket)) {
                $this->recordAuditLog(
                    $freshTicket,
                    $actor,
                    $context['action'] ?? 'status_changed',
                    $context['description'] ?? $this->defaultDescription($freshTicket, $oldStatus, $targetStatus),
                    $before,
                    $this->snapshotTicket($freshTicket),
                    $context
                );
            }

            return $freshTicket;
        });

        // Phase 4: invalidate dashboard cache setelah setiap status transition
        $this->dashboardCache->invalidate();

        return $result;
    }

    public function claim(Ticket $ticket, User $actor, ?string $note = null, array $context = []): Ticket
    {
        $ticket->loadMissing('teamMaster');

        if (!$ticket->isTeamCode('it')) {
            throw ValidationException::withMessages([
                'ticket' => 'Only IT tickets can be claimed.',
            ]);
        }

        if ($ticket->holder_id !== null && (int) $ticket->holder_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'ticket' => 'Ticket already claimed by another resolver.',
            ]);
        }

        return $this->transition(
            $ticket,
            self::STATUS_IN_PROGRESS,
            $actor,
            $note ?: 'Ticket claimed by IT.',
            array_merge($context, [
                'action'      => $context['action'] ?? 'claimed',
                'description' => $context['description'] ?? 'Claimed ticket ' . AuditLogger::ticketLabel($ticket),
                'guard'       => function (Ticket $freshTicket) use ($actor): void {
                    if (!$freshTicket->isTeamCode('it')) {
                        throw ValidationException::withMessages([
                            'ticket' => 'Only IT tickets can be claimed.',
                        ]);
                    }

                    if ($freshTicket->holder_id !== null && (int) $freshTicket->holder_id !== (int) $actor->id) {
                        throw ValidationException::withMessages([
                            'ticket' => 'Ticket already claimed by another resolver.',
                        ]);
                    }
                },
                'updates' => array_merge($context['updates'] ?? [], [
                    'holder_id'  => $actor->id,
                    'claimed_at' => $ticket->claimed_at ?: now(),
                ]),
            ])
        );
    }

    public function autoClose(Ticket $ticket, ?string $note = null, array $context = []): Ticket
    {
        return $this->transition(
            $ticket,
            self::STATUS_CLOSED,
            null,
            $note ?: 'Auto-closed by scheduled command after resolved day changed.',
            array_merge($context, [
                'action'      => $context['action'] ?? 'auto_closed',
                'description' => $context['description'] ?? 'Auto-closed resolved ticket ' . AuditLogger::ticketLabel($ticket),
                'actor_name'  => $context['actor_name'] ?? 'System',
                'actor_role'  => $context['actor_role'] ?? 'system',
                'user_agent'  => $context['user_agent'] ?? 'artisan tickets:auto-close-resolved',
            ])
        );
    }

    public function snapshotTicket(Ticket $ticket): array
    {
        return [
            'id'          => $ticket->id,
            'ticket_code' => $ticket->ticket_code,
            'title'       => $ticket->title,
            'status'      => $ticket->status,
            'priority'    => $ticket->priority,
            'team'        => $ticket->team,
            'holder_id'   => $ticket->holder_id,
            'claimed_at'  => optional($ticket->claimed_at)?->toISOString(),
            'resolved_at' => optional($ticket->resolved_at)?->toISOString(),
            'closed_at'   => optional($ticket->closed_at)?->toISOString(),
        ];
    }

    private function assertValidStatus(string $status): void
    {
        if (!in_array($status, $this->allowedStatuses(), true)) {
            throw ValidationException::withMessages([
                'status' => "Unknown ticket status: {$status}.",
            ]);
        }
    }

    private function applyLifecycleTimestamps(Ticket $ticket, string $status): void
    {
        match ($status) {
            self::STATUS_NEW,
            self::STATUS_IN_PROGRESS,
            self::STATUS_WAITING_INFO => $this->markAsActive($ticket),
            self::STATUS_RESOLVED => $this->markAsResolved($ticket),
            self::STATUS_CLOSED => $this->markAsClosed($ticket),
            default => null,
        };
    }

    private function markAsActive(Ticket $ticket): void
    {
        // Reopened tickets must leave completion buckets immediately.
        $ticket->resolved_at = null;
        $ticket->closed_at = null;
    }

    private function markAsResolved(Ticket $ticket): void
    {
        // Preserve the first resolved timestamp while clearing stale closed state.
        $ticket->resolved_at = $ticket->resolved_at ?: now();
        $ticket->closed_at = null;
    }

    private function markAsClosed(Ticket $ticket): void
    {
        // Closed tickets need a completion timestamp even when directly closed without explicit resolve.
        $ticket->closed_at = $ticket->closed_at ?: now();

        if (!$ticket->resolved_at) {
            $ticket->resolved_at = $ticket->closed_at;
        }
    }

    private function defaultDescription(Ticket $ticket, string $fromStatus, string $toStatus): string
    {
        return 'Changed ticket status from ' . $fromStatus . ' to ' . $toStatus . ' for ' . AuditLogger::ticketLabel($ticket);
    }

    private function recordAuditLog(
        Ticket $ticket,
        ?User $actor,
        string $action,
        string $description,
        ?array $before,
        ?array $after,
        array $context = []
    ): void {
        AuditLog::create([
            'actor_id'     => $actor?->id,
            'actor_name'   => $actor?->name ?? $context['actor_name'] ?? null,
            'actor_email'  => $actor?->email ?? $context['actor_email'] ?? null,
            'actor_role'   => $actor?->role ?? $context['actor_role'] ?? null,
            'action'       => $this->normalizeAuditKey($action),
            'entity_type'  => 'ticket',
            'entity_id'    => $ticket->id,
            'entity_label' => AuditLogger::ticketLabel($ticket),
            'description'  => $description,
            'before_values' => $before,
            'after_values'  => $after,
            'ip_address'   => $context['ip_address'] ?? null,
            'user_agent'   => $context['user_agent'] ?? null,
        ]);
    }

    private function normalizeAuditKey(string $value): string
    {
        return str($value)
            ->trim()
            ->replace([' ', '-'], '_')
            ->lower()
            ->toString();
    }
}
