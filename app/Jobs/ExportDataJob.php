<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\User;
use App\Services\CaseAnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

    public function handle(CaseAnalyticsService $caseAnalyticsService): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        match ($this->type) {
            'reports_csv' => $this->exportReportsCsv(),
            'ticket_history_csv' => $this->exportTicketHistoryCsv(),
            'ticket_history_excel' => $this->exportTicketHistoryExcel(),
            'ticket_history_pdf' => $this->exportTicketHistoryPdf(),
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

    private function exportTicketHistoryCsv(): void
    {
        $path = 'exports/ticket-history/' . $this->filename;
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($handle, $this->historyHeaders());

        $this->historyQuery()->chunkById(500, function (Collection $tickets) use ($handle) {
            foreach ($tickets as $ticket) {
                fputcsv($handle, $this->historyRow($ticket));
            }
        });

        $this->storeHandle($path, $handle);
    }

    private function exportTicketHistoryExcel(): void
    {
        $path = 'exports/ticket-history/' . $this->filename;
        $handle = fopen('php://temp', 'w+');

        fwrite($handle, '<html><head><meta charset="UTF-8"></head><body><table border="1"><thead><tr>');
        foreach ($this->historyHeaders() as $header) {
            fwrite($handle, '<th>' . e($header) . '</th>');
        }
        fwrite($handle, '</tr></thead><tbody>');

        $this->historyQuery()->chunkById(500, function (Collection $tickets) use ($handle) {
            foreach ($tickets as $ticket) {
                fwrite($handle, '<tr>');
                foreach ($this->historyRow($ticket) as $cell) {
                    fwrite($handle, '<td>' . e((string) $cell) . '</td>');
                }
                fwrite($handle, '</tr>');
            }
        });

        fwrite($handle, '</tbody></table></body></html>');
        $this->storeHandle($path, $handle);
    }


    private function exportTicketHistoryPdf(): void
    {
        $rows = $this->historyQuery()
            ->limit(10000)
            ->get()
            ->map(fn (Ticket $ticket) => $this->historyRow($ticket));

        $pdf = Pdf::loadView('exports.history-pdf', [
            'headers' => $this->historyHeaders(),
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

    private function historyQuery(): Builder
    {
        $query = Ticket::query()
            ->with(['creator', 'holder'])
            ->whereIn('status', ['resolved', 'closed']);

        $q = trim((string) ($this->filters['q'] ?? ''));
        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('ticket_code', 'like', $q . '%')
                    ->when($this->supportsFullTextSearch(),
                        fn ($query) => $query->orWhereRaw('MATCH(title, description) AGAINST (? IN BOOLEAN MODE)', [$this->booleanFullTextTerm($q)]),
                        fn ($query) => $query->orWhere('title', 'like', '%' . $q . '%')->orWhere('description', 'like', '%' . $q . '%')
                    );
            });
        }

        $from = (string) ($this->filters['date_from'] ?? '');
        $to = (string) ($this->filters['date_to'] ?? '');

        if ($from !== '') {
            $query->where(function ($date) use ($from) {
                $date->where('resolved_at', '>=', $from . ' 00:00:00')
                    ->orWhere(fn ($q) => $q->whereNull('resolved_at')->where('closed_at', '>=', $from . ' 00:00:00'));
            });
        }

        if ($to !== '') {
            $query->where(function ($date) use ($to) {
                $date->where('resolved_at', '<=', $to . ' 23:59:59')
                    ->orWhere(fn ($q) => $q->whereNull('resolved_at')->where('closed_at', '<=', $to . ' 23:59:59'));
            });
        }

        return $query->orderBy('id');
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

    private function historyHeaders(): array
    {
        return ['Ticket', 'Resolved Date', 'Category', 'Team', 'Resolution Note', 'Duration (SLA)'];
    }

    private function historyRow(Ticket $ticket): array
    {
        $effective = $ticket->resolved_at ?: $ticket->closed_at ?: $ticket->updated_at ?: $ticket->created_at;

        return [
            'T-' . ($ticket->ticket_code ?: $ticket->id),
            optional($effective)?->format('Y-m-d H:i:s') ?? '',
            $ticket->category ?: $ticket->categoryMaster?->name ?: '-',
            strtoupper($ticket->displayTeamCode() ?: (string) $ticket->team),
            $ticket->status === 'closed' ? 'Closed' : 'Resolved',
            $ticket->created_at && $effective ? $ticket->created_at->diffForHumans($effective, true) : '-',
        ];
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

    private function supportsFullTextSearch(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function booleanFullTextTerm(string $term): string
    {
        $tokens = preg_split('/\s+/', mb_strtolower($term), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return collect($tokens)
            ->map(fn (string $token) => preg_replace('/[^\pL\pN_]+/u', '', $token) ?: '')
            ->filter(fn (string $token) => mb_strlen($token) >= 3)
            ->map(fn (string $token) => '+' . $token . '*')
            ->implode(' ') ?: $term;
    }

    private function storeHandle(string $path, mixed $handle): void
    {
        rewind($handle);
        Storage::disk('local')->put($path, $handle);
        fclose($handle);
    }
}
