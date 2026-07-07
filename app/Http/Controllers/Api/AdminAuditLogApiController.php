<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ExportDataJob;
use App\Models\AuditLog;
use App\Support\ExportBatchAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

/**
 * Provides filtered audit log data and export endpoints for administrative monitoring.
 */
class AdminAuditLogApiController extends BaseApiController
{
    protected const ENTITY_GROUPS = [
        'ticket' => ['ticket'],
        'user' => ['user'],
        'resolver_inbox' => ['resolver_message'],
        'master_data' => ['category', 'categorie', 'issue_type', 'team', 'priority', 'prioritie', 'sla_rule'],
    ];

    protected const MASTER_DATA_ENTITY_FILTERS = [
        'categories' => ['category', 'categorie'],
        'issue_types' => ['issue_type'],
        'teams' => ['team'],
        'priorities' => ['priority', 'prioritie'],
        'sla_rules' => ['sla_rule'],
    ];

    protected const ACTION_FILTERS = [
        'created' => ['created'],
        'updated' => ['updated'],
        'edit' => ['updated'],
        'status_change' => ['status_changed', 'deactivated', 'activated', 'reactivated', 'claimed'],
        'handoff' => ['holder_transferred'],
    ];

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
            'summary' => $this->summary($request),
            'options' => $this->options($request),
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
        $storagePath = 'exports/audit-logs/' . $fileName;
        $user = $request->user();

        $batch = Bus::batch([
            new ExportDataJob('audit_logs_' . ($format === 'csv' ? 'csv' : 'excel'), $user->id, [
                'q' => (string) $request->query('q', ''),
                'action' => (string) $request->query('action', 'all'),
                'entity_group' => $this->normalizeEntityGroup((string) $request->query('entity_group', 'ticket')),
                'entity_type' => $this->normalizeMasterDataEntityFilter((string) $request->query('entity_type', 'all')),
                'date_range' => (string) $request->query('date_range', '30d'),
                'date_from' => (string) $request->query('date_from', ''),
                'date_to' => (string) $request->query('date_to', ''),
            ], $fileName),
        ])->name(ExportBatchAccess::batchName('audit-logs', $user->id, $storagePath, $fileName))->dispatch();

