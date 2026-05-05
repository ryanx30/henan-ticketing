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
use Illuminate\Validation\Rule;

class AdminMasterDataApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $type = (string) $request->query('type', 'categories');
        $search = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 10);

        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        [, $query] = $this->resolveQuery($type);

        if ($search !== '') {
            $this->applySearch($type, $query, $search);
        }

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
                'code' => Str::slug(trim($validated['code'])),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]),

            'priorities' => Priority::create([
                'code_num' => $this->prepareNumericCode($validated['code_num'], 1),
                'name' => trim($validated['name']),
                'code' => Str::slug(trim($validated['code'])),
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

        return $this->success(
            $after,
            $this->label($type) . ' created successfully.'
        );
    }

    public function update(Request $request, string $type, int $id)
    {
        $row = $this->findRow($type, $id);
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
                'code' => Str::slug(trim($validated['code'])),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]),

            'priorities' => $row->update([
                'code_num' => $this->prepareNumericCode($validated['code_num'], 1),
                'name' => trim($validated['name']),
                'code' => Str::slug(trim($validated['code'])),
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
        $row = $this->reloadRelations($type, $row);
        $before = $this->transformRow($type, $row);
        $entityLabel = $this->entityLabel($type, $row);

        try {
            $row->delete();
        } catch (QueryException $e) {
            return $this->error('This record is already used by another data and cannot be deleted.', 422);
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

        return $this->success([], $this->label($type) . ' deleted successfully.');
    }

    protected function resolveQuery(string $type): array
    {
        return match ($type) {
            'categories' => [
                Category::class,
                Category::query()->latest('id'),
            ],

            'issue-types' => [
                IssueType::class,
                IssueType::query()->with('category')->latest('id'),
            ],

            'teams' => [
                Team::class,
                Team::query()->latest('id'),
            ],

            'priorities' => [
                Priority::class,
                Priority::query()->orderBy('sort_order')->orderBy('id'),
            ],

            'sla-rules' => [
                SlaRule::class,
                SlaRule::query()->with(['team', 'priority'])->latest('id'),
            ],

            default => abort(404),
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