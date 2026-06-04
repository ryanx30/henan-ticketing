<?php

namespace App\Queries;

use App\Models\Ticket;
use App\Support\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Builds the resolved and closed ticket history query shared by the page and export jobs.
 */
final class TicketHistoryQuery
{
    public function build(Request $request): Builder
    {
        return $this->buildFromFilters($this->filtersFromRequest($request));
    }

    public function buildFromFilters(array $filters): Builder
    {
        $query = Ticket::query()
            ->with(['creator', 'holder', 'teamMaster', 'categoryMaster', 'issueTypeMaster', 'priorityMaster'])
            ->whereIn('status', TicketStatus::completedValues());

        $this->applySearch($query, trim((string) ($filters['q'] ?? '')));
        $this->applyEffectiveDateRange($query, (string) ($filters['date_from'] ?? ''), (string) ($filters['date_to'] ?? ''));
        $this->applySorting($query, (string) ($filters['sort_by'] ?? 'resolved_at'), (string) ($filters['sort_dir'] ?? 'desc'));

        return $query;
    }

    public function filtersFromRequest(Request $request): array
    {
        return [
            'q' => (string) $request->query('q', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'sort_by' => (string) $request->query('sort_by', 'resolved_at'),
            'sort_dir' => (string) $request->query('sort_dir', 'desc'),
        ];
    }

    private function applySearch(Builder $query, string $keyword): void
    {
        if ($keyword === '') {
            return;
        }

        $query->where(function (Builder $search) use ($keyword) {
            $search->where('ticket_code', 'like', $keyword . '%')
                ->orWhere('title', 'like', '%' . $keyword . '%')
                ->orWhere('description', 'like', '%' . $keyword . '%')
                ->orWhere('issue_type', 'like', '%' . $keyword . '%')
                ->orWhere('category', 'like', '%' . $keyword . '%')
                ->orWhere('team', 'like', '%' . $keyword . '%');
        });
    }

    private function applyEffectiveDateRange(Builder $query, string $from, string $to): void
    {
        if ($from !== '') {
            $start = $from . ' 00:00:00';
            $query->where(function (Builder $date) use ($start) {
                $date->where('resolved_at', '>=', $start)
                    ->orWhere(fn (Builder $fallback) => $fallback->whereNull('resolved_at')->where('closed_at', '>=', $start))
                    ->orWhere(fn (Builder $fallback) => $fallback->whereNull('resolved_at')->whereNull('closed_at')->where('updated_at', '>=', $start));
            });
        }

        if ($to !== '') {
            $end = $to . ' 23:59:59';
            $query->where(function (Builder $date) use ($end) {
                $date->where('resolved_at', '<=', $end)
                    ->orWhere(fn (Builder $fallback) => $fallback->whereNull('resolved_at')->where('closed_at', '<=', $end))
                    ->orWhere(fn (Builder $fallback) => $fallback->whereNull('resolved_at')->whereNull('closed_at')->where('updated_at', '<=', $end));
            });
        }
    }

    private function applySorting(Builder $query, string $sortBy, string $sortDir): void
    {
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
        $allowed = ['ticket_code', 'resolved_at', 'closed_at', 'updated_at', 'category', 'team', 'created_at', 'duration'];

        if (!in_array($sortBy, $allowed, true)) {
            $sortBy = 'resolved_at';
        }

        if ($sortBy === 'resolved_at') {
            $query->orderBy('resolved_at', $sortDir)
                ->orderBy('closed_at', $sortDir)
                ->orderBy('updated_at', $sortDir)
                ->orderBy('created_at', $sortDir);
            return;
        }

        if ($sortBy === 'duration') {
            $query->orderBy('created_at', $sortDir)
                ->orderBy('resolved_at', $sortDir)
                ->orderBy('closed_at', $sortDir);
            return;
        }

        $query->orderBy($sortBy, $sortDir);
    }
}
