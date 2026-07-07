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

    private const AUDIT_ENTITY_GROUPS = [
        'ticket' => ['ticket'],
        'user' => ['user'],
        'resolver_inbox' => ['resolver_message'],
        'master_data' => ['category', 'categorie', 'issue_type', 'team', 'priority', 'prioritie', 'sla_rule'],
    ];

    private const AUDIT_MASTER_DATA_ENTITY_FILTERS = [
        'categories' => ['category', 'categorie'],
        'issue_types' => ['issue_type'],
        'teams' => ['team'],
        'priorities' => ['priority', 'prioritie'],
        'sla_rules' => ['sla_rule'],
    ];

    private const AUDIT_ACTION_FILTERS = [
        'created' => ['created'],
        'updated' => ['updated'],
        'edit' => ['updated'],
        'status_change' => ['status_changed', 'deactivated', 'activated', 'reactivated', 'claimed'],
        'handoff' => ['holder_transferred'],
    ];

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
        $scope = (string) ($this->filters['scope'] ?? 'my');
        $selectedUserId = $this->normalizedReportUserId($user);

        if ($selectedUserId && $user->isHeadCS()) {
            $query->where('created_by', $selectedUserId);
        } elseif ($selectedUserId && $user->isAdmin()) {
            $query->where('holder_id', $selectedUserId);
        } else {
            $this->applyReportScope($query, $user, $scope);
        }

        return $query
            ->where(function (Builder $range) {
                $range->whereBetween('created_at', [$this->filters['date_from'], $this->filters['date_to']])
                    ->orWhereBetween('resolved_at', [$this->filters['date_from'], $this->filters['date_to']])
                    ->orWhere(fn (Builder $fallback) => $fallback
                        ->whereNull('resolved_at')
                        ->whereBetween('closed_at', [$this->filters['date_from'], $this->filters['date_to']]));
            })
            ->orderBy('id');
    }

    private function applyReportScope(Builder $query, User $user, string $scope): void
    {
        if ($scope === 'it_performance' && $user->isAdmin()) {
            $query->whereIn('holder_id', User::query()->where('role', User::ROLE_IT)->select('id'));
            return;
        }

        if ($scope === 'cs_performance' && $user->isHeadCS()) {
            $query->whereIn('created_by', User::query()->where('role', User::ROLE_CS)->select('id'));
            return;
        }

        if ($scope === 'all' && $user->isSupervisor()) {
            return;
        }

        if ($user->isIT()) {
            if ($scope === 'team') {
                $query->forTeamCode('it');
                return;
            }

            $query->where('holder_id', $user->id);
            return;
        }

        if ($user->isCS()) {
            $query->where('created_by', $user->id);
            return;
        }

        $query->where('created_by', $user->id);
    }

    private function normalizedReportUserId(User $user): ?int
    {
        $selectedUserId = (int) ($this->filters['selected_user_id'] ?? 0);

        if ($selectedUserId <= 0) {
            return null;
        }

        if ($user->isHeadCS()) {
            return User::query()->whereKey($selectedUserId)->where('role', User::ROLE_CS)->exists()
                ? $selectedUserId
                : null;
        }

        if ($user->isAdmin()) {
            return User::query()->whereKey($selectedUserId)->where('role', User::ROLE_IT)->exists()
                ? $selectedUserId
                : null;
        }

        return null;
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

                if (($this->filters['entity_group'] ?? 'ticket') === 'master_data') {
                    $q->orWhere('change_reason', 'like', '%' . $search . '%');
                }
            });
        }

        $entityGroup = (string) ($this->filters['entity_group'] ?? 'ticket');
        $entityGroup = array_key_exists($entityGroup, self::AUDIT_ENTITY_GROUPS) ? $entityGroup : 'ticket';

        $query->whereIn('entity_type', self::AUDIT_ENTITY_GROUPS[$entityGroup]);

        $entityType = (string) ($this->filters['entity_type'] ?? 'all');
        if ($entityGroup === 'master_data' && array_key_exists($entityType, self::AUDIT_MASTER_DATA_ENTITY_FILTERS)) {
            $query->whereIn('entity_type', self::AUDIT_MASTER_DATA_ENTITY_FILTERS[$entityType]);
        }

        $action = (string) ($this->filters['action'] ?? 'all');
        if ($action !== 'all') {
            if (array_key_exists($action, self::AUDIT_ACTION_FILTERS)) {
                $query->whereIn('action', self::AUDIT_ACTION_FILTERS[$action]);
            } else {
                $query->where('action', $action);
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
        $headers = ['Timestamp', 'Actor', 'Role', 'Action', 'Entity', 'Entity ID', 'Description'];

        if ($this->auditIncludesReason()) {
            $headers[] = 'Reason';
        }

        return array_merge($headers, ['IP Address', 'User Agent', 'Before Values', 'After Values']);
    }

    private function auditRow(AuditLog $log): array
    {
        $row = [
            optional($log->created_at)?->format('Y-m-d H:i:s') ?? '',
            $log->actor_name ?: $log->actor?->name ?: 'System',
            $log->actor_role ?: $log->actor?->role ?: '',
            $log->action,
            $log->entity_type,
            $log->entity_id ?? '',
            $log->description ?? '',
        ];

        if ($this->auditIncludesReason()) {
            $row[] = $log->change_reason ?? '';
        }

        return array_merge($row, [
            $log->ip_address ?? '',
            $log->user_agent ?? '',
            $this->jsonValue($log->before_values),
            $this->jsonValue($log->after_values),
        ]);
    }

    private function auditIncludesReason(): bool
    {
        return ($this->filters['entity_group'] ?? 'ticket') === 'master_data';
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
