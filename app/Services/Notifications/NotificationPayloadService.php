<?php

namespace App\Services\Notifications;

use App\Models\AuditLog;
use App\Models\NotificationState;
use App\Models\ResolverMessage;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Builds role-aware notification payloads and keeps per-user read state separate
 * from the ticket and resolver-message source records.
 */
class NotificationPayloadService
{
    private const ACTIVE_STATUSES = ['new', 'in_progress', 'waiting_info'];
    private const RECENT_STATUS_DAYS = 7;
    private const SLA_WARNING_PROGRESS = 0.80;
    private const SOURCE_LIMIT = 50;

    private const SEVERITY_SCORES = [
        'critical' => 40,
        'warning' => 30,
        'info' => 20,
        'success' => 10,
        'neutral' => 0,
    ];

    /**
     * Return the latest notifications and separate unread/action counters.
     */
    public function payloadFor(?User $user, int $limit = 7): array
    {
        if (! $user) {
            return $this->emptyPayload();
        }

        $items = $this->itemsFor($user);
        $unreadCount = $items->where('is_unread', true)->count();
        $actionCount = $items->where('requires_action', true)->count();

        return [
            // Keep count for backward compatibility. The badge now represents unread items.
            'count' => $unreadCount,
            'unread_count' => $unreadCount,
            'action_count' => $actionCount,
            'total_count' => $items->count(),
            'latest' => $items
                ->take(max(1, min($limit, 20)))
                ->map(fn (array $item): array => $this->serializeItem($item))
                ->values()
                ->all(),
        ];
    }

