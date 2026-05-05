<?php

namespace App\Http\Controllers\Api;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminAuditLogApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 50);

        if (!in_array($perPage, [50, 75, 100], true)) {
            $perPage = 50;
        }

        $query = $this->filteredAuditLogQuery($request)
            ->with('actor')
            ->latest('id');

        $paginator = $query->paginate($perPage)->withQueryString();

        $rows = $paginator->getCollection()
            ->map(fn (AuditLog $log) => $this->mapLog($log))
            ->values();

        return $this->success([
            'rows' => $rows,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'summary' => $this->summary(),
            'options' => $this->options(),
        ], 'Audit logs loaded');
    }

    public function export(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'csv'));

        if (!in_array($format, ['csv', 'excel', 'xls'], true)) {
            $format = 'csv';
        }

        $extension = $format === 'csv' ? 'csv' : 'xls';
        $fileName = 'audit-logs-' . now()->format('Ymd-His') . '.' . $extension;

        if ($format === 'csv') {
            return $this->exportCsv($request, $fileName);
        }

        return $this->exportExcel($request, $fileName);
    }

    protected function filteredAuditLogQuery(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $action = (string) $request->query('action', 'all');
        $entity = (string) $request->query('entity', 'all');
        $dateRange = (string) $request->query('date_range', '30d');
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');

        $query = AuditLog::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('actor_name', 'like', '%' . $search . '%')
                    ->orWhere('actor_email', 'like', '%' . $search . '%')
                    ->orWhere('actor_role', 'like', '%' . $search . '%')
                    ->orWhere('action', 'like', '%' . $search . '%')
                    ->orWhere('entity_type', 'like', '%' . $search . '%')
                    ->orWhere('entity_label', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('ip_address', 'like', '%' . $search . '%')
                    ->orWhere('user_agent', 'like', '%' . $search . '%');
            });
        }

        if ($action !== 'all') {
            $query->where('action', $action);
        }

        if ($entity !== 'all') {
            $query->where('entity_type', $entity);
        }

        if ($dateRange === 'today') {
            $query->whereDate('created_at', now()->toDateString());
        } elseif ($dateRange === '7d') {
            $query->where('created_at', '>=', now()->copy()->subDays(7));
        } elseif ($dateRange === '30d') {
            $query->where('created_at', '>=', now()->copy()->subDays(30));
        } elseif ($dateRange === 'custom') {
            if ($dateFrom !== '') {
                $query->whereDate('created_at', '>=', $dateFrom);
            }

            if ($dateTo !== '') {
                $query->whereDate('created_at', '<=', $dateTo);
            }
        }

        return $query;
    }

    protected function exportCsv(Request $request, string $fileName)
    {
        return response()->streamDownload(function () use ($request) {
            echo "\xEF\xBB\xBF";

            $handle = fopen('php://output', 'w');
            fputcsv($handle, $this->exportHeaders());

            $this->filteredAuditLogQuery($request)
                ->with('actor')
                ->orderByDesc('id')
                ->chunk(500, function ($logs) use ($handle) {
                    foreach ($logs as $log) {
                        fputcsv($handle, $this->exportRow($log));
                    }
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function exportExcel(Request $request, string $fileName)
    {
        return response()->streamDownload(function () use ($request) {
            echo '<html>';
            echo '<head>';
            echo '<meta charset="UTF-8">';
            echo '</head>';
            echo '<body>';
            echo '<table border="1">';

            echo '<thead><tr>';
            foreach ($this->exportHeaders() as $header) {
                echo '<th>' . e($header) . '</th>';
            }
            echo '</tr></thead>';

            echo '<tbody>';

            $this->filteredAuditLogQuery($request)
                ->with('actor')
                ->orderByDesc('id')
                ->chunk(500, function ($logs) {
                    foreach ($logs as $log) {
                        echo '<tr>';

                        foreach ($this->exportRow($log) as $cell) {
                            echo '<td style="vertical-align: top; white-space: pre-wrap;">' . e($cell) . '</td>';
                        }

                        echo '</tr>';
                    }
                });

            echo '</tbody>';
            echo '</table>';
            echo '</body>';
            echo '</html>';
        }, $fileName, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    protected function exportHeaders(): array
    {
        return [
            'Timestamp',
            'Actor',
            'Role',
            'Action',
            'Entity',
            'Entity ID',
            'Description',
            'IP Address',
            'User Agent',
            'Before Values',
            'After Values',
        ];
    }

    protected function exportRow(AuditLog $log): array
    {
        return [
            optional($log->created_at)?->format('Y-m-d H:i:s') ?? '',
            $log->actor_name ?: $log->actor?->name ?: 'System',
            $log->actor_role ?: $log->actor?->role ?: '',
            $this->titleLabel($log->action),
            $this->titleLabel($log->entity_type),
            $log->entity_id ?? '',
            $log->description ?? '',
            $log->ip_address ?? '',
            $log->user_agent ?? '',
            $this->exportValue($log->before_values),
            $this->exportValue($log->after_values),
        ];
    }

    protected function exportValue(mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '';
        }

        $decoded = json_decode((string) $value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '';
        }

        return (string) $value;
    }

    protected function mapLog(AuditLog $log): array
    {
        return [
            'id' => $log->id,
            'actor_id' => $log->actor_id,
            'actor_name' => $log->actor_name ?: $log->actor?->name ?: 'System',
            'actor_email' => $log->actor_email ?: $log->actor?->email,
            'actor_role' => $log->actor_role ?: $log->actor?->role,
            'action' => $log->action,
            'action_label' => $this->titleLabel($log->action),
            'entity_type' => $log->entity_type,
            'entity_label_type' => $this->titleLabel($log->entity_type),
            'entity_id' => $log->entity_id,
            'entity_label' => $log->entity_label,
            'description' => $log->description,
            'before_values' => $log->before_values,
            'after_values' => $log->after_values,
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'created_at' => optional($log->created_at)?->toISOString(),
            'created_label' => optional($log->created_at)?->format('d M Y, H:i:s'),
        ];
    }

    protected function summary(): array
    {
        return [
            'total' => AuditLog::query()->count(),
            'today' => AuditLog::query()->whereDate('created_at', now()->toDateString())->count(),
            'last_7_days' => AuditLog::query()->where('created_at', '>=', now()->copy()->subDays(7))->count(),
            'critical_changes' => AuditLog::query()
                ->whereIn('action', ['deleted', 'deactivated', 'status_changed'])
                ->where('created_at', '>=', now()->copy()->subDays(7))
                ->count(),
        ];
    }

    protected function options(): array
    {
        return [
            'actions' => AuditLog::query()
                ->select('action')
                ->whereNotNull('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action')
                ->map(fn ($action) => [
                    'value' => $action,
                    'label' => $this->titleLabel($action),
                ])
                ->values(),

            'entities' => AuditLog::query()
                ->select('entity_type')
                ->whereNotNull('entity_type')
                ->distinct()
                ->orderBy('entity_type')
                ->pluck('entity_type')
                ->map(fn ($entity) => [
                    'value' => $entity,
                    'label' => $this->titleLabel($entity),
                ])
                ->values(),
        ];
    }

    protected function titleLabel(?string $value): string
    {
        if (!$value) {
            return '-';
        }

        return str($value)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
