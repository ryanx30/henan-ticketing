<?php

namespace App\Queries;

use App\Models\Ticket;
use App\Models\User;
use App\Support\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class TicketIndexQuery
{
    public function build(Request $request, User $viewer): Builder
    {
        $query = Ticket::query()
            ->with(['creator', 'holder', 'teamMaster', 'categoryMaster', 'issueTypeMaster', 'priorityMaster']);

        $this->scopeForViewer($query, $viewer, $request);
        $this->applySearch($query, trim((string) $request->query('q', '')));
        $this->applyStatus($query, (string) $request->query('status', 'all'));
        $this->applyPriority($query, (string) $request->query('priority', 'all'));
        $this->applyCreatedRange($query, (string) $request->query('date_from', ''), (string) $request->query('date_to', ''));
        $this->applyFocus($query, (string) $request->query('focus', ''));
        $this->applySorting($query, (string) $request->query('sort_by', 'created_at'), (string) $request->query('sort_dir', 'desc'));

        return $query;
    }

    public function scopeForViewer(Builder $query, User $viewer, ?Request $request = null): void
    {
        if ($viewer->isAdmin() || $viewer->isSupervisor()) {
            return;
        }

        if ($viewer->isCS()) {
            if ($request && $request->boolean('mine')) {
                $query->where('created_by', $viewer->id);
            }

            return;
        }

        if ($viewer->isIT()) {
            $query->forTeamCode('it')
                ->where(function (Builder $query) use ($viewer) {
                    $query->whereNull('holder_id')
                        ->orWhere('holder_id', $viewer->id);
                });
        }
    }

    private function applySearch(Builder $query, string $keyword): void
    {
        if ($keyword === '') {
            return;
        }

        $query->where(function (Builder $search) use ($keyword) {
            $search->where('ticket_code', 'like', $keyword . '%');

            if (ctype_digit($keyword)) {
                $search->orWhereKey((int) $keyword);
            }

            if (mb_strlen($keyword) >= 3 && $this->supportsFullTextSearch()) {
                $search->orWhereRaw(
                    'MATCH(title, description) AGAINST (? IN BOOLEAN MODE)',
                    [$this->booleanFullTextTerm($keyword)]
                );
                return;
            }

            $search->orWhere('title', 'like', '%' . $keyword . '%')
                ->orWhere('description', 'like', '%' . $keyword . '%');
        });
    }

    private function applyStatus(Builder $query, string $status): void
    {
        if ($status === 'all' || $status === '') {
            return;
        }

        $status = TicketStatus::normalize($status);

        if (TicketStatus::isCanonical($status)) {
            $query->where('status', $status);
        }
    }

    private function applyPriority(Builder $query, string $priority): void
    {
        if ($priority !== 'all' && $priority !== '') {
            $query->forPriorityCode($priority);
        }
    }

    private function applyCreatedRange(Builder $query, string $from, string $to): void
    {
        if ($from !== '') {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }

        if ($to !== '') {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }
    }

    private function applyFocus(Builder $query, string $focus): void
    {
        match ($focus) {
            'sla_risk' => $query->whereIn('status', TicketStatus::activeValues())
                ->whereNotNull('sla_deadline_at')
                ->whereBetween('sla_deadline_at', [now(), now()->copy()->addMinutes(59)]),
            'due_today' => $query->whereIn('status', TicketStatus::activeValues())
                ->whereBetween('sla_deadline_at', [now()->startOfDay(), now()->endOfDay()]),
            'reopened' => $query->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('ticket_status_histories')
                    ->whereColumn('ticket_status_histories.ticket_id', 'tickets.id')
                    ->whereIn('ticket_status_histories.to_status', [TicketStatus::IN_PROGRESS, TicketStatus::WAITING_INFO])
                    ->where('ticket_status_histories.from_status', TicketStatus::RESOLVED)
                    ->whereBetween('ticket_status_histories.changed_at', [now()->startOfDay(), now()->endOfDay()]);
            }),
            default => null,
        };
    }

    private function applySorting(Builder $query, string $sortBy, string $sortDir): void
    {
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['ticket_code', 'title', 'priority', 'category', 'team', 'status', 'created_at'];

        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        // Keep this query database-agnostic: no MySQL FIELD() ordering in controllers or query objects.
        $query->orderBy($sortBy, $sortDir);

        if ($sortBy !== 'created_at') {
            $query->orderByDesc('created_at');
        }
    }

    private function supportsFullTextSearch(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function booleanFullTextTerm(string $keyword): string
    {
        $tokens = collect(preg_split('/\s+/', $keyword))
            ->map(fn ($token) => preg_replace('/[^\pL\pN_]+/u', '', (string) $token))
            ->filter(fn ($token) => mb_strlen((string) $token) >= 2)
            ->map(fn ($token) => '+' . $token . '*')
            ->values();

        return $tokens->isEmpty() ? $keyword : $tokens->implode(' ');
    }
}
