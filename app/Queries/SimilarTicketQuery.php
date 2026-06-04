<?php

namespace App\Queries;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Finds related tickets using classification and title signals to support duplicate checking.
 */
final class SimilarTicketQuery
{
    public function __construct(private TicketIndexQuery $ticketIndexQuery)
    {
    }

    public function forTicket(Ticket $ticket, User $viewer, int $limit = 5): Collection
    {
        $words = collect(preg_split('/\s+/', trim((string) $ticket->title)))
            ->map(fn ($word) => mb_strtolower(preg_replace('/[^\pL\pN_]+/u', '', (string) $word)))
            ->filter(fn ($word) => mb_strlen((string) $word) >= 4)
            ->unique()
            ->take(4)
            ->values();

        $candidateWindowStart = optional($ticket->created_at)->copy()?->subMonths(6) ?? now()->subMonths(6);

        $query = Ticket::query()
            ->whereKeyNot($ticket->id)
            ->where('created_at', '>=', $candidateWindowStart)
            ->with(['creator', 'holder'])
            ->latest()
            ->limit(50);

        $this->ticketIndexQuery->scopeForViewer($query, $viewer);

        $query->where(function (Builder $candidate) use ($ticket, $words) {
            if ($ticket->team_id) {
                $candidate->where('team_id', $ticket->team_id);
            } elseif ($ticket->team) {
                $candidate->where('team', $ticket->team);
            }

            if ($ticket->category_id) {
                $candidate->orWhere('category_id', $ticket->category_id);
            } elseif ($ticket->category) {
                $candidate->orWhere('category', $ticket->category);
            }

            if ($ticket->issue_type_id) {
                $candidate->orWhere('issue_type_id', $ticket->issue_type_id);
            } elseif ($ticket->issue_type) {
                $candidate->orWhere('issue_type', $ticket->issue_type);
            }

            if ($words->isNotEmpty()) {
                if ($this->supportsFullTextSearch()) {
                    $candidate->orWhereRaw(
                        'MATCH(title, description) AGAINST (? IN BOOLEAN MODE)',
                        [$this->booleanFullTextTerm($words->implode(' '))]
                    );
                } else {
                    foreach ($words as $word) {
                        $candidate->orWhere('title', 'like', '%' . $word . '%');
                    }
                }
            }
        });

        return $query->get([
            'id', 'ticket_code', 'title', 'status', 'priority', 'created_at',
            'category', 'issue_type', 'team', 'category_id', 'issue_type_id', 'team_id',
            'created_by', 'holder_id',
        ])->map(function (Ticket $item) use ($ticket, $words) {
            $item->similarity_score = $this->score($ticket, $item, $words);

            return $item;
        })->filter(fn (Ticket $item) => $item->similarity_score > 0)
            ->sortByDesc('similarity_score')
            ->take($limit)
            ->values();
    }

    private function score(Ticket $source, Ticket $candidate, $words): int
    {
        $score = 0;

        if ($source->issue_type_id && $source->issue_type_id === $candidate->issue_type_id) {
            $score += 4;
        } elseif ($this->sameText($source->issue_type, $candidate->issue_type)) {
            $score += 3;
        }

        if ($source->category_id && $source->category_id === $candidate->category_id) {
            $score += 3;
        } elseif ($this->sameText($source->category, $candidate->category)) {
            $score += 2;
        }

        if ($source->team_id && $source->team_id === $candidate->team_id) {
            $score += 2;
        } elseif ($this->sameText($source->team, $candidate->team)) {
            $score += 1;
        }

        $title = mb_strtolower((string) $candidate->title);
        foreach ($words as $word) {
            if (str_contains($title, $word)) {
                $score += 2;
            }
        }

        return $score;
    }

    private function sameText(?string $left, ?string $right): bool
    {
        return $left !== null && $right !== null && mb_strtolower($left) === mb_strtolower($right);
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
