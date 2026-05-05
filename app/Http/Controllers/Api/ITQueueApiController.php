<?php

namespace App\Http\Controllers\Api;

use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Support\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Collection;

class ITQueueApiController extends BaseApiController
{
    private const PDF_EXPORT_LIMIT = 1000;

    public function myQueue(Request $request)
    {
        $userId = $request->user()->id;

        $baseQuery = Ticket::with(['creator', 'holder'])
            ->where('team', 'it')
            ->where('holder_id', $userId);

        $newTickets = (clone $baseQuery)
            ->where('status', 'new')
            ->latest()
            ->get();

        $ongoingTickets = (clone $baseQuery)
            ->where('status', 'in_progress')
            ->latest()
            ->get();

        $waitingTickets = (clone $baseQuery)
            ->where('status', 'waiting_info')
            ->latest()
            ->get();

        $resolvedTickets = (clone $baseQuery)
            ->whereIn('status', ['resolved', 'closed'])
            ->latest()
            ->get();

        return $this->success([
            'new_tickets' => $newTickets,
            'ongoing_tickets' => $ongoingTickets,
            'waiting_tickets' => $waitingTickets,
            'resolved_tickets' => $resolvedTickets,
        ], 'My queue loaded');
    }

    public function teamQueue(Request $request)
    {
        $baseQuery = Ticket::with(['creator', 'holder'])
            ->where('team', 'it');

        $newTickets = (clone $baseQuery)
            ->where('status', 'new')
            ->whereNull('holder_id')
            ->latest()
            ->get();

        $ongoingTickets = (clone $baseQuery)
            ->where('status', 'in_progress')
            ->latest()
            ->get();

        $waitingTickets = (clone $baseQuery)
            ->where('status', 'waiting_info')
            ->latest()
            ->get();

        $resolvedTickets = (clone $baseQuery)
            ->whereIn('status', ['resolved', 'closed'])
            ->latest()
            ->get();

        return $this->success([
            'new_tickets' => $newTickets,
            'ongoing_tickets' => $ongoingTickets,
            'waiting_tickets' => $waitingTickets,
            'resolved_tickets' => $resolvedTickets,
        ], 'Team queue loaded');
    }

