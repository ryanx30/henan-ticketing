<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\IssueType;
use App\Models\Priority;
use App\Models\SlaRule;
use App\Models\Team;
use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Manages master data endpoints for teams, categories, issue types, priorities, and SLA rules.
 */
class AdminMasterDataApiController extends BaseApiController
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Category::class);

        $type = (string) $request->query('type', 'categories');
        $search = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 10);
        $sortBy = (string) $request->query('sort_by', '');
        $sortDir = strtolower((string) $request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        [, $query] = $this->resolveQuery($type);

        if ($search !== '') {
            $this->applySearch($type, $query, $search);
        }

        $this->applySort($type, $query, $sortBy, $sortDir);

        $paginator = $query->paginate($perPage)->withQueryString();

        $rows = $paginator->getCollection()
            ->map(fn ($row) => $this->transformRow($type, $row))
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
            'options' => $this->options(),
        ], 'Master data loaded');
    }

    public function store(Request $request, string $type)
    {
        Gate::authorize('create', $this->modelClassForType($type));

        $validated = $this->validatePayload($request, $type);

        $row = match ($type) {
            'categories' => Category::create([
                'code_num' => $this->prepareNumericCode($validated['code_num'], 2),
                'name' => trim($validated['name']),
                'slug' => $this->prepareSlug($validated['slug'] ?? $validated['name']),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]),

            'issue-types' => IssueType::create([
                'category_id' => $validated['category_id'],
                'code_num' => $this->prepareNumericCode($validated['code_num'], 3),
                'name' => trim($validated['name']),
                'slug' => $this->prepareSlug($validated['slug'] ?? $validated['name']),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]),

            'teams' => Team::create([
                'code_num' => $this->prepareNumericCode($validated['code_num'], 1),
                'name' => trim($validated['name']),
                'code' => $this->prepareSystemCode($validated['code']),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]),

            'priorities' => Priority::create([
                'code_num' => $this->prepareNumericCode($validated['code_num'], 1),
                'name' => trim($validated['name']),
                'code' => $this->prepareSystemCode($validated['code']),
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]),

            'sla-rules' => SlaRule::create([
                'team_id' => $validated['team_id'],
                'priority_id' => $validated['priority_id'],
                'hours' => (int) $validated['hours'],
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]),

            default => abort(404),
        };

        $row = $this->reloadRelations($type, $row);
        $after = $this->transformRow($type, $row);

        AuditLogger::record(
            $request,
            'created',
            $this->entityType($type),
            $row->id,
            $this->entityLabel($type, $row),
            'Created ' . strtolower($this->label($type)) . ': ' . $this->entityLabel($type, $row),
            null,
            $after
        );

        return $this->createdResponse(
            $after,
            $this->label($type) . ' created successfully.'
        );
    }

    public function update(Request $request, string $type, int $id)
    {
        $row = $this->findRow($type, $id);
        Gate::authorize('update', $row);
        $beforeRow = $this->reloadRelations($type, $row);
        $before = $this->transformRow($type, $beforeRow);
        $validated = $this->validatePayload($request, $type, $id);

        match ($type) {
            'categories' => $row->update([
                'code_num' => $this->prepareNumericCode($validated['code_num'], 2),
                'name' => trim($validated['name']),
                'slug' => $this->prepareSlug($validated['slug'] ?? $validated['name']),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]),

            'issue-types' => $row->update([
                'category_id' => $validated['category_id'],
                'code_num' => $this->prepareNumericCode($validated['code_num'], 3),
                'name' => trim($validated['name']),
                'slug' => $this->prepareSlug($validated['slug'] ?? $validated['name']),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]),

            'teams' => $row->update([
                'code_num' => $this->prepareNumericCode($validated['code_num'], 1),
                'name' => trim($validated['name']),
                'code' => $this->prepareSystemCode($validated['code']),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]),

            'priorities' => $row->update([
                'code_num' => $this->prepareNumericCode($validated['code_num'], 1),
                'name' => trim($validated['name']),
                'code' => $this->prepareSystemCode($validated['code']),
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]),

            'sla-rules' => $row->update([
                'team_id' => $validated['team_id'],
                'priority_id' => $validated['priority_id'],
                'hours' => (int) $validated['hours'],
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]),

            default => abort(404),
        };

        $row = $this->reloadRelations($type, $row);
        $after = $this->transformRow($type, $row);

        AuditLogger::record(
            $request,
            'updated',
            $this->entityType($type),
            $row->id,
            $this->entityLabel($type, $row),
            'Updated ' . strtolower($this->label($type)) . ': ' . $this->entityLabel($type, $row),
            $before,
            $after
        );

        return $this->success(
            $after,
            $this->label($type) . ' updated successfully.'
        );
    }

    public function destroy(Request $request, string $type, int $id)
    {
        $row = $this->findRow($type, $id);
        Gate::authorize('delete', $row);
        $row = $this->reloadRelations($type, $row);
        $before = $this->transformRow($type, $row);
        $entityLabel = $this->entityLabel($type, $row);

        try {
            $row->delete();
        } catch (QueryException $e) {
            return $this->validationError([], 'This record is already used by another data and cannot be deleted.');
        }

        AuditLogger::record(
            $request,
            'deleted',
            $this->entityType($type),
            $id,
            $entityLabel,
            'Deleted ' . strtolower($this->label($type)) . ': ' . $entityLabel,
            $before,
            null
        );

        return $this->deletedResponse($this->label($type) . ' deleted successfully.');
    }


    /**
     * Resolve the model class for policy checks before creating rows.
     */
    protected function modelClassForType(string $type): string
    {
        return match ($type) {
            'categories' => Category::class,
            'issue-types' => IssueType::class,
            'teams' => Team::class,
            'priorities' => Priority::class,
            'sla-rules' => SlaRule::class,
            default => abort(404),
        };
    }
    protected function resolveQuery(string $type): array
    {
        return match ($type) {
            'categories' => [
                Category::class,
                Category::query(),
            ],

            'issue-types' => [
                IssueType::class,
                IssueType::query()->with('category'),
            ],

            'teams' => [
                Team::class,
                Team::query(),
            ],

            'priorities' => [
                Priority::class,
                Priority::query(),
            ],

            'sla-rules' => [
                SlaRule::class,
                SlaRule::query()->with(['team', 'priority']),
            ],

            default => abort(404),
        };
    }

    protected function applySort(string $type, Builder $query, string $sortBy, string $sortDir): void
    {
        $allowed = [
            'categories' => ['code_num', 'name', 'slug', 'is_active', 'created_at'],
            'issue-types' => ['category', 'code_num', 'name', 'slug', 'is_active'],
            'teams' => ['code_num', 'name', 'code', 'is_active', 'created_at'],
            'priorities' => ['code_num', 'name', 'code', 'sort_order', 'is_active'],
            'sla-rules' => ['team', 'priority', 'hours', 'is_active'],
        ];

        if (!in_array($sortBy, $allowed[$type] ?? [], true)) {
            $this->applyDefaultSort($type, $query);

            return;
        }

        match ($type) {
            'issue-types' => match ($sortBy) {
                'category' => $query
                    ->orderBy(Category::query()
                        ->select('name')
                        ->whereColumn('categories.id', 'issue_types.category_id'), $sortDir)
                    ->orderBy('issue_types.id'),
                default => $query->orderBy('issue_types.' . $sortBy, $sortDir)->orderBy('issue_types.id'),
            },

            'sla-rules' => match ($sortBy) {
                'team' => $query
                    ->orderBy(Team::query()
                        ->select('name')
                        ->whereColumn('teams.id', 'sla_rules.team_id'), $sortDir)
                    ->orderBy('sla_rules.id'),
                'priority' => $query
                    ->orderBy(Priority::query()
                        ->select('name')
                        ->whereColumn('priorities.id', 'sla_rules.priority_id'), $sortDir)
                    ->orderBy('sla_rules.id'),
                default => $query->orderBy('sla_rules.' . $sortBy, $sortDir)->orderBy('sla_rules.id'),
            },

            'categories' => $query->orderBy('categories.' . $sortBy, $sortDir)->orderBy('categories.id'),
            'teams' => $query->orderBy('teams.' . $sortBy, $sortDir)->orderBy('teams.id'),
            'priorities' => $query->orderBy('priorities.' . $sortBy, $sortDir)->orderBy('priorities.id'),
            default => $this->applyDefaultSort($type, $query),
        };
    }

    protected function applyDefaultSort(string $type, Builder $query): void
    {
        match ($type) {
            'categories' => $query->latest('id'),
            'issue-types' => $query->latest('id'),
            'teams' => $query->latest('id'),
            'priorities' => $query->orderBy('sort_order')->orderBy('id'),
            'sla-rules' => $query->latest('id'),
            default => null,
        };
    }

    protected function applySearch(string $type, Builder $query, string $search): void
    {
        match ($type) {
            'categories' => $query->where(function ($q) use ($search) {
                $q->where('code_num', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            }),

            'issue-types' => $query->where(function ($q) use ($search) {
                $q->where('code_num', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%')
                    ->orWhereHas('category', fn ($qq) => $qq->where('name', 'like', '%' . $search . '%'));
            }),

            'teams' => $query->where(function ($q) use ($search) {
                $q->where('code_num', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            }),

            'priorities' => $query->where(function ($q) use ($search) {
                $q->where('code_num', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            }),

            'sla-rules' => $query->where(function ($q) use ($search) {
                $q->where('hours', 'like', '%' . $search . '%')
                    ->orWhereHas('team', fn ($qq) => $qq->where('name', 'like', '%' . $search . '%'))
                    ->orWhereHas('priority', fn ($qq) => $qq->where('name', 'like', '%' . $search . '%'));
            }),

            default => null,
        };
    }

    protected function validatePayload(Request $request, string $type, ?int $id = null): array
    {
        return match ($type) {
            'categories' => $request->validate([
                'code_num' => [
                    'required',
                    'digits:2',
                    Rule::unique('categories', 'code_num')->ignore($id),
                ],
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('categories', 'name')->ignore($id),
                ],
                'slug' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique('categories', 'slug')->ignore($id),
                ],
                'is_active' => ['nullable', 'boolean'],
            ]),

            'issue-types' => $request->validate([
                'category_id' => ['required', 'exists:categories,id'],
                'code_num' => [
                    'required',
                    'digits:3',
                    Rule::unique('issue_types', 'code_num')
                        ->where(fn ($q) => $q->where('category_id', $request->input('category_id')))
                        ->ignore($id),
                ],
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('issue_types', 'name')
                        ->where(fn ($q) => $q->where('category_id', $request->input('category_id')))
                        ->ignore($id),
                ],
                'slug' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique('issue_types', 'slug')->ignore($id),
                ],
                'is_active' => ['nullable', 'boolean'],
            ]),

            'teams' => $request->validate([
                'code_num' => [
                    'required',
                    'digits:1',
                    Rule::unique('teams', 'code_num')->ignore($id),
                ],
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('teams', 'name')->ignore($id),
                ],
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    'regex:/^[A-Za-z][A-Za-z0-9_ -]*$/',
                    'not_regex:/^\d+$/',
                    Rule::unique('teams', 'code')->ignore($id),
                ],
                'is_active' => ['nullable', 'boolean'],
            ]),

            'priorities' => $request->validate([
                'code_num' => [
                    'required',
                    'digits:1',
                    Rule::unique('priorities', 'code_num')->ignore($id),
                ],
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('priorities', 'name')->ignore($id),
                ],
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    'regex:/^[A-Za-z][A-Za-z0-9_ -]*$/',
                    'not_regex:/^\d+$/',
                    Rule::unique('priorities', 'code')->ignore($id),
                ],
                'sort_order' => ['nullable', 'integer', 'min:0'],
                'is_active' => ['nullable', 'boolean'],
            ]),

            'sla-rules' => $request->validate([
                'team_id' => ['required', 'exists:teams,id'],
                'priority_id' => ['required', 'exists:priorities,id'],
                'hours' => ['required', 'integer', 'min:1'],
                'is_active' => ['nullable', 'boolean'],
                Rule::unique('sla_rules')
                    ->where(function ($q) use ($request) {
                        return $q->where('team_id', $request->input('team_id'))
                            ->where('priority_id', $request->input('priority_id'));
                    })
                    ->ignore($id),
            ]),

            default => abort(404),
        };
    }

    protected function findRow(string $type, int $id)
    {
        $model = match ($type) {
            'categories' => Category::class,
            'issue-types' => IssueType::class,
            'teams' => Team::class,
            'priorities' => Priority::class,
            'sla-rules' => SlaRule::class,
            default => abort(404),
        };

        return $model::query()->findOrFail($id);
    }

    protected function reloadRelations(string $type, Model $row): Model
    {
        return match ($type) {
            'issue-types' => $row->load('category'),
            'sla-rules' => $row->load(['team', 'priority']),
            default => $row,
        };
    }

    protected function transformRow(string $type, Model $row): array
    {
        return match ($type) {
            'categories' => [
                'id' => $row->id,
                'code_num' => $row->code_num,
                'name' => $row->name,
                'slug' => $row->slug,
                'is_active' => (bool) $row->is_active,
                'created_at' => optional($row->created_at)?->toISOString(),
            ],

            'issue-types' => [
                'id' => $row->id,
                'category_id' => $row->category_id,
                'category_name' => $row->category?->name,
                'code_num' => $row->code_num,
                'name' => $row->name,
                'slug' => $row->slug,
                'is_active' => (bool) $row->is_active,
                'created_at' => optional($row->created_at)?->toISOString(),
            ],

            'teams' => [
                'id' => $row->id,
                'code_num' => $row->code_num,
                'name' => $row->name,
                'code' => $row->code,
                'is_active' => (bool) $row->is_active,
                'created_at' => optional($row->created_at)?->toISOString(),
            ],

            'priorities' => [
                'id' => $row->id,
                'code_num' => $row->code_num,
                'name' => $row->name,
                'code' => $row->code,
                'sort_order' => $row->sort_order,
                'is_active' => (bool) $row->is_active,
                'created_at' => optional($row->created_at)?->toISOString(),
            ],

            'sla-rules' => [
                'id' => $row->id,
                'team_id' => $row->team_id,
                'team_name' => $row->team?->name,
                'team_code_num' => $row->team?->code_num,
                'priority_id' => $row->priority_id,
                'priority_name' => $row->priority?->name,
                'priority_code_num' => $row->priority?->code_num,
                'hours' => $row->hours,
                'is_active' => (bool) $row->is_active,
                'created_at' => optional($row->created_at)?->toISOString(),
            ],

            default => [],
        };
    }

    protected function options(): array
    {
        return [
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('code_num')
                ->get(['id', 'code_num', 'name'])
                ->map(fn (Category $row) => [
                    'id' => $row->id,
                    'code_num' => $row->code_num,
                    'name' => $row->name,
                ])
                ->values(),

            'teams' => Team::query()
                ->where('is_active', true)
                ->orderBy('code_num')
                ->get(['id', 'code_num', 'name'])
                ->map(fn (Team $row) => [
                    'id' => $row->id,
                    'code_num' => $row->code_num,
                    'name' => $row->name,
                ])
                ->values(),

            'priorities' => Priority::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('code_num')
                ->get(['id', 'code_num', 'name'])
                ->map(fn (Priority $row) => [
                    'id' => $row->id,
                    'code_num' => $row->code_num,
                    'name' => $row->name,
                ])
                ->values(),
        ];
    }

    protected function entityType(string $type): string
    {
        return match ($type) {
            'issue-types' => 'issue_type',
            'sla-rules' => 'sla_rule',
            default => rtrim(str_replace('-', '_', $type), 's'),
        };
    }

    protected function entityLabel(string $type, Model $row): string
    {
        return match ($type) {
            'sla-rules' => ($row->team?->name ?? 'Team') . ' / ' . ($row->priority?->name ?? 'Priority'),
            default => (string) ($row->name ?? $row->code ?? $row->slug ?? $row->id),
        };
    }

    protected function prepareSystemCode(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replace(['-', ' '], '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->toString();
    }

    protected function prepareNumericCode(string $value, int $length): string
    {
        return str_pad(preg_replace('/\D/', '', trim($value)), $length, '0', STR_PAD_LEFT);
    }

    protected function prepareSlug(string $value): string
    {
        return Str::slug(trim($value));
    }

    protected function label(string $type): string
    {
        return match ($type) {
            'categories' => 'Category',
            'issue-types' => 'Issue type',
            'teams' => 'Team',
            'priorities' => 'Priority',
            'sla-rules' => 'SLA rule',
            default => 'Data',
        };
    }
}