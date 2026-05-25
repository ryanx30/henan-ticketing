<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        'tickets_case_analytics_created_team_category_issue_idx' => ['deleted_at', 'created_at', 'team_id', 'category_id', 'issue_type_id'],
        'tickets_case_analytics_team_created_status_idx' => ['deleted_at', 'team_id', 'created_at', 'status'],
        'tickets_case_analytics_created_resolved_closed_idx' => ['deleted_at', 'created_at', 'resolved_at', 'closed_at'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            foreach ($this->indexes as $name => $columns) {
                if (! $this->indexExists('tickets', $name)) {
                    $table->index($columns, $name);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            foreach (array_keys($this->indexes) as $name) {
                if ($this->indexExists('tickets', $name)) {
                    $table->dropIndex($name);
                }
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