    public function history(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);

        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        $tickets = $this->buildHistoryQuery($request)
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginated($tickets, 'History loaded');
    }

    public function exportHistory(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'csv'));

        if (!in_array($format, ['csv', 'excel', 'xls', 'pdf'], true)) {
            return $this->error('Invalid export format', 422);
        }

        $filename = 'ticket-history-' . now()->format('Ymd-His');

        if ($format === 'csv') {
            return $this->exportHistoryCsv($request, $filename . '.csv');
        }

        if (in_array($format, ['excel', 'xls'], true)) {
            return $this->exportHistoryExcel($request, $filename . '.xls');
        }

        return $this->exportHistoryPdf($request, $filename . '.pdf');
    }

    private function buildHistoryQuery(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');
        $sortBy = (string) $request->query('sort_by', 'resolved_at');
        $sortDir = strtolower((string) $request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['ticket_code', 'resolved_at', 'category', 'team', 'duration'];

        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'resolved_at';
        }

        $query = Ticket::with(['creator', 'holder'])
            ->whereIn('status', ['resolved', 'closed']);

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('ticket_code', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('issue_type', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")
                    ->orWhere('team', 'like', "%{$q}%");
            });
        }

        $effectiveDateSql = 'COALESCE(resolved_at, closed_at, updated_at, created_at)';

        if ($dateFrom !== '') {
            $query->whereRaw("DATE({$effectiveDateSql}) >= ?", [$dateFrom]);
        }

        if ($dateTo !== '') {
            $query->whereRaw("DATE({$effectiveDateSql}) <= ?", [$dateTo]);
        }

        switch ($sortBy) {
            case 'resolved_at':
                $query->orderByRaw("{$effectiveDateSql} {$sortDir}");
                break;

            case 'duration':
                $query->orderByRaw("TIMESTAMPDIFF(SECOND, created_at, COALESCE(resolved_at, closed_at, updated_at, created_at)) {$sortDir}");
                break;

            default:
                $query->orderBy($sortBy, $sortDir);
                break;
        }

        return $query;
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
            strtoupper((string) ($ticket->team ?? '-')),
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

    private function exportHistoryCsv(Request $request, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($request) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM supaya Excel Windows aman baca karakter.
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, $this->historyExportHeaders());

            $this->buildHistoryQuery($request)
                ->chunk(500, function (Collection $tickets) use ($handle) {
                    foreach ($tickets as $ticket) {
                        fputcsv($handle, $this->historyExportRow($ticket));
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportHistoryExcel(Request $request, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($request) {
            echo '<html>';
            echo '<head>';
            echo '<meta charset="UTF-8">';
            echo '<style>';
            echo 'body{font-family:Arial,sans-serif;font-size:12px;color:#1e293b;}';
            echo 'h2{margin:0 0 12px 0;color:#051823;}';
            echo 'table{width:100%;border-collapse:collapse;}';
            echo 'th{background:#051823;color:#ffffff;font-weight:700;text-align:left;}';
            echo 'th,td{border:1px solid #cbd5e1;padding:8px;vertical-align:top;}';
            echo 'tr:nth-child(even){background:#eef3f7;}';
            echo '</style>';
            echo '</head>';
            echo '<body>';

            echo '<h2>Ticket History Repository</h2>';
            echo '<table>';

            echo '<thead><tr>';
            foreach ($this->historyExportHeaders() as $header) {
                echo '<th>' . e($header) . '</th>';
            }
            echo '</tr></thead>';

            echo '<tbody>';

            $this->buildHistoryQuery($request)
                ->chunk(500, function (Collection $tickets) {
                    foreach ($tickets as $ticket) {
                        echo '<tr>';

                        foreach ($this->historyExportRow($ticket) as $cell) {
                            echo '<td>' . e($cell) . '</td>';
                        }

                        echo '</tr>';
                    }
                });

            echo '</tbody>';
            echo '</table>';
            echo '</body>';
            echo '</html>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    private function exportHistoryPdf(Request $request, string $filename)
    {
        $filters = [
            'q' => (string) $request->query('q', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'sort_by' => (string) $request->query('sort_by', 'resolved_at'),
            'sort_dir' => (string) $request->query('sort_dir', 'desc'),
            'limit' => self::PDF_EXPORT_LIMIT,
        ];

        $tickets = $this->buildHistoryQuery($request)
            ->limit(self::PDF_EXPORT_LIMIT)
            ->get();

        $rows = $tickets
            ->map(fn (Ticket $ticket) => $this->historyExportRow($ticket))
            ->values();

        $pdf = Pdf::loadView('exports.history-pdf', [
            'headers' => $this->historyExportHeaders(),
            'rows' => $rows,
            'filters' => $filters,
            'isLimited' => $tickets->count() >= self::PDF_EXPORT_LIMIT,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    public function claim(Request $request, Ticket $ticket)
    {
        if ($ticket->team !== 'it') {
            return $this->error('Only IT tickets can be claimed', 422);
        }

        if ($ticket->holder_id !== null && (int) $ticket->holder_id !== (int) $request->user()->id) {
            return $this->error('Ticket already claimed by another resolver', 422);
        }

        $before = $this->snapshotTicket($ticket);

        DB::transaction(function () use ($request, $ticket) {
            $oldStatus = $ticket->status;

            $ticket->update([
                'holder_id' => $request->user()->id,
                'claimed_at' => now(),
                'status' => 'in_progress',
            ]);

            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'from_status' => $oldStatus,
                'to_status' => 'in_progress',
                'changed_by' => $request->user()->id,
                'changed_at' => now(),
                'note' => 'Ticket claimed by IT',
            ]);
        });

        $freshTicket = $ticket->fresh(['creator', 'holder']);

        AuditLogger::record(
            $request,
            'claimed',
            'ticket',
            $freshTicket->id,
            AuditLogger::ticketLabel($freshTicket),
            'Claimed ticket ' . AuditLogger::ticketLabel($freshTicket),
            $before,
            $this->snapshotTicket($freshTicket)
        );

        return $this->success(
            $freshTicket,
            'Ticket claimed successfully'
        );
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:new,in_progress,waiting_info,resolved,closed'],
            'note' => ['nullable', 'string'],
        ]);

        $oldStatus = $ticket->status;
        $before = $this->snapshotTicket($ticket);

        DB::transaction(function () use ($request, $ticket, $validated, $oldStatus) {
            $ticket->status = $validated['status'];

            if ($validated['status'] === 'resolved' && !$ticket->resolved_at) {
                $ticket->resolved_at = now();
            }

            if ($validated['status'] === 'closed' && !$ticket->closed_at) {
                $ticket->closed_at = now();
            }

            $ticket->save();

            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'from_status' => $oldStatus,
                'to_status' => $validated['status'],
                'changed_by' => $request->user()->id,
                'changed_at' => now(),
                'note' => $validated['note'] ?? 'Status updated by IT',
            ]);
        });

        $freshTicket = $ticket->fresh(['creator', 'holder']);

        AuditLogger::record(
            $request,
            'status_changed',
            'ticket',
            $freshTicket->id,
            AuditLogger::ticketLabel($freshTicket),
            'Changed ticket status from ' . $oldStatus . ' to ' . $validated['status'] . ' for ' . AuditLogger::ticketLabel($freshTicket),
            $before,
            $this->snapshotTicket($freshTicket)
        );

        return $this->success(
            $freshTicket,
            'Ticket status updated successfully'
        );
    }

    protected function snapshotTicket(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'ticket_code' => $ticket->ticket_code,
            'title' => $ticket->title,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'team' => $ticket->team,
            'holder_id' => $ticket->holder_id,
            'claimed_at' => optional($ticket->claimed_at)?->toISOString(),
            'resolved_at' => optional($ticket->resolved_at)?->toISOString(),
            'closed_at' => optional($ticket->closed_at)?->toISOString(),
        ];
    }

    public function ticketLabel(Ticket $ticket): string
    {
        $ticketNumber = $ticket->ticket_code ?: $ticket->id;

        $cleanCode = preg_replace('/[\s#]+/', '', (string) $ticketNumber);
        $cleanCode = preg_replace('/^T-?/i', '', $cleanCode);

        return $cleanCode ? 'T-' . $cleanCode : '-';
    }

    public function resolvedLabel(Ticket $ticket): string
    {
        $value = $ticket->resolved_at ?: $ticket->closed_at ?: $ticket->updated_at ?: $ticket->created_at;

        if (!$value) {
            return '-';
        }

        return \Carbon\Carbon::parse($value)->format('d M Y, H:i');
    }

    public function categoryLabel(Ticket $ticket): string
    {
        if (!$ticket->category) {
            return '-';
        }

        return str($ticket->category)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function resolutionLabel(Ticket $ticket): string
    {
        if ($ticket->issue_type) {
            return str($ticket->issue_type)
                ->replace('_', ' ')
                ->title()
                ->toString();
        }

        return $ticket->title ?: '-';
    }

    public function durationText(Ticket $ticket): string
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

    public function slaBadge(Ticket $ticket): string
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