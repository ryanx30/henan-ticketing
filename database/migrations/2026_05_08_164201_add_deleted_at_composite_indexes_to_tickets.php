<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Composite indexes below deliberately start with deleted_at because Laravel
     * SoftDeletes automatically adds "where deleted_at is null" to the main
     * ticket queries. This lets MySQL use one composite index for soft-delete
     * filtering plus the dashboard/list/queue/report predicates.
     */
    private array $indexes = [
        'tickets_del_status_created_idx' => ['deleted_at', 'status', 'created_at'],
        'tickets_del_team_status_created_idx' => ['deleted_at', 'team_id', 'status', 'created_at'],
        'tickets_del_holder_status_created_idx' => ['deleted_at', 'holder_id', 'status', 'created_at'],
        'tickets_del_creator_created_idx' => ['deleted_at', 'created_by', 'created_at'],
        'tickets_del_priority_status_created_idx' => ['deleted_at', 'priority_id', 'status', 'created_at'],
        'tickets_del_sla_status_idx' => ['deleted_at', 'sla_deadline_at', 'status'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('tickets')) {
            return;
        }

        foreach ($this->indexes as $indexName => $columns) {
            if ($this->indexExists('tickets', $indexName) || !$this->columnsExist('tickets', $columns)) {
                continue;
            }

            Schema::table('tickets', function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('tickets')) {
            return;
        }

        foreach (array_reverse(array_keys($this->indexes)) as $indexName) {
            if (!$this->indexExists('tickets', $indexName)) {
                continue;
            }

            Schema::table('tickets', function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }

    private function columnsExist(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

            return count($rows) > 0;
        }

        return false;
    }
};
