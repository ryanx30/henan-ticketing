<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Queries\TicketHistoryQuery;
use App\Services\Tickets\TicketHistoryPresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Generates large history PDF exports outside the request cycle.
 */
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

    public function handle(TicketHistoryQuery $ticketHistoryQuery, TicketHistoryPresenter $presenter): void
    {
        $limit = $this->exportLimit();

        $tickets = $ticketHistoryQuery
            ->buildFromFilters($this->filters)
            ->limit($limit)
            ->get();

        $rows = $tickets
            ->map(fn (Ticket $ticket) => $presenter->row($ticket))
            ->values();

        $pdf = Pdf::loadView('exports.history-pdf', [
            'headers' => $presenter->headers(),
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

    private function exportLimit(): int
    {
        $limit = (int) ($this->filters['async_limit'] ?? 10000);

        if ($limit <= 0) {
            return 10000;
        }

        return min($limit, 50000);
    }
}
