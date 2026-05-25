<?php

namespace App\Jobs;

use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExportTicketHistoryPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 180;

    public function __construct(
        public int $requestedBy,
        public array $filters,
        public string $filename
    ) {
    }

    public function handle(): void
    {
        $limit = $this->exportLimit();

        $tickets = $this->buildHistoryQuery()
            ->limit($limit)
            ->get();

        $rows = $tickets
            ->map(fn (Ticket $ticket) => $this->historyExportRow($ticket))
            ->values();

        $pdf = Pdf::loadView('exports.history-pdf', [
            'headers' => $this->historyExportHeaders(),
            'rows' => $rows,
            'filters' => array_merge($this->filters, [
                'queued' => true,
                'requested_by' => $this->requestedBy,
                'limit' => $limit,
            ]),
            'isLimited' => $tickets->count() >= $limit,
        ])->setPaper('a4', 'landscape');

        Storage::disk('local')->put(
            'exports/ticket-history/' . $this->filename,
            $pdf->output()
        );
    }

    private function buildHistoryQuery(): Builder
    {
        $q = trim((string) ($this->filters['q'] ?? ''));
        $dateFrom = (string) ($this->filters['date_from'] ?? '');
        $dateTo = (string) ($this->filters['date_to'] ?? '');
        $sortBy = (string) ($this->filters['sort_by'] ?? 'resolved_at');
        $sortDir = strtolower((string) ($this->filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['ticket_code', 'resolved_at', 'category', 'team', 'duration'];

        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'resolved_at';
        }

        $query = Ticket::with(['creator', 'holder'])
            ->whereIn('status', ['resolved', 'closed']);

        if ($q !== '') {
            $query->where(function (Builder $query) use ($q) {
                $query->where('ticket_code', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('issue_type', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")
                    ->orWhere('team', 'like', "%{$q}%");
            });
        }

        if ($dateFrom !== '') {
            $query->where(function ($q) use ($dateFrom) {
                $q->where('resolved_at', '>=', $dateFrom . ' 00:00:00')
                    ->orWhere(function ($qq) use ($dateFrom) {
                        $qq->whereNull('resolved_at')->where('closed_at', '>=', $dateFrom . ' 00:00:00');
                    })
                    ->orWhere(function ($qq) use ($dateFrom) {
                        $qq->whereNull('resolved_at')->whereNull('closed_at')->where('updated_at', '>=', $dateFrom . ' 00:00:00');
                    });
            });
        }

        if ($dateTo !== '') {
            $query->where(function ($q) use ($dateTo) {
                $q->where('resolved_at', '<=', $dateTo . ' 23:59:59')
                    ->orWhere(function ($qq) use ($dateTo) {
                        $qq->whereNull('resolved_at')->where('closed_at', '<=', $dateTo . ' 23:59:59');
                    })
                    ->orWhere(function ($qq) use ($dateTo) {
                        $qq->whereNull('resolved_at')->whereNull('closed_at')->where('updated_at', '<=', $dateTo . ' 23:59:59');
                    });
            });
        }

        // Keep the export query database-agnostic. Duration is displayed in PHP; for duration sort,
        // use created_at as a stable proxy instead of MySQL-only TIMESTAMPDIFF().
        match ($sortBy) {
            'resolved_at' => $query
                ->orderBy('resolved_at', $sortDir)
                ->orderBy('closed_at', $sortDir)
                ->orderBy('updated_at', $sortDir)
                ->orderBy('created_at', $sortDir),
            'duration' => $query
                ->orderBy('created_at', $sortDir)
                ->orderBy('resolved_at', $sortDir)
                ->orderBy('closed_at', $sortDir),
            default => $query->orderBy($sortBy, $sortDir),
        };

        return $query;
    }

    private function exportLimit(): int
    {
        $limit = (int) ($this->filters['async_limit'] ?? 10000);

        if ($limit <= 0) {
            return 10000;
        }

        return min($limit, 50000);
    }

    private function historyExportHeaders(): array
    {
        return [
            'Ticket',
            'Resolved Date',
            'Category',
            'Team',
            'Resolution Note',
            'Duration (SLA)',
        ];
    }

    private function historyExportRow(Ticket $ticket): array
    {
        return [
            $this->ticketLabel($ticket),
            $this->resolvedLabel($ticket),
            $this->categoryLabel($ticket),
            strtoupper($ticket->displayTeamCode() ?: '-'),
            $this->resolutionLabel($ticket),
            $this->durationSlaText($ticket),
        ];
    }

    private function durationSlaText(Ticket $ticket): string
    {
        $duration = $this->durationText($ticket);
        $sla = $this->slaBadge($ticket);

        if ($sla === '') {
            return $duration;
        }

        return $duration . ' (' . $sla . ')';
    }

    private function ticketLabel(Ticket $ticket): string
    {
        $ticketNumber = $ticket->ticket_code ?: $ticket->id;

        $cleanCode = preg_replace('/[\s#]+/', '', (string) $ticketNumber);
        $cleanCode = preg_replace('/^T-?/i', '', $cleanCode);

        return $cleanCode ? 'T-' . $cleanCode : '-';
    }

    private function resolvedLabel(Ticket $ticket): string
    {
        $value = $ticket->resolved_at ?: $ticket->closed_at ?: $ticket->updated_at ?: $ticket->created_at;

        if (!$value) {
            return '-';
        }

        return \Carbon\Carbon::parse($value)->format('d M Y, H:i');
    }

    private function categoryLabel(Ticket $ticket): string
    {
        if (!$ticket->category) {
            return '-';
        }

        return str($ticket->category)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    private function resolutionLabel(Ticket $ticket): string
    {
        if ($ticket->issue_type) {
            return str($ticket->issue_type)
                ->replace('_', ' ')
                ->title()
                ->toString();
        }

        return $ticket->title ?: '-';
    }

    private function durationText(Ticket $ticket): string
    {
        $start = $ticket->created_at ? \Carbon\Carbon::parse($ticket->created_at) : null;
        $endValue = $ticket->resolved_at ?: $ticket->closed_at ?: $ticket->updated_at;

        if (!$start || !$endValue) {
            return '-';
        }

        $end = \Carbon\Carbon::parse($endValue);
        $seconds = abs($start->diffInSeconds($end));

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($days > 0) {
            return "{$days}d {$hours}h {$minutes}m {$remainingSeconds}s";
        }

        if ($hours > 0) {
            return "{$hours}h {$minutes}m {$remainingSeconds}s";
        }

        if ($minutes > 0) {
            return "{$minutes}m {$remainingSeconds}s";
        }

        return "{$remainingSeconds}s";
    }

    private function slaBadge(Ticket $ticket): string
    {
        $endValue = $ticket->resolved_at ?: $ticket->closed_at;

        if (!$ticket->sla_deadline_at || !$endValue) {
            return '';
        }

        return \Carbon\Carbon::parse($endValue)->lte(\Carbon\Carbon::parse($ticket->sla_deadline_at))
            ? 'Met'
            : 'Breached';
    }
}