        return $this->acceptedResponse([
            'queued' => true,
            'batch_id' => $batch->id,
            'filename' => $fileName,
            'storage_disk' => 'local',
            'storage_path' => $storagePath,
        ], 'Audit log export has been queued.');
    }

    protected function filteredAuditLogQuery(Request $request): Builder
    {
        $query = AuditLog::query();

        $entityGroup = $this->normalizeEntityGroup((string) $request->query('entity_group', 'ticket'));

        $this->applyEntityGroup($query, $entityGroup);
        $this->applyMasterDataEntityFilter($query, $entityGroup, (string) $request->query('entity_type', 'all'));
        $this->applySearch($query, trim((string) $request->query('q', '')), $entityGroup);
        $this->applyAction($query, (string) $request->query('action', 'all'));
        $this->applyDateRange(
            $query,
            (string) $request->query('date_range', '30d'),
            (string) $request->query('date_from', ''),
            (string) $request->query('date_to', '')
        );

        return $query;
    }

    protected function applySearch(Builder $query, string $search, string $entityGroup): void
    {
        if ($search === '') {
            return;
        }

        $query->where(function ($q) use ($search, $entityGroup) {
            $q->where('actor_name', 'like', '%' . $search . '%')
                ->orWhere('actor_email', 'like', '%' . $search . '%')
                ->orWhere('actor_role', 'like', '%' . $search . '%')
                ->orWhere('action', 'like', '%' . $search . '%')
                ->orWhere('entity_type', 'like', '%' . $search . '%')
                ->orWhere('entity_label', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%')
                ->orWhere('ip_address', 'like', '%' . $search . '%')
                ->orWhere('user_agent', 'like', '%' . $search . '%');

            if ($entityGroup === 'master_data') {
                $q->orWhere('change_reason', 'like', '%' . $search . '%');
            }
        });
    }

    protected function applyAction(Builder $query, string $action): void
    {
        if ($action === 'all') {
            return;
        }

        if (array_key_exists($action, self::ACTION_FILTERS)) {
            $query->whereIn('action', self::ACTION_FILTERS[$action]);
            return;
        }

        $query->where('action', $action);
    }

    protected function applyEntityGroup(Builder $query, string $entityGroup): void
    {
        $query->whereIn('entity_type', self::ENTITY_GROUPS[$entityGroup]);
    }

    protected function applyMasterDataEntityFilter(Builder $query, string $entityGroup, string $entityType): void
    {
        if ($entityGroup !== 'master_data') {
            return;
        }

        $entityType = $this->normalizeMasterDataEntityFilter($entityType);

        if ($entityType === 'all') {
            return;
        }

        $query->whereIn('entity_type', self::MASTER_DATA_ENTITY_FILTERS[$entityType]);
    }

    protected function applyDateRange(Builder $query, string $dateRange, string $dateFrom, string $dateTo): void
    {
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
            'entity_group' => $this->entityGroupFor($log->entity_type),
            'entity_label_type' => $this->entityTypeLabel($log->entity_type),
            'entity_id' => $log->entity_id,
            'entity_label' => $log->entity_label,
            'description' => $log->description,
            'change_reason' => $log->change_reason,
            'before_values' => $log->before_values,
            'after_values' => $log->after_values,
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'created_at' => optional($log->created_at)?->toISOString(),
            'created_label' => optional($log->created_at)?->format('d M Y, H:i:s'),
        ];
    }

    protected function summary(Request $request): array
    {
        $entityGroup = $this->normalizeEntityGroup((string) $request->query('entity_group', 'ticket'));
        $search = trim((string) $request->query('q', ''));
        $action = (string) $request->query('action', 'all');
        $entityType = (string) $request->query('entity_type', 'all');

        $baseQuery = AuditLog::query();
        $this->applyEntityGroup($baseQuery, $entityGroup);
        $this->applyMasterDataEntityFilter($baseQuery, $entityGroup, $entityType);
        $this->applySearch($baseQuery, $search, $entityGroup);
        $this->applyAction($baseQuery, $action);

        return [
            'total' => (clone $baseQuery)->count(),
            'today' => (clone $baseQuery)->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])->count(),
            'last_7_days' => (clone $baseQuery)->where('created_at', '>=', now()->copy()->subDays(7))->count(),
            'critical_changes' => (clone $baseQuery)
                ->whereIn('action', ['deleted', 'deactivated', 'reactivated', 'status_changed'])
                ->where('created_at', '>=', now()->copy()->subDays(7))
                ->count(),
        ];
    }

    protected function options(Request $request): array
    {
        $entityGroup = $this->normalizeEntityGroup((string) $request->query('entity_group', 'ticket'));
        $entityType = (string) $request->query('entity_type', 'all');
        $actionQuery = AuditLog::query();
        $this->applyEntityGroup($actionQuery, $entityGroup);
        $this->applyMasterDataEntityFilter($actionQuery, $entityGroup, $entityType);

        return [
            'tabs' => $this->tabs(),
            'actions' => $this->actionsFor($entityGroup, $actionQuery),
            'master_data_entities' => $this->masterDataEntities(),
        ];
    }

    protected function actionsFor(string $entityGroup, Builder $query): array
    {
        if ($entityGroup === 'master_data') {
            return [
                ['value' => 'created', 'label' => 'Created'],
                ['value' => 'updated', 'label' => 'Edit'],
                ['value' => 'status_change', 'label' => 'Status Change'],
            ];
        }

        if ($entityGroup === 'user') {
            return [
                ['value' => 'created', 'label' => 'Created'],
                ['value' => 'updated', 'label' => 'Edit'],
                ['value' => 'status_change', 'label' => 'Status Change'],
            ];
        }

        if ($entityGroup === 'ticket') {
            return [
                ['value' => 'created', 'label' => 'Created'],
                ['value' => 'handoff', 'label' => 'Handoff'],
                ['value' => 'status_change', 'label' => 'Status Change'],
            ];
        }

        return $query
            ->select('action')
            ->whereNotNull('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->map(fn ($action) => [
                'value' => $action,
                'label' => $this->titleLabel($action),
            ])
            ->values()
            ->all();
    }

    protected function masterDataEntities(): array
    {
        return [
            ['value' => 'categories', 'label' => 'Categories'],
            ['value' => 'issue_types', 'label' => 'Issue Types'],
            ['value' => 'teams', 'label' => 'Teams'],
            ['value' => 'priorities', 'label' => 'Priorities'],
            ['value' => 'sla_rules', 'label' => 'SLA Rules'],
        ];
    }

    protected function tabs(): array
    {
        return [
            [
                'value' => 'ticket',
                'label' => 'Ticket',
                'description' => 'Ticket creation, claim, status, routing, and lifecycle activity.',
            ],
            [
                'value' => 'user',
                'label' => 'User',
                'description' => 'User account creation, update, activation, and deactivation activity.',
            ],
            [
                'value' => 'resolver_inbox',
                'label' => 'Resolver Inbox',
                'description' => 'Resolver conversation messages, reads, replies, and attachments.',
            ],
            [
                'value' => 'master_data',
                'label' => 'Master Data',
                'description' => 'Category, issue type, team, priority, and SLA rule changes.',
            ],
        ];
    }

    protected function entityGroupFor(?string $entityType): string
    {
        foreach (self::ENTITY_GROUPS as $group => $types) {
            if (in_array($entityType, $types, true)) {
                return $group;
            }
        }

        return 'ticket';
    }

    protected function normalizeEntityGroup(string $entityGroup): string
    {
        return array_key_exists($entityGroup, self::ENTITY_GROUPS) ? $entityGroup : 'ticket';
    }

    protected function normalizeMasterDataEntityFilter(string $entityType): string
    {
        return array_key_exists($entityType, self::MASTER_DATA_ENTITY_FILTERS) ? $entityType : 'all';
    }

    protected function entityTypeLabel(?string $value): string
    {
        if (!$value) {
            return '-';
        }

        if (in_array($value, self::ENTITY_GROUPS['master_data'], true)) {
            return 'Master Data / ' . $this->titleLabel($this->normalizedEntityTypeLabel($value));
        }

        return $this->titleLabel($value);
    }

    protected function normalizedEntityTypeLabel(string $value): string
    {
        return match ($value) {
            'categorie' => 'category',
            'prioritie' => 'priority',
            default => $value,
        };
    }

    protected function titleLabel(?string $value): string
    {
        if (!$value) {
            return '-';
        }

        return match ($value) {
            'holder_transferred' => 'Handoff',
            default => str($value)
                ->replace('_', ' ')
                ->title()
                ->toString(),
        };
    }
}
