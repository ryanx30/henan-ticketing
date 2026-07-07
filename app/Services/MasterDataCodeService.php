<?php

namespace App\Services;

use App\Models\Category;
use App\Models\IssueType;
use App\Models\Priority;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

/**
 * Generates protected numeric codes for master data used by ticket code prefixes.
 */
class MasterDataCodeService
{
    public function generate(string $type, array $payload = []): string
    {
        return match ($type) {
            'categories' => $this->nextAvailableCode(Category::query(), 2, 'Category'),
            'issue-types' => $this->nextIssueTypeCode($payload),
            'teams' => $this->nextAvailableCode(Team::query(), 1, 'Team'),
            'priorities' => $this->nextAvailableCode(Priority::query(), 1, 'Priority'),
            default => throw new RuntimeException('Auto-generated code is not available for this master data type.'),
        };
    }

    protected function nextIssueTypeCode(array $payload): string
    {
        $categoryId = (int) ($payload['category_id'] ?? 0);

        if ($categoryId <= 0) {
            throw new RuntimeException('Category is required before generating an issue type code.');
        }

        return $this->nextAvailableCode(
            IssueType::query()->where('category_id', $categoryId),
            3,
            'Issue Type'
        );
    }

    protected function nextAvailableCode(Builder $query, int $length, string $label): string
    {
        $usedCodes = $query
            ->lockForUpdate()
            ->pluck('code_num')
            ->map(fn ($value) => preg_replace('/\D/', '', (string) $value))
            ->filter(fn ($value) => $value !== '')
            ->map(fn ($value) => str_pad($value, $length, '0', STR_PAD_LEFT))
            ->unique()
            ->values()
            ->all();

        $maximum = (10 ** $length) - 1;

        for ($number = 1; $number <= $maximum; $number++) {
            $candidate = str_pad((string) $number, $length, '0', STR_PAD_LEFT);

            if (!in_array($candidate, $usedCodes, true)) {
                return $candidate;
            }
        }

        throw new RuntimeException("{$label} code range is full. Please review existing master data codes.");
    }
}
