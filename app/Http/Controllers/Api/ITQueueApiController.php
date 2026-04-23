<?php

namespace App\Http\Controllers\Api;

use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ITQueueApiController extends BaseApiController
{
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

        $query = $this->buildHistoryQuery($request);
        $tickets = $query->paginate($perPage)->withQueryString();

        return $this->paginated($tickets, 'History loaded');
    }

    public function exportHistory(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'csv'));

        if (!in_array($format, ['csv', 'pdf'], true)) {
            return $this->error('Invalid export format', 422);
        }

        $tickets = $this->buildHistoryQuery($request)->get();

        $filename = 'ticket-history-' . now()->format('Ymd-His');

        if ($format === 'csv') {
            return $this->exportHistoryCsv($tickets, $filename . '.csv');
        }

        return $this->exportHistoryPdf($tickets, $filename . '.pdf', $request);
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

        $effectiveDateSql = "COALESCE(resolved_at, closed_at, updated_at, created_at)";

        if ($dateFrom !== '') {
            $query->whereRaw("DATE($effectiveDateSql) >= ?", [$dateFrom]);
        }

        if ($dateTo !== '') {
            $query->whereRaw("DATE($effectiveDateSql) <= ?", [$dateTo]);
        }

        switch ($sortBy) {
            case 'resolved_at':
                $query->orderByRaw("$effectiveDateSql $sortDir");
                break;

            case 'duration':
                $query->orderByRaw("TIMESTAMPDIFF(SECOND, created_at, COALESCE(resolved_at, closed_at, updated_at, created_at)) $sortDir");
                break;

            default:
                $query->orderBy($sortBy, $sortDir);
                break;
        }

        return $query;
    }

    private function exportHistoryCsv($tickets, string $filename): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($tickets) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Ticket',
                'Resolved Date',
                'Category',
                'Team',
                'Resolution Note',
                'Duration',
                'SLA Status',
                'Title',
                'Status',
            ]);

            foreach ($tickets as $t) {
                fputcsv($handle, [
                    $this->ticketLabel($t),
                    $this->resolvedLabel($t),
                    $this->categoryLabel($t),
                    strtoupper((string) ($t->team ?? '-')),
                    $this->resolutionLabel($t),
                    $this->durationText($t),
                    $this->slaBadge($t),
                    $t->title ?? '-',
                    $t->status ?? '-',
                ]);
            }

            fclose($handle);
        }, $filename, $headers);
    }

    private function exportHistoryPdf($tickets, string $filename, Request $request)
    {
        $filters = [
            'q' => (string) $request->query('q', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'sort_by' => (string) $request->query('sort_by', 'resolved_at'),
            'sort_dir' => (string) $request->query('sort_dir', 'desc'),
        ];

        $pdf = Pdf::loadView('exports.history-pdf', [
            'tickets' => $tickets,
            'filters' => $filters,
            'helper' => $this,
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

        DB::transaction(function () use ($request, $ticket) {
            $oldStatus = $ticket->status;

            $ticket->update([
                'holder_id' => $request->user()->id,
                'claimed_at' => now(),
                'status' => 'in_progress',
            ]);

            TicketStatusHistory::create([
                'ticket_id'   => $ticket->id,
                'from_status' => $oldStatus,
                'to_status'   => 'in_progress',
                'changed_by'  => $request->user()->id,
                'changed_at'  => now(),
                'note'        => 'Ticket claimed by IT',
            ]);
        });

        return $this->success(
            $ticket->fresh(['creator', 'holder']),
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
                'ticket_id'   => $ticket->id,
                'from_status' => $oldStatus,
                'to_status'   => $validated['status'],
                'changed_by'  => $request->user()->id,
                'changed_at'  => now(),
                'note'        => $validated['note'] ?? 'Status updated by IT',
            ]);
        });

        return $this->success(
            $ticket->fresh(['creator', 'holder']),
            'Ticket status updated successfully'
        );
    }

    public function ticketLabel($t): string
    {
        $ticketNumber = $t->ticket_code ?: $t->id;
        $ticketNumber = preg_replace('/^#?T-?/i', '', (string) $ticketNumber);

        return '#T-' . $ticketNumber;
    }

    public function resolvedLabel($t): string
    {
        $value = $t->resolved_at ?: $t->closed_at ?: $t->updated_at ?: $t->created_at;
        if (!$value) {
            return '-';
        }

        return \Carbon\Carbon::parse($value)->format('d M, H:i');
    }

    public function categoryLabel($t): string
    {
        if (!$t->category) {
            return '-';
        }

        return str($t->category)->replace('_', ' ')->title()->toString();
    }

    public function resolutionLabel($t): string
    {
        if ($t->issue_type) {
            return str($t->issue_type)->replace('_', ' ')->title()->toString();
        }

        return $t->title ?: '-';
    }

    public function durationText($t): string
    {
        $start = $t->created_at ? \Carbon\Carbon::parse($t->created_at) : null;
        $endValue = $t->resolved_at ?: $t->closed_at ?: $t->updated_at;

        if (!$start || !$endValue) {
            return '-';
        }

        $end = \Carbon\Carbon::parse($endValue);
        $minutes = abs($start->diffInMinutes($end));

        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $mins = $minutes % 60;

        if ($days > 0) {
            return "{$days}d {$hours}h {$mins}m";
        }

        if ($hours > 0) {
            return "{$hours}h {$mins}m";
        }

        return "{$mins}m";
    }

    public function slaBadge($t): string
    {
        $endValue = $t->resolved_at ?: $t->closed_at;

        if (!$t->sla_deadline_at || !$endValue) {
            return '';
        }

        return \Carbon\Carbon::parse($endValue)->lte(\Carbon\Carbon::parse($t->sla_deadline_at))
            ? 'Met'
            : 'Breached';
    }
}