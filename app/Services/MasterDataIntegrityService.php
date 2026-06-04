<?php

namespace App\Services;

use App\Models\Category;
use App\Models\IssueType;
use App\Models\Priority;
use App\Models\SlaRule;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Protects master data from unsafe deletes or changes while related ticket data still depends on it.
 */
class MasterDataIntegrityService
{
    /**
     * Default source of truth for core system-owned master data.
     * Admins may still add additional rows from Master Data UI.
     */
    public const TEAM_DEFAULTS = [
        'it' => ['code_num' => '1', 'name' => 'IT'],
        'finance' => ['code_num' => '2', 'name' => 'Finance'],
        'compliance' => ['code_num' => '3', 'name' => 'Compliance'],
    ];

    public const PRIORITY_DEFAULTS = [
        'critical' => ['code_num' => '1', 'name' => 'Critical', 'sort_order' => 1],
        'high' => ['code_num' => '2', 'name' => 'High', 'sort_order' => 2],
        'medium' => ['code_num' => '3', 'name' => 'Medium', 'sort_order' => 3],
        'low' => ['code_num' => '4', 'name' => 'Low', 'sort_order' => 4],
    ];

    public const CATEGORY_DEFAULTS = [
        ['code_num' => '01', 'name' => 'Account', 'slug' => 'account'],
        ['code_num' => '02', 'name' => 'Trading', 'slug' => 'trading'],
        ['code_num' => '03', 'name' => 'Fund', 'slug' => 'fund'],
        ['code_num' => '04', 'name' => 'Compliance', 'slug' => 'compliance'],
        ['code_num' => '05', 'name' => 'System', 'slug' => 'system'],
        ['code_num' => '06', 'name' => 'General', 'slug' => 'general'],
    ];

    public const ISSUE_TYPE_DEFAULTS = [
        'account' => [
            ['code_num' => '001', 'name' => 'Login Problem', 'slug' => 'login-problem'],
            ['code_num' => '002', 'name' => 'Account Verification', 'slug' => 'account-verification'],
        ],
        'trading' => [
            ['code_num' => '001', 'name' => 'Order Problem', 'slug' => 'order-problem'],
            ['code_num' => '002', 'name' => 'Portfolio Display', 'slug' => 'portfolio-display'],
        ],
        'fund' => [
            ['code_num' => '001', 'name' => 'Deposit', 'slug' => 'deposit'],
            ['code_num' => '002', 'name' => 'Withdrawal', 'slug' => 'withdrawal'],
        ],
        'compliance' => [
            ['code_num' => '001', 'name' => 'Document Review', 'slug' => 'document-review'],
            ['code_num' => '002', 'name' => 'KYC Update', 'slug' => 'kyc-update'],
        ],
        'system' => [
            ['code_num' => '001', 'name' => 'Application Error', 'slug' => 'application-error'],
            ['code_num' => '002', 'name' => 'Performance Issue', 'slug' => 'performance-issue'],
        ],
        'general' => [
            ['code_num' => '001', 'name' => 'General Issue', 'slug' => 'general-issue'],
        ],
    ];

    public function normalize(bool $dryRun = false): array
    {
        return DB::transaction(function () use ($dryRun) {
            $summary = [
                'dry_run' => $dryRun,
                'teams' => 0,
                'priorities' => 0,
                'categories' => 0,
                'issue_types' => 0,
                'sla_rules' => 0,
                'warnings' => [],
            ];

            $this->normalizeExistingTeams($summary, $dryRun);
            $this->normalizeExistingPriorities($summary, $dryRun);
            $this->normalizeExistingCategories($summary, $dryRun);
            $this->normalizeExistingIssueTypes($summary, $dryRun);

            foreach (self::TEAM_DEFAULTS as $code => $data) {
                $summary['teams'] += $this->upsertTeam($code, $data, $dryRun);
            }

            foreach (self::PRIORITY_DEFAULTS as $code => $data) {
                $summary['priorities'] += $this->upsertPriority($code, $data, $dryRun);
            }

            foreach (self::CATEGORY_DEFAULTS as $data) {
                $summary['categories'] += $this->upsertCategory($data, $dryRun);
            }

            foreach (self::ISSUE_TYPE_DEFAULTS as $categorySlug => $rows) {
                $category = Category::query()->where('slug', $categorySlug)->first();

                if (!$category) {
                    $summary['warnings'][] = "Category {$categorySlug} not found; skipped its issue types.";
                    continue;
                }

                foreach ($rows as $row) {
                    $summary['issue_types'] += $this->upsertIssueType($category, $row, $dryRun);
                }
            }

            $itTeam = Team::query()->where('code', 'it')->first();
            if ($itTeam) {
                foreach (self::PRIORITY_DEFAULTS as $priorityCode => $priorityData) {
                    $priority = Priority::query()->where('code', $priorityCode)->first();
                    if (!$priority) {
                        continue;
                    }

                    $hours = match ($priorityCode) {
                        'critical' => 6,
                        'high' => 12,
                        'medium' => 18,
                        'low' => 24,
                        default => 24,
                    };

                    $summary['sla_rules'] += $this->upsertSlaRule($itTeam, $priority, $hours, $dryRun);
                }
            }

            if (!$dryRun) {
                $this->validateOrFail();
            }

            return $summary;
        });
    }

