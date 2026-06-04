<?php

namespace App\Services\Notifications;

use App\Models\ResolverMessage;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Builds the role-aware notification payload used by the navbar dropdown.
 */
class NotificationPayloadService
{
    private const ACTIVE_STATUSES = ['new', 'in_progress', 'waiting_info'];
    private const SLA_WARNING_MINUTES = 60;
    private const RECENT_STATUS_DAYS = 7;

    /**
     * Return the latest actionable notifications and the total count for the current role.
     */
    public function payloadFor(?User $user, int $limit = 5): array
    {
        if (! $user) {
            return [
                'count' => 0,
                'latest' => [],
            ];
        }

        $items = collect()
            ->merge($this->resolverMessages($user))
            ->merge($this->slaBreached($user))
            ->merge($this->slaWarnings($user))
            ->merge($this->waitingInfo($user))
            ->merge($this->reopenedTickets($user))
            ->merge($this->csStatusUpdates($user))
            ->merge($this->itTeamQueueTickets($user))
            ->merge($this->itMyQueueTickets($user));

        $latest = $items
            ->sortByDesc(fn (array $item) => $item['sort_at']?->getTimestamp() ?? 0)
            ->take($limit)
            ->map(function (array $item): array {
                unset($item['sort_at']);

                return $item;
            })
            ->values()
            ->all();

        return [
            'count' => $this->totalCount($user),
            'latest' => $latest,
        ];
    }

    // ========= NOTIFICATION SOURCES =========

