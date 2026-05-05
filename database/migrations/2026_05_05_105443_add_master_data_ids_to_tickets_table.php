<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tickets')) {
            return;
        }

        if (!Schema::hasColumn('tickets', 'team_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->foreignId('team_id')
                    ->nullable()
                    ->after('team')
                    ->constrained('teams')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('tickets', 'priority_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->foreignId('priority_id')
                    ->nullable()
                    ->after('priority')
                    ->constrained('priorities')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('tickets', 'category_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->foreignId('category_id')
                    ->nullable()
                    ->after('category')
                    ->constrained('categories')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('tickets', 'issue_type_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->foreignId('issue_type_id')
                    ->nullable()
                    ->after('issue_type')
                    ->constrained('issue_types')
                    ->nullOnDelete();
            });
        }

        $this->backfillMasterDataIds();
    }

    public function down(): void
    {
        if (!Schema::hasTable('tickets')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'issue_type_id')) {
                $table->dropConstrainedForeignId('issue_type_id');
            }

            if (Schema::hasColumn('tickets', 'category_id')) {
                $table->dropConstrainedForeignId('category_id');
            }

            if (Schema::hasColumn('tickets', 'priority_id')) {
                $table->dropConstrainedForeignId('priority_id');
            }

            if (Schema::hasColumn('tickets', 'team_id')) {
                $table->dropConstrainedForeignId('team_id');
            }
        });
    }

    private function backfillMasterDataIds(): void
    {
        DB::table('tickets')
            ->select('id', 'team', 'priority', 'category', 'issue_type')
            ->orderBy('id')
            ->chunkById(200, function ($tickets) {
                foreach ($tickets as $ticket) {
                    $teamId = $this->findTeamId($ticket->team);
                    $priorityId = $this->findPriorityId($ticket->priority);
                    $categoryId = $this->findCategoryId($ticket->category);
                    $issueTypeId = $this->findIssueTypeId($ticket->issue_type, $categoryId);

                    DB::table('tickets')
                        ->where('id', $ticket->id)
                        ->update([
                            'team_id' => $teamId,
                            'priority_id' => $priorityId,
                            'category_id' => $categoryId,
                            'issue_type_id' => $issueTypeId,
                        ]);
                }
            });
    }

    private function findTeamId(?string $value): ?int
    {
        return $this->findLookupId('teams', $value, ['code', 'name']);
    }

    private function findPriorityId(?string $value): ?int
    {
        return $this->findLookupId('priorities', $value, ['code', 'name']);
    }

    private function findCategoryId(?string $value): ?int
    {
        if (!$value) {
            return null;
        }

        $raw = $this->rawLower($value);
        $normalized = $this->normalize($value);
        $slug = Str::slug($value);

        $row = DB::table('categories')
            ->where(function ($query) use ($raw, $normalized, $slug) {
                $query->whereRaw('LOWER(name) = ?', [$raw])
                    ->orWhereRaw('LOWER(slug) = ?', [$raw])
                    ->orWhereRaw('LOWER(name) = ?', [$normalized])
                    ->orWhereRaw('LOWER(slug) = ?', [$normalized])
                    ->orWhere('slug', $slug);
            })
            ->first();

        return $row?->id ? (int) $row->id : null;
    }

    private function findIssueTypeId(?string $value, ?int $categoryId): ?int
    {
        if (!$value) {
            return null;
        }

        $raw = $this->rawLower($value);
        $normalized = $this->normalize($value);
        $slug = Str::slug($value);

        $query = DB::table('issue_types')
            ->where(function ($query) use ($raw, $normalized, $slug) {
                $query->whereRaw('LOWER(name) = ?', [$raw])
                    ->orWhereRaw('LOWER(slug) = ?', [$raw])
                    ->orWhereRaw('LOWER(name) = ?', [$normalized])
                    ->orWhereRaw('LOWER(slug) = ?', [$normalized])
                    ->orWhere('slug', $slug);
            });

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $row = $query->first();

        return $row?->id ? (int) $row->id : null;
    }

    private function findLookupId(string $table, ?string $value, array $columns): ?int
    {
        if (!$value) {
            return null;
        }

        $raw = $this->rawLower($value);
        $normalized = $this->normalize($value);

        $query = DB::table($table)->where(function ($query) use ($columns, $raw, $normalized) {
            foreach ($columns as $column) {
                $query->orWhereRaw("LOWER({$column}) = ?", [$raw])
                    ->orWhereRaw("LOWER({$column}) = ?", [$normalized]);
            }
        });

        $row = $query->first();

        return $row?->id ? (int) $row->id : null;
    }

    private function rawLower(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->squish()
            ->toString();
    }

    private function normalize(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replace('-', '_')
            ->replace(' ', '_')
            ->squish()
            ->toString();
    }
};