    public function validateOrFail(): void
    {
        $errors = $this->validate();

        if ($errors !== []) {
            throw new RuntimeException("Master Data integrity check failed:\n- " . implode("\n- ", $errors));
        }
    }

    public function validate(): array
    {
        $errors = [];

        $this->validateTableCodes(
            $errors,
            'teams',
            Team::query()->get(['id', 'code_num', 'name', 'code']),
            1,
            true
        );

        $this->validateTableCodes(
            $errors,
            'priorities',
            Priority::query()->get(['id', 'code_num', 'name', 'code']),
            1,
            true
        );

        $this->validateTableCodes(
            $errors,
            'categories',
            Category::query()->get(['id', 'code_num', 'name', 'slug']),
            2,
            false
        );

        $issueTypes = IssueType::query()->get(['id', 'category_id', 'code_num', 'name', 'slug']);
        foreach ($issueTypes as $issueType) {
            if (!preg_match('/^\d{3}$/', (string) $issueType->code_num)) {
                $errors[] = "issue_types.id={$issueType->id} code_num must be exactly 3 digits.";
            }
        }

        $duplicates = $issueTypes
            ->groupBy(fn (IssueType $row) => $row->category_id . ':' . $row->code_num)
            ->filter(fn ($rows) => $rows->count() > 1);

        foreach ($duplicates as $key => $rows) {
            $errors[] = "issue_types duplicate category/code_num {$key} on ids " . $rows->pluck('id')->implode(', ') . '.';
        }

        foreach (self::TEAM_DEFAULTS as $code => $data) {
            $row = Team::query()->where('code', $code)->first();
            if (!$row || (string) $row->code_num !== $data['code_num']) {
                $errors[] = "core team {$code} must use code_num {$data['code_num']}.";
            }
        }

        foreach (self::PRIORITY_DEFAULTS as $code => $data) {
            $row = Priority::query()->where('code', $code)->first();
            if (!$row || (string) $row->code_num !== $data['code_num']) {
                $errors[] = "core priority {$code} must use code_num {$data['code_num']}.";
            }
        }

        return $errors;
    }

    private function validateTableCodes(array &$errors, string $table, $rows, int $length, bool $hasSystemCode): void
    {
        foreach ($rows as $row) {
            if (!preg_match('/^\d{' . $length . '}$/', (string) $row->code_num)) {
                $errors[] = "{$table}.id={$row->id} code_num must be exactly {$length} digit(s).";
            }

            if ($hasSystemCode) {
                $code = (string) $row->code;
                if (!preg_match('/^[a-z][a-z0-9_]*$/', $code)) {
                    $errors[] = "{$table}.id={$row->id} code must be a lowercase system key, not a label or number.";
                }

                if (preg_match('/^\d+$/', $code)) {
                    $errors[] = "{$table}.id={$row->id} code must not be numeric; use code_num for numeric ticket code digits.";
                }
            }
        }

        $duplicates = $rows
            ->groupBy('code_num')
            ->filter(fn ($items, $codeNum) => $codeNum !== '' && $items->count() > 1);

        foreach ($duplicates as $codeNum => $items) {
            $errors[] = "{$table} duplicate code_num {$codeNum} on ids " . $items->pluck('id')->implode(', ') . '.';
        }
    }

    private function normalizeExistingTeams(array &$summary, bool $dryRun): void
    {
        $fallback = 4;
        foreach (Team::query()->orderBy('id')->get() as $team) {
            $rawCode = (string) ($team->code ?? '');
            $codeSource = preg_match('/^\d+$/', $rawCode) ? ($team->name ?: 'team_' . $team->id) : ($team->code ?: $team->name ?: 'team_' . $team->id);
            $code = $this->systemCode($codeSource);
            $codeNum = self::TEAM_DEFAULTS[$code]['code_num'] ?? $team->code_num;

            if (!$codeNum || !preg_match('/^\d$/', (string) $codeNum)) {
                $codeNum = (string) $fallback++;
            }

            $changes = [
                'code' => $code,
                'code_num' => $this->uniqueCodeNum(Team::class, (string) $codeNum, 1, $team->id),
                'name' => trim((string) ($team->name ?: Str::title(str_replace('_', ' ', $code)))),
                'is_active' => (bool) $team->is_active,
            ];

            if ($this->hasChanges($team, $changes)) {
                $summary['teams']++;
                if (!$dryRun) {
                    $team->update($changes);
                }
            }
        }
    }