    private function resolverMessages(User $user): Collection
    {
        return ResolverMessage::query()
            ->with(['sender', 'ticket'])
            ->where('to_user_id', $user->id)
            ->where('is_read', false)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function (ResolverMessage $message): array {
                $ticket = $message->ticket;
                $sender = $message->sender?->name ?: 'System';

                return $this->item(
                    type: 'message',
                    label: 'Message',
                    title: $sender,
                    description: Str::limit(strip_tags((string) ($message->body ?: $message->subject)), 86),
                    meta: $this->ticketMeta($ticket),
                    url: route('resolver-inbox.show', $message),
                    sortAt: $message->created_at,
                    accent: '#2563eb'
                );
            });
    }

    private function slaBreached(User $user): Collection
    {
        return $this->ticketScope($user)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->whereNotNull('sla_deadline_at')
            ->where('sla_deadline_at', '<', now())
            ->with(['teamMaster', 'categoryMaster', 'issueTypeMaster'])
            ->orderByDesc('sla_deadline_at')
            ->limit(10)
            ->get()
            ->map(fn (Ticket $ticket): array => $this->item(
                type: 'sla_breached',
                label: 'SLA Breached',
                title: 'SLA breached',
                description: $ticket->title,
                meta: $this->ticketMeta($ticket),
                url: route('tickets.show', $ticket),
                sortAt: $ticket->sla_deadline_at,
                accent: '#dc2626'
            ));
    }

    private function slaWarnings(User $user): Collection
    {
        return $this->ticketScope($user)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->whereNotNull('sla_deadline_at')
            ->whereBetween('sla_deadline_at', [now(), now()->addMinutes(self::SLA_WARNING_MINUTES)])
            ->with(['teamMaster', 'categoryMaster', 'issueTypeMaster'])
            ->orderBy('sla_deadline_at')
            ->limit(10)
            ->get()
            ->map(fn (Ticket $ticket): array => $this->item(
                type: 'sla_warning',
                label: 'SLA Warning',
                title: 'SLA deadline approaching',
                description: 'Due ' . $this->timeAgo($ticket->sla_deadline_at),
                meta: $this->ticketMeta($ticket),
                url: route('tickets.show', $ticket),
                sortAt: $ticket->sla_deadline_at,
                accent: '#f59e0b'
            ));
    }

    private function waitingInfo(User $user): Collection
    {
        if (! $user->isAdmin() && ! $user->isSupervisor() && ! $user->isCS() && ! $user->isIT()) {
            return collect();
        }

        $query = Ticket::query()
            ->where('status', 'waiting_info')
            ->with(['teamMaster', 'categoryMaster', 'issueTypeMaster']);

        if ($user->isCS()) {
            $query->where('created_by', $user->id);
        } elseif ($user->isIT()) {
            $query->where('holder_id', $user->id);
        }

        return $query
            ->latest('updated_at')
            ->limit(10)
            ->get()
            ->map(fn (Ticket $ticket): array => $this->item(
                type: 'waiting_info',
                label: 'Need Follow-up',
                title: 'Ticket needs follow-up',
                description: $ticket->title,
                meta: $this->ticketMeta($ticket),
                url: route('tickets.show', $ticket),
                sortAt: $ticket->updated_at,
                accent: '#7c3aed'
            ));
    }

    private function reopenedTickets(User $user): Collection
    {
        return TicketStatusHistory::query()
            ->with(['ticket.teamMaster', 'ticket.categoryMaster', 'ticket.issueTypeMaster'])
            ->where('from_status', 'resolved')
            ->whereIn('to_status', ['in_progress', 'waiting_info'])
            ->where('changed_at', '>=', now()->subDays(self::RECENT_STATUS_DAYS))
            ->whereHas('ticket', fn (Builder $query) => $this->applyTicketScope($query, $user))
            ->latest('changed_at')
            ->limit(10)
            ->get()
            ->map(function (TicketStatusHistory $history): array {
                $ticket = $history->ticket;

                return $this->item(
                    type: 'ticket_reopened',
                    label: 'Reopened',
                    title: 'Ticket reopened',
                    description: $ticket?->title ?: 'Ticket was reopened',
                    meta: $this->ticketMeta($ticket),
                    url: $ticket ? route('tickets.show', $ticket) : route('tickets.index'),
                    sortAt: $history->changed_at,
                    accent: '#0f766e'
                );
            });
    }

    private function csStatusUpdates(User $user): Collection
    {
        if (! $user->isCS()) {
            return collect();
        }

        return TicketStatusHistory::query()
            ->with(['ticket.teamMaster', 'ticket.categoryMaster', 'ticket.issueTypeMaster'])
            ->whereIn('to_status', ['waiting_info', 'resolved', 'closed'])
            ->where('changed_at', '>=', now()->subDays(self::RECENT_STATUS_DAYS))
            ->where(function (Builder $query) use ($user) {
                $query->whereNull('changed_by')
                    ->orWhere('changed_by', '<>', $user->id);
            })
            ->whereHas('ticket', fn (Builder $query) => $query->where('created_by', $user->id))
            ->latest('changed_at')
            ->limit(10)
            ->get()
            ->map(function (TicketStatusHistory $history): array {
                $ticket = $history->ticket;
                $status = str_replace('_', ' ', (string) $history->to_status);

                return $this->item(
                    type: 'ticket_status',
                    label: 'Ticket Update',
                    title: 'Ticket ' . Str::title($status),
                    description: $ticket?->title ?: 'Ticket status was updated',
                    meta: $this->ticketMeta($ticket),
                    url: $ticket ? route('tickets.show', $ticket) : route('tickets.index'),
                    sortAt: $history->changed_at,
                    accent: '#0284c7'
                );
            });
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
            ->with(['teamMaster', 'categoryMaster', 'issueTypeMaster'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Ticket $ticket): array => $this->item(
                type: 'team_queue',
                label: 'Team Queue',
                title: 'New ticket in team queue',
                description: $ticket->title,
                meta: $this->ticketMeta($ticket),
                url: route('tickets.show', $ticket),
                sortAt: $ticket->created_at,
                accent: '#16a34a'
            ));
    }

    private function itMyQueueTickets(User $user): Collection
    {
        if (! $user->isIT()) {
            return collect();
        }

        return Ticket::query()
            ->where('holder_id', $user->id)
            ->whereIn('status', ['in_progress', 'waiting_info'])
            ->with(['teamMaster', 'categoryMaster', 'issueTypeMaster'])
            ->latest('updated_at')
            ->limit(10)
            ->get()
            ->map(fn (Ticket $ticket): array => $this->item(
                type: 'my_queue',
                label: 'My Queue',
                title: 'Ticket needs your action',
                description: $ticket->title,
                meta: $this->ticketMeta($ticket),
                url: route('tickets.show', $ticket),
                sortAt: $ticket->updated_at,
                accent: '#2563eb'
            ));
    }

    // ========= COUNT HELPERS =========

    private function totalCount(User $user): int
    {
        return $this->resolverMessageCount($user)
            + $this->slaBreachedCount($user)
            + $this->slaWarningCount($user)
            + $this->waitingInfoCount($user)
            + $this->reopenedCount($user)
            + $this->csStatusUpdateCount($user)
            + $this->itTeamQueueCount($user)
            + $this->itMyQueueCount($user);
    }

    private function resolverMessageCount(User $user): int
    {
        return ResolverMessage::query()
            ->where('to_user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    private function slaBreachedCount(User $user): int
    {
        return $this->ticketScope($user)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->whereNotNull('sla_deadline_at')
            ->where('sla_deadline_at', '<', now())
            ->count();
    }

    private function slaWarningCount(User $user): int
    {
        return $this->ticketScope($user)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->whereNotNull('sla_deadline_at')
            ->whereBetween('sla_deadline_at', [now(), now()->addMinutes(self::SLA_WARNING_MINUTES)])
            ->count();
    }

    private function waitingInfoCount(User $user): int
    {
        $query = Ticket::query()->where('status', 'waiting_info');

        if ($user->isCS()) {
            $query->where('created_by', $user->id);
        } elseif ($user->isIT()) {
            $query->where('holder_id', $user->id);
        } elseif (! $user->isAdmin() && ! $user->isSupervisor()) {
            return 0;
        }

        return $query->count();
    }

    private function reopenedCount(User $user): int
    {
        return TicketStatusHistory::query()
            ->where('from_status', 'resolved')
            ->whereIn('to_status', ['in_progress', 'waiting_info'])
            ->where('changed_at', '>=', now()->subDays(self::RECENT_STATUS_DAYS))
            ->whereHas('ticket', fn (Builder $query) => $this->applyTicketScope($query, $user))
            ->distinct('ticket_id')
            ->count('ticket_id');
    }

    private function csStatusUpdateCount(User $user): int
    {
        if (! $user->isCS()) {
            return 0;
        }

        return TicketStatusHistory::query()
            ->whereIn('to_status', ['waiting_info', 'resolved', 'closed'])
            ->where('changed_at', '>=', now()->subDays(self::RECENT_STATUS_DAYS))
            ->where(function (Builder $query) use ($user) {
                $query->whereNull('changed_by')
                    ->orWhere('changed_by', '<>', $user->id);
            })
            ->whereHas('ticket', fn (Builder $query) => $query->where('created_by', $user->id))
            ->count();
    }

    private function itTeamQueueCount(User $user): int
    {
        if (! $user->isIT()) {
            return 0;
        }

        return Ticket::query()
            ->forTeamCode('it')
            ->where('status', 'new')
            ->whereNull('holder_id')
            ->count();
    }

    private function itMyQueueCount(User $user): int
    {
        if (! $user->isIT()) {
            return 0;
        }

        return Ticket::query()
            ->where('holder_id', $user->id)
            ->whereIn('status', ['in_progress', 'waiting_info'])
            ->count();
    }

    // ========= SHARED FORMATTERS =========

    private function ticketScope(User $user): Builder
    {
        return $this->applyTicketScope(Ticket::query(), $user);
    }

    private function applyTicketScope(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isSupervisor()) {
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
        string $type,
        string $label,
        string $title,
        string $description,
        string $meta,
        string $url,
        ?CarbonInterface $sortAt,
        string $accent
    ): array {
        return [
            'type' => $type,
            'label' => $label,
            'title' => Str::limit($title, 58),
            'description' => Str::limit($description, 96),
            'meta' => $meta,
            'url' => $url,
            'time' => $this->timeAgo($sortAt),
            'accent' => $accent,
            'sort_at' => $sortAt,
        ];
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

    private function timeAgo(?CarbonInterface $date): string
    {
        return $date ? $date->diffForHumans() : '-';
    }
}