    /**
     * Mark every currently visible notification as read for the user.
     */
    public function markAllAsRead(User $user): array
    {
        $items = $this->itemsFor($user);

        if ($items->isEmpty()) {
            return $this->payloadFor($user);
        }

        $now = now();
        $rows = $items
            ->map(fn (array $item): array => [
                'user_id' => $user->id,
                'notification_key' => $item['key'],
                'read_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        DB::table('notification_states')->upsert(
            $rows,
            ['user_id', 'notification_key'],
            ['read_at', 'updated_at']
        );

        $resolverMessageIds = $items
            ->where('source_type', 'resolver_message')
            ->pluck('source_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->values();

        if ($resolverMessageIds->isNotEmpty()) {
            ResolverMessage::query()
                ->where('to_user_id', $user->id)
                ->whereIn('id', $resolverMessageIds)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        return $this->payloadFor($user);
    }

    /**
     * Mark one current notification as read.
     */
    public function markAsRead(User $user, string $notificationKey): array
    {
        $item = $this->findCurrentItem($user, $notificationKey);
        $now = now();

        NotificationState::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'notification_key' => $item['key'],
            ],
            ['read_at' => $now]
        );

        if ($item['source_type'] === 'resolver_message' && $item['source_id']) {
            ResolverMessage::query()
                ->whereKey($item['source_id'])
                ->where('to_user_id', $user->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        return $this->payloadFor($user);
    }

    /**
     * Dismiss an informational notification. Action alerts remain visible until
     * the underlying ticket condition is resolved.
     */
    public function dismiss(User $user, string $notificationKey): array
    {
        $item = $this->findCurrentItem($user, $notificationKey);

        if ($item['requires_action']) {
            throw ValidationException::withMessages([
                'notification' => 'Action notifications cannot be dismissed while the ticket still needs attention.',
            ]);
        }

        $now = now();

        NotificationState::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'notification_key' => $item['key'],
            ],
            [
                'read_at' => $now,
                'dismissed_at' => $now,
            ]
        );

        if ($item['source_type'] === 'resolver_message' && $item['source_id']) {
            ResolverMessage::query()
                ->whereKey($item['source_id'])
                ->where('to_user_id', $user->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        return $this->payloadFor($user);
    }

    private function emptyPayload(): array
    {
        return [
            'count' => 0,
            'unread_count' => 0,
            'action_count' => 0,
            'total_count' => 0,
            'latest' => [],
        ];
    }

    /**
     * Build, deduplicate, apply read state, and sort notifications once so counts
     * always match the items shown in the dropdown.
     */
    private function itemsFor(User $user): Collection
    {
        $items = collect()
            ->merge($this->resolverMessages($user))
            ->merge($this->priorityChanges($user))
            ->merge($this->slaBreached($user))
            ->merge($this->slaWarnings($user))
            ->merge($this->waitingInfo($user))
            ->merge($this->criticalUnassigned($user))
            ->merge($this->reopenedTickets($user))
            ->merge($this->csStatusUpdates($user))
            ->merge($this->itTeamQueueTickets($user))
            ->merge($this->itInformationReceived($user))
            ->merge($this->itMyQueueTickets($user));

        $items = $this->deduplicateTicketItems($items->unique('key')->values());
        $keys = $items->pluck('key')->filter()->values();

        $states = NotificationState::query()
            ->where('user_id', $user->id)
            ->whereIn('notification_key', $keys)
            ->get()
            ->keyBy('notification_key');

        return $items
            ->map(function (array $item) use ($states): array {
                /** @var NotificationState|null $state */
                $state = $states->get($item['key']);

                $item['is_unread'] = ! $state?->read_at;
                $item['can_dismiss'] = ! $item['requires_action'];
                $item['dismissed_at'] = $state?->dismissed_at;

                return $item;
            })
            ->reject(fn (array $item): bool => $item['dismissed_at'] !== null)
            ->sort(function (array $left, array $right): int {
                $unreadComparison = (int) $right['is_unread'] <=> (int) $left['is_unread'];

                if ($unreadComparison !== 0) {
                    return $unreadComparison;
                }

                $priorityComparison = $right['priority_score'] <=> $left['priority_score'];

                if ($priorityComparison !== 0) {
                    return $priorityComparison;
                }

                $severityComparison = $right['severity_score'] <=> $left['severity_score'];

                if ($severityComparison !== 0) {
                    return $severityComparison;
                }

                return ($right['sort_at']?->getTimestamp() ?? 0)
                    <=> ($left['sort_at']?->getTimestamp() ?? 0);
            })
            ->values();
    }

    private function findCurrentItem(User $user, string $notificationKey): array
    {
        $notificationKey = trim($notificationKey);
        $item = $this->itemsFor($user)
            ->first(fn (array $item): bool => hash_equals($item['key'], $notificationKey));

        if (! $item) {
            throw ValidationException::withMessages([
                'notification' => 'Notification is no longer available.',
            ]);
        }

        return $item;
    }

    /**
     * Keep resolver messages as separate conversation events, then show only the
     * strongest current ticket notification for every other source.
     */
    private function deduplicateTicketItems(Collection $items): Collection
    {
        $resolverMessages = $items->where('source_type', 'resolver_message');

        $ticketItems = $items
            ->reject(fn (array $item): bool => $item['source_type'] === 'resolver_message')
            ->sort(function (array $left, array $right): int {
                $priorityComparison = $right['priority_score'] <=> $left['priority_score'];

                if ($priorityComparison !== 0) {
                    return $priorityComparison;
                }

                $severityComparison = $right['severity_score'] <=> $left['severity_score'];

                if ($severityComparison !== 0) {
                    return $severityComparison;
                }

                $actionComparison = (int) $right['requires_action'] <=> (int) $left['requires_action'];

                if ($actionComparison !== 0) {
                    return $actionComparison;
                }

                return ($right['sort_at']?->getTimestamp() ?? 0)
                    <=> ($left['sort_at']?->getTimestamp() ?? 0);
            })
            ->groupBy(fn (array $item): string => $item['ticket_id']
                ? 'ticket:' . $item['ticket_id']
                : $item['key'])
            ->map(fn (Collection $group): array => $group->first())
            ->values();

        return $resolverMessages->concat($ticketItems)->values();
    }

    // ========= NOTIFICATION SOURCES =========

    private function resolverMessages(User $user): Collection
    {
        return ResolverMessage::query()
            ->with(['sender', 'ticket.teamMaster', 'ticket.categoryMaster', 'ticket.issueTypeMaster'])
            ->where('to_user_id', $user->id)
            ->where('is_read', false)
            ->latest()
            ->limit(self::SOURCE_LIMIT)
            ->get()
            ->map(function (ResolverMessage $message): array {
                $ticket = $message->ticket;
                $sender = $message->sender?->name ?: 'System';

                return $this->item(
                    key: 'resolver_message:' . $message->id,
                    type: 'message',
                    group: 'event',
                    label: 'Message',
                    title: $sender,
                    description: Str::limit(strip_tags((string) ($message->body ?: $message->subject)), 86),
                    meta: $this->ticketMeta($ticket),
                    url: route('resolver-inbox.show', $message),
                    sortAt: $message->created_at,
                    accent: '#2563eb',
                    severity: 'info',
                    requiresAction: false,
                    ticket: $ticket,
                    sourceType: 'resolver_message',
                    sourceId: $message->id,
                );
            });
    }

    private function priorityChanges(User $user): Collection
    {
        $logs = AuditLog::query()
            ->where('entity_type', 'ticket')
            ->where('action', 'updated')
            ->where('created_at', '>=', now()->subDays(self::RECENT_STATUS_DAYS))
            ->whereNotNull('entity_id')
            ->where(function (Builder $query) use ($user) {
                $query->whereNull('actor_id')
                    ->orWhere('actor_id', '<>', $user->id);
            })
            ->latest()
            ->limit(self::SOURCE_LIMIT * 2)
            ->get()
            ->filter(function (AuditLog $log): bool {
                $beforePriority = data_get($log->before_values, 'priority');
                $afterPriority = data_get($log->after_values, 'priority');

                return $beforePriority && $afterPriority && $beforePriority !== $afterPriority;
            });

        if ($logs->isEmpty()) {
            return collect();
        }

        $tickets = $this->applyTicketScope(
            Ticket::query()->whereIn('id', $logs->pluck('entity_id')),
            $user
        )
            ->with(['teamMaster', 'categoryMaster', 'issueTypeMaster', 'priorityMaster'])
            ->get()
            ->keyBy('id');

        return $logs
            ->map(function (AuditLog $log) use ($tickets, $user): ?array {
                /** @var Ticket|null $ticket */
                $ticket = $tickets->get((int) $log->entity_id);

                if (! $ticket) {
                    return null;
                }

                $beforePriority = (string) data_get($log->before_values, 'priority');
                $afterPriority = (string) data_get($log->after_values, 'priority');
                $increased = $this->priorityScore($afterPriority) > $this->priorityScore($beforePriority);
                $requiresAction = $increased
                    && in_array($ticket->status, self::ACTIVE_STATUSES, true)
                    && ! ($user->isIT() && $ticket->status === 'waiting_info');
                $actor = $log->actor_name ?: 'System';

                return $this->item(
                    key: 'audit_log:' . $log->id,
                    type: 'priority_changed',
                    group: $requiresAction ? 'action' : 'event',
                    label: $increased ? 'Priority Increased' : 'Priority Changed',
                    title: Str::title($beforePriority) . ' to ' . Str::title($afterPriority),
                    description: $ticket->title,
                    meta: $this->ticketMeta($ticket) . ' · by ' . $actor,
                    url: route('tickets.show', $ticket),
                    sortAt: $log->created_at,
                    accent: $increased ? '#ea580c' : '#0284c7',
                    severity: $increased ? $this->severityForPriority($afterPriority) : 'info',
                    requiresAction: $requiresAction,
                    ticket: $ticket,
                    sourceType: 'audit_log',
                    sourceId: $log->id,
                );
            })
            ->filter()
            ->unique('ticket_id')
            ->take(self::SOURCE_LIMIT)
            ->values();
    }

    private function slaBreached(User $user): Collection
    {
        return $this->ticketScope($user)
            ->whereIn('status', $this->slaStatusesFor($user))
            ->whereNotNull('sla_deadline_at')
            ->where('sla_deadline_at', '<', now())
            ->with(['teamMaster', 'categoryMaster', 'issueTypeMaster', 'priorityMaster'])
            ->orderByDesc('sla_deadline_at')
            ->limit(self::SOURCE_LIMIT)
            ->get()
            ->map(fn (Ticket $ticket): array => $this->item(
                key: 'ticket:' . $ticket->id . ':sla_breached:' . $ticket->sla_deadline_at?->getTimestamp(),
                type: 'sla_breached',
                group: 'action',
                label: 'SLA Breached',
                title: 'SLA deadline missed',
                description: 'Deadline ' . $this->timeAgo($ticket->sla_deadline_at),
                meta: $this->ticketMeta($ticket),
                url: route('tickets.show', $ticket),
                sortAt: $ticket->sla_deadline_at,
                accent: '#dc2626',
                severity: 'critical',
                requiresAction: true,
                ticket: $ticket,
            ));
    }

    private function slaWarnings(User $user): Collection
    {
        return $this->ticketScope($user)
            ->whereIn('status', $this->slaStatusesFor($user))
            ->whereNotNull('sla_deadline_at')
            ->where('sla_deadline_at', '>=', now())
            ->with(['teamMaster', 'categoryMaster', 'issueTypeMaster', 'priorityMaster'])
            ->orderBy('sla_deadline_at')
            ->limit(self::SOURCE_LIMIT)
            ->get()
            ->filter(fn (Ticket $ticket): bool => $this->slaProgress($ticket) >= self::SLA_WARNING_PROGRESS)
            ->map(fn (Ticket $ticket): array => $this->item(
                key: 'ticket:' . $ticket->id . ':sla_warning:' . $ticket->sla_deadline_at?->getTimestamp(),
                type: 'sla_warning',
                group: 'action',
                label: 'SLA Warning',
                title: 'SLA deadline approaching',
                description: 'Due ' . $this->timeAgo($ticket->sla_deadline_at),
                meta: $this->ticketMeta($ticket) . ' · ' . round($this->slaProgress($ticket) * 100) . '% used',
                url: route('tickets.show', $ticket),
                sortAt: $this->slaWarningAt($ticket),
                accent: '#f59e0b',
                severity: 'warning',
                requiresAction: true,
                ticket: $ticket,
                displayAt: $ticket->sla_deadline_at,
            ));
    }

    private function waitingInfo(User $user): Collection
    {
        if (! $user->isAdmin() && ! $user->isSupervisor() && ! $user->isHeadCS() && ! $user->isCS()) {
            return collect();
        }

        $query = Ticket::query()
            ->where('status', 'waiting_info')
            ->with(['creator', 'latestStatusHistory', 'teamMaster', 'categoryMaster', 'issueTypeMaster', 'priorityMaster']);

        if ($user->isCS()) {
            $query->where('created_by', $user->id);
        } elseif ($user->isHeadCS()) {
            $query->whereHas('creator', fn (Builder $query) => $query->whereIn('role', [
                User::ROLE_CS,
                User::ROLE_HEAD_CS,
            ]));
        }

        return $query
            ->latest('updated_at')
            ->limit(self::SOURCE_LIMIT)
            ->get()
            ->map(fn (Ticket $ticket): array => $this->item(
                key: 'ticket:' . $ticket->id . ':waiting_info:' . ($ticket->latestStatusHistory?->id ?: 'current'),
                type: 'waiting_info',
                group: 'action',
                label: 'Information Required',
                title: 'IT needs additional information',
                description: $ticket->title,
                meta: $this->ticketMeta($ticket),
                url: route('tickets.show', $ticket),
                sortAt: $ticket->updated_at,
                accent: '#7c3aed',
                severity: 'warning',
                requiresAction: true,
                ticket: $ticket,
            ));
    }

    private function criticalUnassigned(User $user): Collection
    {
        if (! $user->isAdmin() && ! $user->isSupervisor() && ! $user->isHeadCS()) {
            return collect();
        }

        return Ticket::query()
            ->forTeamCode('it')
            ->forPriorityCode('critical')
            ->where('status', 'new')
            ->whereNull('holder_id')
            ->where('created_at', '<=', now()->subMinutes(15))
            ->with(['teamMaster', 'categoryMaster', 'issueTypeMaster', 'priorityMaster'])
            ->latest()
            ->limit(self::SOURCE_LIMIT)
            ->get()
            ->map(fn (Ticket $ticket): array => $this->item(
                key: 'ticket:' . $ticket->id . ':critical_unassigned',
                type: 'critical_unassigned',
                group: 'action',
                label: 'Critical Unassigned',
                title: 'Critical ticket has not been claimed',
                description: $ticket->title,
                meta: $this->ticketMeta($ticket),
                url: route('tickets.show', $ticket),
                sortAt: $ticket->created_at,
                accent: '#b91c1c',
                severity: 'critical',
                requiresAction: true,
                ticket: $ticket,
            ));
    }

    private function reopenedTickets(User $user): Collection
    {
        return TicketStatusHistory::query()
            ->with(['ticket.teamMaster', 'ticket.categoryMaster', 'ticket.issueTypeMaster', 'ticket.priorityMaster'])
            ->where('from_status', 'resolved')
            ->whereIn('to_status', ['in_progress', 'waiting_info'])
            ->where('changed_at', '>=', now()->subDays(self::RECENT_STATUS_DAYS))
            ->whereHas('ticket', fn (Builder $query) => $this->applyTicketScope($query, $user))
            ->latest('changed_at')
            ->limit(self::SOURCE_LIMIT)
            ->get()
            ->filter(fn (TicketStatusHistory $history): bool => $history->ticket
                && $history->ticket->status === $history->to_status)
            ->unique('ticket_id')
            ->map(function (TicketStatusHistory $history) use ($user): array {
                $ticket = $history->ticket;
                $requiresAction = ! ($user->isIT() && $history->to_status === 'waiting_info');

                return $this->item(
                    key: 'ticket_status_history:' . $history->id,
                    type: 'ticket_reopened',
                    group: $requiresAction ? 'action' : 'event',
                    label: 'Reopened',
                    title: 'Ticket reopened',
                    description: $ticket?->title ?: 'Ticket was reopened',
                    meta: $this->ticketMeta($ticket),
                    url: $ticket ? route('tickets.show', $ticket) : route('tickets.index'),
                    sortAt: $history->changed_at,
                    accent: '#0f766e',
                    severity: 'warning',
                    requiresAction: $requiresAction,
                    ticket: $ticket,
                    sourceType: 'ticket_status_history',
                    sourceId: $history->id,
                );
            })
            ->values();
    }

    private function csStatusUpdates(User $user): Collection
    {
        if (! $user->isCS() && ! $user->isHeadCS()) {
            return collect();
        }

        $query = TicketStatusHistory::query()
            ->with(['ticket.creator', 'ticket.teamMaster', 'ticket.categoryMaster', 'ticket.issueTypeMaster', 'ticket.priorityMaster'])
            ->where(function (Builder $query) {
                $query->whereIn('to_status', ['resolved', 'closed'])
                    ->orWhere(function (Builder $query) {
                        $query->where('from_status', 'new')
                            ->where('to_status', 'in_progress');
                    });
            })
            ->where('changed_at', '>=', now()->subDays(self::RECENT_STATUS_DAYS))
            ->where(function (Builder $query) use ($user) {
                $query->whereNull('changed_by')
                    ->orWhere('changed_by', '<>', $user->id);
            });

        if ($user->isCS()) {
            $query->whereHas('ticket', fn (Builder $query) => $query->where('created_by', $user->id));
        } else {
            $query->whereHas('ticket.creator', fn (Builder $query) => $query->whereIn('role', [
                User::ROLE_CS,
                User::ROLE_HEAD_CS,
            ]));
        }

        return $query
            ->latest('changed_at')
            ->limit(self::SOURCE_LIMIT)
            ->get()
            ->filter(fn (TicketStatusHistory $history): bool => $history->ticket
                && $history->ticket->status === $history->to_status)
            ->unique('ticket_id')
            ->map(function (TicketStatusHistory $history): array {
                $ticket = $history->ticket;
                $status = str_replace('_', ' ', (string) $history->to_status);
                $severity = match ($history->to_status) {
                    'waiting_info' => 'warning',
                    'resolved', 'closed' => 'success',
                    default => 'info',
                };

                return $this->item(
                    key: 'ticket_status_history:' . $history->id,
                    type: 'ticket_status',
                    group: 'event',
                    label: 'Ticket Update',
                    title: 'Ticket ' . Str::title($status),
                    description: $ticket?->title ?: 'Ticket status was updated',
                    meta: $this->ticketMeta($ticket),
                    url: $ticket ? route('tickets.show', $ticket) : route('tickets.index'),
                    sortAt: $history->changed_at,
                    accent: $severity === 'success' ? '#16a34a' : '#0284c7',
                    severity: $severity,
                    requiresAction: false,
                    ticket: $ticket,
                    sourceType: 'ticket_status_history',
                    sourceId: $history->id,
                );
            })
            ->values();
    }

    private function itTeamQueueTickets(User $user): Collection
    {
        if (! $user->isIT()) {
            return collect();
        }

        return Ticket::query()
            ->forTeamCode('it')
            ->where('status', 'new')
            ->whereNull('holder_id')
            ->with(['teamMaster', 'categoryMaster', 'issueTypeMaster', 'priorityMaster'])
            ->latest()
            ->limit(self::SOURCE_LIMIT)
            ->get()
            ->map(fn (Ticket $ticket): array => $this->item(
                key: 'ticket:' . $ticket->id . ':team_queue',
                type: 'team_queue',
                group: 'action',
                label: 'Team Queue',
                title: 'New ticket in team queue',
                description: $ticket->title,
                meta: $this->ticketMeta($ticket),
                url: route('tickets.show', $ticket),
                sortAt: $ticket->created_at,
                accent: '#16a34a',
                severity: $this->ticketSeverity($ticket),
                requiresAction: true,
                ticket: $ticket,
            ));
    }

    private function itInformationReceived(User $user): Collection
    {
        if (! $user->isIT()) {
            return collect();
        }

        return TicketStatusHistory::query()
            ->with(['ticket.teamMaster', 'ticket.categoryMaster', 'ticket.issueTypeMaster', 'ticket.priorityMaster'])
            ->where('from_status', 'waiting_info')
            ->where('to_status', 'in_progress')
            ->where('changed_at', '>=', now()->subDays(self::RECENT_STATUS_DAYS))
            ->whereHas('ticket', fn (Builder $query) => $query
                ->where('holder_id', $user->id)
                ->where('status', 'in_progress'))
            ->latest('changed_at')
            ->limit(self::SOURCE_LIMIT)
            ->get()
            ->unique('ticket_id')
            ->map(function (TicketStatusHistory $history): array {
                $ticket = $history->ticket;

                return $this->item(
                    key: 'ticket_status_history:' . $history->id,
                    type: 'information_received',
                    group: 'action',
                    label: 'Information Received',
                    title: 'Ticket can be continued',
                    description: $ticket?->title ?: 'Additional information was provided',
                    meta: $this->ticketMeta($ticket),
                    url: $ticket ? route('tickets.show', $ticket) : route('it.my-queue'),
                    sortAt: $history->changed_at,
                    accent: '#2563eb',
                    severity: 'info',
                    requiresAction: true,
                    ticket: $ticket,
                    sourceType: 'ticket_status_history',
                    sourceId: $history->id,
                );
            })
            ->values();
    }

    private function itMyQueueTickets(User $user): Collection
    {
        if (! $user->isIT()) {
            return collect();
        }

        return Ticket::query()
            ->where('holder_id', $user->id)
            ->where('status', 'in_progress')
            ->with(['teamMaster', 'categoryMaster', 'issueTypeMaster', 'priorityMaster'])
            ->latest('updated_at')
            ->limit(self::SOURCE_LIMIT)
            ->get()
            ->map(fn (Ticket $ticket): array => $this->item(
                key: 'ticket:' . $ticket->id . ':my_queue',
                type: 'my_queue',
                group: 'action',
                label: 'My Queue',
                title: 'Ticket needs your action',
                description: $ticket->title,
                meta: $this->ticketMeta($ticket),
                url: route('tickets.show', $ticket),
                sortAt: $ticket->updated_at,
                accent: '#2563eb',
                severity: $this->ticketSeverity($ticket),
                requiresAction: true,
                ticket: $ticket,
            ));
    }

    // ========= SHARED FORMATTERS =========

    private function ticketScope(User $user): Builder
    {
        return $this->applyTicketScope(Ticket::query(), $user);
    }

    private function applyTicketScope(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isSupervisor() || $user->isHeadCS()) {
            return $query;
        }

        if ($user->isCS()) {
            return $query->where('created_by', $user->id);
        }

        if ($user->isIT()) {
            return $query->where(function (Builder $query) use ($user) {
                $query->where('holder_id', $user->id)
                    ->orWhere(function (Builder $query) {
                        $query->forTeamCode('it')
                            ->whereNull('holder_id');
                    });
            });
        }

        return $query->whereRaw('1 = 0');
    }

    private function item(
        string $key,
        string $type,
        string $group,
        string $label,
        string $title,
        string $description,
        string $meta,
        string $url,
        ?CarbonInterface $sortAt,
        string $accent,
        string $severity,
        bool $requiresAction,
        ?Ticket $ticket = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?CarbonInterface $displayAt = null,
    ): array {
        return [
            'key' => Str::limit($key, 191, ''),
            'type' => $type,
            'group' => $group,
            'label' => $label,
            'title' => Str::limit($title, 58),
            'description' => Str::limit($description, 96),
            'meta' => $meta,
            'url' => $url,
            'time' => $this->timeAgo($displayAt ?: $sortAt),
            'accent' => $accent,
            'severity' => $severity,
            'severity_score' => self::SEVERITY_SCORES[$severity] ?? 0,
            'priority_score' => $this->typePriority($type),
            'requires_action' => $requiresAction,
            'ticket_id' => $ticket?->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'sort_at' => $sortAt,
        ];
    }

    private function serializeItem(array $item): array
    {
        unset(
            $item['sort_at'],
            $item['severity_score'],
            $item['priority_score'],
            $item['dismissed_at'],
            $item['source_type'],
            $item['source_id'],
        );

        return $item;
    }

    private function ticketMeta(?Ticket $ticket): string
    {
        if (! $ticket) {
            return 'Ticket unavailable';
        }

        $code = $ticket->ticket_code ? 'T-' . $ticket->ticket_code : 'Ticket #' . $ticket->id;
        $issue = $ticket->displayIssueTypeName() ?: $ticket->displayCategoryName();

        return trim($code . ($issue ? ' · ' . $issue : ''));
    }

    private function typePriority(string $type): int
    {
        return match ($type) {
            'sla_breached' => 100,
            'critical_unassigned' => 90,
            'sla_warning' => 80,
            'waiting_info' => 70,
            'information_received' => 65,
            'ticket_reopened' => 60,
            'priority_changed' => 55,
            'my_queue' => 40,
            'team_queue' => 30,
            'ticket_status' => 20,
            'message' => 10,
            default => 0,
        };
    }

    private function ticketSeverity(Ticket $ticket): string
    {
        return $this->severityForPriority($ticket->displayPriorityCode());
    }

    private function severityForPriority(string $priority): string
    {
        return match (strtolower($priority)) {
            'critical' => 'critical',
            'high' => 'warning',
            default => 'info',
        };
    }

    private function slaStatusesFor(User $user): array
    {
        return $user->isIT()
            ? ['new', 'in_progress']
            : self::ACTIVE_STATUSES;
    }

    private function priorityScore(string $priority): int
    {
        return match (strtolower($priority)) {
            'critical' => 4,
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 0,
        };
    }

    private function slaProgress(Ticket $ticket): float
    {
        if (! $ticket->created_at || ! $ticket->sla_deadline_at) {
            return 0.0;
        }

        $totalSeconds = $ticket->created_at->diffInSeconds($ticket->sla_deadline_at, false);

        if ($totalSeconds <= 0) {
            return 0.0;
        }

        $elapsedSeconds = $ticket->created_at->diffInSeconds(now(), false);

        return max(0.0, min(1.0, $elapsedSeconds / $totalSeconds));
    }

    private function slaWarningAt(Ticket $ticket): ?CarbonInterface
    {
        if (! $ticket->created_at || ! $ticket->sla_deadline_at) {
            return $ticket->updated_at;
        }

        $totalSeconds = $ticket->created_at->diffInSeconds($ticket->sla_deadline_at, false);

        if ($totalSeconds <= 0) {
            return $ticket->updated_at;
        }

        return $ticket->created_at->copy()->addSeconds((int) round($totalSeconds * self::SLA_WARNING_PROGRESS));
    }

    private function timeAgo(?CarbonInterface $date): string
    {
        return $date ? $date->diffForHumans() : '-';
    }
}