    private function normalizeExistingPriorities(array &$summary, bool $dryRun): void
    {
        $fallback = 5;
        foreach (Priority::query()->orderBy('id')->get() as $priority) {
            $rawCode = (string) ($priority->code ?? '');
            $codeSource = preg_match('/^\d+$/', $rawCode) ? ($priority->name ?: 'priority_' . $priority->id) : ($priority->code ?: $priority->name ?: 'priority_' . $priority->id);
            $code = $this->systemCode($codeSource);
            $codeNum = self::PRIORITY_DEFAULTS[$code]['code_num'] ?? $priority->code_num;

            if (!$codeNum || !preg_match('/^\d$/', (string) $codeNum)) {
                $codeNum = (string) $fallback++;
            }

            $changes = [
                'code' => $code,
                'code_num' => $this->uniqueCodeNum(Priority::class, (string) $codeNum, 1, $priority->id),
                'name' => trim((string) ($priority->name ?: Str::title(str_replace('_', ' ', $code)))),
                'sort_order' => (int) ($priority->sort_order ?: (self::PRIORITY_DEFAULTS[$code]['sort_order'] ?? 99)),
                'is_active' => (bool) $priority->is_active,
            ];

            if ($this->hasChanges($priority, $changes)) {
                $summary['priorities']++;
                if (!$dryRun) {
                    $priority->update($changes);
                }
            }
        }
    }

    private function normalizeExistingCategories(array &$summary, bool $dryRun): void
    {
        $counter = 1;
        foreach (Category::query()->orderBy('id')->get() as $category) {
            $slug = Str::slug($category->slug ?: $category->name ?: 'category-' . $category->id);
            $codeNum = $category->code_num;

            if (!$codeNum || !preg_match('/^\d{2}$/', (string) $codeNum)) {
                $codeNum = str_pad((string) $counter, 2, '0', STR_PAD_LEFT);
            }

            $changes = [
                'slug' => $slug,
                'code_num' => $this->uniqueCodeNum(Category::class, (string) $codeNum, 2, $category->id),
                'name' => trim((string) ($category->name ?: Str::title(str_replace('-', ' ', $slug)))),
                'is_active' => (bool) $category->is_active,
            ];

            if ($this->hasChanges($category, $changes)) {
                $summary['categories']++;
                if (!$dryRun) {
                    $category->update($changes);
                }
            }

            $counter++;
        }
    }

    private function normalizeExistingIssueTypes(array &$summary, bool $dryRun): void
    {
        $issueTypesByCategory = IssueType::query()->orderBy('category_id')->orderBy('id')->get()->groupBy('category_id');

        foreach ($issueTypesByCategory as $categoryId => $rows) {
            $counter = 1;
            foreach ($rows as $issueType) {
                $slug = Str::slug($issueType->slug ?: $issueType->name ?: 'issue-type-' . $issueType->id);
                $codeNum = $issueType->code_num;

                if (!$codeNum || !preg_match('/^\d{3}$/', (string) $codeNum)) {
                    $codeNum = str_pad((string) $counter, 3, '0', STR_PAD_LEFT);
                }

                $changes = [
                    'slug' => $slug,
                    'code_num' => $this->uniqueIssueTypeCodeNum((int) $categoryId, (string) $codeNum, $issueType->id),
                    'name' => trim((string) ($issueType->name ?: Str::title(str_replace('-', ' ', $slug)))),
                    'is_active' => (bool) $issueType->is_active,
                ];

                if ($this->hasChanges($issueType, $changes)) {
                    $summary['issue_types']++;
                    if (!$dryRun) {
                        $issueType->update($changes);
                    }
                }

                $counter++;
            }
        }
    }

    private function upsertTeam(string $code, array $data, bool $dryRun): int
    {
        $changed = 0;
        $conflict = Team::query()
            ->where('code_num', $data['code_num'])
            ->where('code', '!=', $code)
            ->first();

        if ($conflict) {
            $changed++;
            if (!$dryRun) {
                $conflict->update([
                    'code_num' => $this->uniqueCodeNum(Team::class, '4', 1, $conflict->id),
                ]);
            }
        }

        $row = Team::query()->firstOrNew(['code' => $code]);
        $row->fill([
            'code_num' => $data['code_num'],
            'name' => $data['name'],
            'is_active' => true,
        ]);

        if (!$row->exists || $row->isDirty()) {
            if (!$dryRun) {
                $row->save();
            }
            return $changed + 1;
        }

        return $changed;
    }

