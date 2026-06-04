<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\User;
use App\Queries\TicketHistoryQuery;
use App\Services\CaseAnalyticsService;
use App\Services\Tickets\TicketHistoryPresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Processes queued exports and writes generated files for download polling.
 */
class ExportDataJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 2;

    public function __construct(
        public string $type,
        public int $userId,
        public array $filters,
        public string $filename
    ) {
        $this->onQueue('exports');
    }

    public function handle(
        CaseAnalyticsService $caseAnalyticsService,
        TicketHistoryQuery $ticketHistoryQuery,
        TicketHistoryPresenter $ticketHistoryPresenter
    ): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        match ($this->type) {
            'reports_csv' => $this->exportReportsCsv(),
            'ticket_history_csv' => $this->exportTicketHistoryCsv($ticketHistoryQuery, $ticketHistoryPresenter),
            'ticket_history_excel' => $this->exportTicketHistoryExcel($ticketHistoryQuery, $ticketHistoryPresenter),
            'ticket_history_pdf' => $this->exportTicketHistoryPdf($ticketHistoryQuery, $ticketHistoryPresenter),
            'case_analytics_excel' => $this->exportCaseAnalyticsExcel($caseAnalyticsService),
            'case_analytics_pdf' => $this->exportCaseAnalyticsPdf($caseAnalyticsService),
            'audit_logs_csv' => $this->exportAuditLogsCsv(),
            'audit_logs_excel' => $this->exportAuditLogsExcel(),
            default => throw new \InvalidArgumentException('Unsupported export type: ' . $this->type),
        };
    }

    private function exportReportsCsv(): void
    {
        $path = 'exports/reports/' . $this->filename;
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($handle, ['Ticket', 'Status', 'Team', 'Priority', 'Created At', 'Resolved At', 'Closed At']);

        $this->reportTicketQuery()->chunkById(500, function (Collection $tickets) use ($handle) {
            foreach ($tickets as $ticket) {
                fputcsv($handle, [
                    $ticket->ticket_code,
                    $ticket->status,
                    strtoupper($ticket->displayTeamCode() ?: (string) $ticket->team),
                    $ticket->priority,
                    optional($ticket->created_at)?->format('Y-m-d H:i:s'),
                    optional($ticket->resolved_at)?->format('Y-m-d H:i:s'),
                    optional($ticket->closed_at)?->format('Y-m-d H:i:s'),
                ]);
            }
        });

        $this->storeHandle($path, $handle);
    }

    private function exportTicketHistoryCsv(TicketHistoryQuery $ticketHistoryQuery, TicketHistoryPresenter $presenter): void
    {
        $path = 'exports/ticket-history/' . $this->filename;
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($handle, $presenter->headers());

        $ticketHistoryQuery->buildFromFilters($this->filters)->chunkById(500, function (Collection $tickets) use ($handle, $presenter) {
            foreach ($tickets as $ticket) {
                fputcsv($handle, $presenter->row($ticket));
            }
        });

        $this->storeHandle($path, $handle);
    }

    private function exportTicketHistoryExcel(TicketHistoryQuery $ticketHistoryQuery, TicketHistoryPresenter $presenter): void
    {
        $path = 'exports/ticket-history/' . $this->filename;
        $handle = fopen('php://temp', 'w+');

        fwrite($handle, '<html><head><meta charset="UTF-8"></head><body><table border="1"><thead><tr>');
        foreach ($presenter->headers() as $header) {
            fwrite($handle, '<th>' . e($header) . '</th>');
        }
        fwrite($handle, '</tr></thead><tbody>');

        $ticketHistoryQuery->buildFromFilters($this->filters)->chunkById(500, function (Collection $tickets) use ($handle, $presenter) {
            foreach ($tickets as $ticket) {
                fwrite($handle, '<tr>');
                foreach ($presenter->row($ticket) as $cell) {
                    fwrite($handle, '<td>' . e((string) $cell) . '</td>');
                }
                fwrite($handle, '</tr>');
            }
        });

        fwrite($handle, '</tbody></table></body></html>');
        $this->storeHandle($path, $handle);
    }


    private function exportTicketHistoryPdf(TicketHistoryQuery $ticketHistoryQuery, TicketHistoryPresenter $presenter): void
    {
        $rows = $ticketHistoryQuery->buildFromFilters($this->filters)
            ->limit(10000)
            ->get()
            ->map(fn (Ticket $ticket) => $presenter->row($ticket));

        $pdf = Pdf::loadView('exports.history-pdf', [
            'headers' => $presenter->headers(),
            'rows' => $rows,
            'filters' => $this->filters,
            'isLimited' => $rows->count() >= 10000,
        ])->setPaper('a4', 'landscape');

        Storage::disk('local')->put('exports/ticket-history/' . $this->filename, $pdf->output());
    }

    private function exportCaseAnalyticsExcel(CaseAnalyticsService $service): void
    {
        $payload = $service->analyticsPayload(
            $this->filters['time_range'] ?? '1y',
            $this->filters['team'] ?? 'all'
        );

        Storage::disk('local')->put(
            'exports/case-analytics/' . $this->filename,
            view('exports.case-analytics-excel', $service->exportViewData($payload))->render()
        );
    }

    private function exportCaseAnalyticsPdf(CaseAnalyticsService $service): void
    {
        $payload = $service->analyticsPayload(
            $this->filters['time_range'] ?? '1y',
            $this->filters['team'] ?? 'all'
        );

        $pdf = Pdf::loadView(
            'exports.case-analytics-pdf',
            $service->exportViewData($payload)
        )->setPaper('a4', 'landscape');

        Storage::disk('local')->put(
            'exports/case-analytics/' . $this->filename,
            $pdf->output()
        );
    }

    private function exportAuditLogsCsv(): void
    {
        $path = 'exports/audit-logs/' . $this->filename;
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($handle, $this->auditHeaders());

        $this->auditQuery()->chunkById(500, function (Collection $logs) use ($handle) {
            foreach ($logs as $log) {
                fputcsv($handle, $this->auditRow($log));
            }
        });

        $this->storeHandle($path, $handle);
    }

    private function exportAuditLogsExcel(): void
    {
        $path = 'exports/audit-logs/' . $this->filename;
        $handle = fopen('php://temp', 'w+');

        fwrite($handle, '<html><head><meta charset="UTF-8"></head><body><table border="1"><thead><tr>');
        foreach ($this->auditHeaders() as $header) {
            fwrite($handle, '<th>' . e($header) . '</th>');
        }
        fwrite($handle, '</tr></thead><tbody>');

        $this->auditQuery()->chunkById(500, function (Collection $logs) use ($handle) {
            foreach ($logs as $log) {
                fwrite($handle, '<tr>');
                foreach ($this->auditRow($log) as $cell) {
                    fwrite($handle, '<td style="vertical-align:top;white-space:pre-wrap;">' . e((string) $cell) . '</td>');
                }
                fwrite($handle, '</tr>');
            }
        });

        fwrite($handle, '</tbody></table></body></html>');
        $this->storeHandle($path, $handle);
    }


    private function reportTicketQuery(): Builder
    {
        $user = User::findOrFail($this->userId);
        $query = Ticket::query()->with(['teamMaster']);

        $scope = $this->filters['scope'] ?? 'my';
        if ($scope === 'all' && ($user->isAdmin() || $user->isSupervisor())) {
            // Global export intentionally unscoped for authorized roles.
        } elseif ($scope === 'team') {
            if ($user->isIT()) {
                $query->forTeamCode('it');
            } elseif ($user->isCS()) {
                $query->where('created_by', $user->id);
            } else {
                $query->where('created_by', $user->id);
            }
        } else {
            if ($user->isIT()) {
                $query->where('holder_id', $user->id);
            } else {
                $query->where('created_by', $user->id);
            }
        }

        return $query
            ->whereBetween('created_at', [$this->filters['date_from'], $this->filters['date_to']])
            ->orderBy('id');
    }

    private function auditQuery(): Builder
    {
        $query = AuditLog::query()->with('actor');

        $search = trim((string) ($this->filters['q'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('actor_name', 'like', '%' . $search . '%')
                    ->orWhere('actor_email', 'like', '%' . $search . '%')
                    ->orWhere('action', 'like', '%' . $search . '%')
                    ->orWhere('entity_type', 'like', '%' . $search . '%')
                    ->orWhere('entity_label', 'like', '%' . $search . '%');
            });
        }

        foreach (['action', 'entity'] as $filter) {
            $value = (string) ($this->filters[$filter] ?? 'all');
            if ($value !== 'all') {
                $query->where($filter === 'entity' ? 'entity_type' : 'action', $value);
            }
        }

        if (($this->filters['date_range'] ?? '') === 'today') {
            $query->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()]);
        } elseif (($this->filters['date_range'] ?? '') === '7d') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif (($this->filters['date_range'] ?? '') === '30d') {
            $query->where('created_at', '>=', now()->subDays(30));
        } elseif (($this->filters['date_range'] ?? '') === 'custom') {
            if (!empty($this->filters['date_from'])) {
                $query->where('created_at', '>=', $this->filters['date_from'] . ' 00:00:00');
            }
            if (!empty($this->filters['date_to'])) {
                $query->where('created_at', '<=', $this->filters['date_to'] . ' 23:59:59');
            }
        }

        return $query->orderBy('id');
    }

    private function auditHeaders(): array
    {
        return ['Timestamp', 'Actor', 'Role', 'Action', 'Entity', 'Entity ID', 'Description', 'IP Address', 'User Agent', 'Before Values', 'After Values'];
    }

    private function auditRow(AuditLog $log): array
    {
        return [
            optional($log->created_at)?->format('Y-m-d H:i:s') ?? '',
            $log->actor_name ?: $log->actor?->name ?: 'System',
            $log->actor_role ?: $log->actor?->role ?: '',
            $log->action,
            $log->entity_type,
            $log->entity_id ?? '',
            $log->description ?? '',
            $log->ip_address ?? '',
            $log->user_agent ?? '',
            $this->jsonValue($log->before_values),
            $this->jsonValue($log->after_values),
        ];
    }

    private function jsonValue(mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        return is_string($value) ? $value : (json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
    }

    private function storeHandle(string $path, mixed $handle): void
    {
        rewind($handle);
        Storage::disk('local')->put($path, $handle);
        fclose($handle);
    }
}
