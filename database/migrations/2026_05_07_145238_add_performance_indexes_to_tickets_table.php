<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        'tickets_status_team_holder_created_idx' => ['status', 'team', 'holder_id', 'created_at'],
        'tickets_team_status_created_idx' => ['team', 'status', 'created_at'],
        'tickets_status_priority_created_idx' => ['status', 'priority', 'created_at'],
        'tickets_client_created_idx' => ['client_id', 'created_at'],
        'tickets_createdby_created_idx' => ['created_by', 'created_at'],
        'tickets_resolved_status_idx' => ['resolved_at', 'status'],
        'tickets_closed_status_idx' => ['closed_at', 'status'],
        'tickets_sla_status_idx' => ['sla_deadline_at', 'status'],
        'tickets_teamid_status_created_idx' => ['team_id', 'status', 'created_at'],
        'tickets_categoryid_issueid_idx' => ['category_id', 'issue_type_id'],
        'tickets_priorityid_status_idx' => ['priority_id', 'status'],
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