    private function upsertPriority(string $code, array $data, bool $dryRun): int
    {
        $changed = 0;
        $conflict = Priority::query()
            ->where('code_num', $data['code_num'])
            ->where('code', '!=', $code)
            ->first();

        if ($conflict) {
            $changed++;
            if (!$dryRun) {
                $conflict->update([
                    'code_num' => $this->uniqueCodeNum(Priority::class, '5', 1, $conflict->id),
                ]);
            }
        }

        $row = Priority::query()->firstOrNew(['code' => $code]);
        $row->fill([
            'code_num' => $data['code_num'],
            'name' => $data['name'],
            'sort_order' => $data['sort_order'],
            'is_active' => true,
        ]);

        if (!$row->exists || $row->isDirty()) {
            if (!$dryRun) {
                $row->save();
            }
            return $changed + 1;
        }

        return $changed;
    }

    private function upsertCategory(array $data, bool $dryRun): int
    {
        $changed = 0;
        $conflict = Category::query()
            ->where('code_num', $data['code_num'])
            ->where('slug', '!=', $data['slug'])
            ->first();

        if ($conflict) {
            $changed++;
            if (!$dryRun) {
                $conflict->update([
                    'code_num' => $this->uniqueCodeNum(Category::class, '07', 2, $conflict->id),
                ]);
            }
        }

        $row = Category::query()->firstOrNew(['slug' => $data['slug']]);
        $row->fill([
            'code_num' => $data['code_num'],
            'name' => $data['name'],
            'is_active' => true,
        ]);

        if (!$row->exists || $row->isDirty()) {
            if (!$dryRun) {
                $row->save();
            }
            return $changed + 1;
        }

        return $changed;
    }

    private function upsertIssueType(Category $category, array $data, bool $dryRun): int
    {
        $changed = 0;
        $conflict = IssueType::query()
            ->where('category_id', $category->id)
            ->where('code_num', $data['code_num'])
            ->where('slug', '!=', $data['slug'])
            ->first();

        if ($conflict) {
            $changed++;
            if (!$dryRun) {
                $conflict->update([
                    'code_num' => $this->uniqueIssueTypeCodeNum($category->id, '003', $conflict->id),
                ]);
            }
        }

        $row = IssueType::query()->firstOrNew([
            'category_id' => $category->id,
            'slug' => $data['slug'],
        ]);
        $row->fill([
            'code_num' => $data['code_num'],
            'name' => $data['name'],
            'is_active' => true,
        ]);

        if (!$row->exists || $row->isDirty()) {
            if (!$dryRun) {
                $row->save();
            }
            return $changed + 1;
        }

        return $changed;
    }

    private function upsertSlaRule(Team $team, Priority $priority, int $hours, bool $dryRun): int
    {
        $row = SlaRule::query()->firstOrNew([
            'team_id' => $team->id,
            'priority_id' => $priority->id,
        ]);
        $row->fill([
            'hours' => $hours,
            'is_active' => true,
        ]);

        if (!$row->exists || $row->isDirty()) {
            if (!$dryRun) {
                $row->save();
            }
            return 1;
        }

        return 0;
    }

    private function uniqueCodeNum(string $modelClass, string $preferred, int $length, ?int $ignoreId = null): string
    {
        $candidate = str_pad($preferred, $length, '0', STR_PAD_LEFT);
        $number = max(1, (int) $candidate);

        while ($modelClass::query()
            ->where('code_num', $candidate)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $number++;
            $candidate = str_pad((string) $number, $length, '0', STR_PAD_LEFT);

            if (strlen($candidate) > $length) {
                throw new RuntimeException("No available {$length}-digit code_num left for {$modelClass}.");
            }
        }

        return $candidate;
    }

    private function uniqueIssueTypeCodeNum(int $categoryId, string $preferred, ?int $ignoreId = null): string
    {
        $candidate = str_pad($preferred, 3, '0', STR_PAD_LEFT);
        $number = max(1, (int) $candidate);

        while (IssueType::query()
            ->where('category_id', $categoryId)
            ->where('code_num', $candidate)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $number++;
            $candidate = str_pad((string) $number, 3, '0', STR_PAD_LEFT);

            if (strlen($candidate) > 3) {
                throw new RuntimeException('No available 3-digit code_num left for issue types in this category.');
            }
        }

        return $candidate;
    }

    private function systemCode(string $value): string
    {
        $code = Str::of($value)
            ->lower()
            ->replace(['-', ' '], '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->toString();

        if ($code === '' || preg_match('/^\d+$/', $code)) {
            return 'code_' . ($code ?: Str::random(6));
        }

        if (preg_match('/^[0-9]/', $code)) {
            return 'code_' . $code;
        }

        return $code;
    }

    private function hasChanges($model, array $changes): bool
    {
        foreach ($changes as $key => $value) {
            if ((string) $model->{$key} !== (string) $value) {
                return true;
            }
        }

        return false;
    }
}
