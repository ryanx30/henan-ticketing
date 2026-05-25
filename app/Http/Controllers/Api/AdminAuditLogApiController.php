<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ExportDataJob;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

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
        $fileName = 'audit-logs-' . now()->format('Ymd-His') . '-' . Str::lower(Str::random(6)) . '.' . $extension;

        $batch = Bus::batch([
            new ExportDataJob('audit_logs_' . ($format === 'csv' ? 'csv' : 'excel'), $request->user()->id, [
                'q' => (string) $request->query('q', ''),
                'action' => (string) $request->query('action', 'all'),
                'entity' => (string) $request->query('entity', 'all'),
                'date_range' => (string) $request->query('date_range', '30d'),
                'date_from' => (string) $request->query('date_from', ''),
                'date_to' => (string) $request->query('date_to', ''),
            ], $fileName),
        ])->name('audit-log-export-' . $fileName)->dispatch();

        return $this->success([
            'queued' => true,
            'batch_id' => $batch->id,
            'filename' => $fileName,
            'storage_disk' => 'local',
            'storage_path' => 'exports/audit-logs/' . $fileName,
        ], 'Audit log export has been queued.', 202);
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
            $query->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()]);
        } elseif ($dateRange === '7d') {
            $query->where('created_at', '>=', now()->copy()->subDays(7));
        } elseif ($dateRange === '30d') {
            $query->where('created_at', '>=', now()->copy()->subDays(30));
        } elseif ($dateRange === 'custom') {
            if ($dateFrom !== '') {
                $query->where('created_at', '>=', $dateFrom . ' 00:00:00');
            }

            if ($dateTo !== '') {
                $query->where('created_at', '<=', $dateTo . ' 23:59:59');
            }
        }

        return $query;
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
            'today' => AuditLog::query()->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])->count(),
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
