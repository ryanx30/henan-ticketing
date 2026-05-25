<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (Schema::hasTable('tickets')) {
            Schema::table('tickets', function (Blueprint $table) {
                $this->addIndexIfPossible($table, 'tickets', ['status', 'holder_id', 'created_at'], 'tickets_status_holder_created_idx');
                $this->addIndexIfPossible($table, 'tickets', ['status', 'created_at'], 'tickets_status_created_idx');
                $this->addIndexIfPossible($table, 'tickets', ['created_by', 'status', 'created_at'], 'tickets_creator_status_created_idx');
                $this->addIndexIfPossible($table, 'tickets', ['team_id', 'holder_id', 'status', 'created_at'], 'tickets_teamid_holder_status_created_idx');
                $this->addIndexIfPossible($table, 'tickets', ['category_id', 'team_id', 'created_at'], 'tickets_category_team_created_idx');
                $this->addIndexIfPossible($table, 'tickets', ['issue_type_id', 'team_id', 'created_at'], 'tickets_issue_team_created_idx');
                $this->addIndexIfPossible($table, 'tickets', ['status', 'resolved_at', 'closed_at'], 'tickets_status_resolved_closed_idx');
                $this->addIndexIfPossible($table, 'tickets', ['status', 'sla_deadline_at', 'created_at'], 'tickets_status_sla_created_idx');
                $this->addIndexIfPossible($table, 'tickets', ['ticket_code', 'created_at'], 'tickets_code_created_idx');
            });

            if ($driver === 'mysql' && $this->hasColumns('tickets', ['title', 'description']) && ! $this->indexExists('tickets', 'tickets_title_description_fulltext')) {
                DB::statement('ALTER TABLE `tickets` ADD FULLTEXT `tickets_title_description_fulltext` (`title`, `description`)');
            }
        }

        if (Schema::hasTable('ticket_status_histories')) {
            Schema::table('ticket_status_histories', function (Blueprint $table) {
                $this->addIndexIfPossible($table, 'ticket_status_histories', ['ticket_id', 'changed_at'], 'ticket_status_histories_ticket_changed_idx');
                $this->addIndexIfPossible($table, 'ticket_status_histories', ['to_status', 'from_status', 'changed_at', 'ticket_id'], 'ticket_status_histories_reopen_idx');
                $this->addIndexIfPossible($table, 'ticket_status_histories', ['to_status', 'changed_at'], 'ticket_status_histories_to_changed_idx');
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $this->addIndexIfPossible($table, 'audit_logs', ['entity_type', 'action', 'created_at'], 'audit_logs_filter_created_idx');
                $this->addIndexIfPossible($table, 'audit_logs', ['actor_id', 'created_at'], 'audit_logs_actor_created_idx');
            });
        }

        if (Schema::hasTable('resolver_messages')) {
            Schema::table('resolver_messages', function (Blueprint $table) {
                $this->addIndexIfPossible($table, 'resolver_messages', ['recipient_id', 'is_read', 'created_at'], 'resolver_messages_recipient_read_created_idx');
                $this->addIndexIfPossible($table, 'resolver_messages', ['ticket_id', 'created_at'], 'resolver_messages_ticket_created_idx');
                $this->addIndexIfPossible($table, 'resolver_messages', ['sender_id', 'created_at'], 'resolver_messages_sender_created_idx');
            });
        }

        if (Schema::hasTable('ticket_attachments')) {
            Schema::table('ticket_attachments', function (Blueprint $table) {
                $this->addIndexIfPossible($table, 'ticket_attachments', ['ticket_id', 'created_at'], 'ticket_attachments_ticket_created_idx');
                $this->addIndexIfPossible($table, 'ticket_attachments', ['uploaded_by', 'created_at'], 'ticket_attachments_uploaded_by_created_idx');
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (Schema::hasTable('tickets')) {
            if ($driver === 'mysql' && $this->indexExists('tickets', 'tickets_title_description_fulltext')) {
                DB::statement('ALTER TABLE `tickets` DROP INDEX `tickets_title_description_fulltext`');
            }

            Schema::table('tickets', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'tickets', 'tickets_status_holder_created_idx');
                $this->dropIndexIfExists($table, 'tickets', 'tickets_status_created_idx');
                $this->dropIndexIfExists($table, 'tickets', 'tickets_creator_status_created_idx');
                $this->dropIndexIfExists($table, 'tickets', 'tickets_teamid_holder_status_created_idx');
                $this->dropIndexIfExists($table, 'tickets', 'tickets_category_team_created_idx');
                $this->dropIndexIfExists($table, 'tickets', 'tickets_issue_team_created_idx');
                $this->dropIndexIfExists($table, 'tickets', 'tickets_status_resolved_closed_idx');
                $this->dropIndexIfExists($table, 'tickets', 'tickets_status_sla_created_idx');
                $this->dropIndexIfExists($table, 'tickets', 'tickets_code_created_idx');
            });
        }

        if (Schema::hasTable('ticket_status_histories')) {
            Schema::table('ticket_status_histories', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'ticket_status_histories', 'ticket_status_histories_ticket_changed_idx');
                $this->dropIndexIfExists($table, 'ticket_status_histories', 'ticket_status_histories_reopen_idx');
                $this->dropIndexIfExists($table, 'ticket_status_histories', 'ticket_status_histories_to_changed_idx');
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'audit_logs', 'audit_logs_filter_created_idx');
                $this->dropIndexIfExists($table, 'audit_logs', 'audit_logs_actor_created_idx');
            });
        }

        if (Schema::hasTable('resolver_messages')) {
            Schema::table('resolver_messages', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'resolver_messages', 'resolver_messages_recipient_read_created_idx');
                $this->dropIndexIfExists($table, 'resolver_messages', 'resolver_messages_ticket_created_idx');
                $this->dropIndexIfExists($table, 'resolver_messages', 'resolver_messages_sender_created_idx');
            });
        }

        if (Schema::hasTable('ticket_attachments')) {
            Schema::table('ticket_attachments', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'ticket_attachments', 'ticket_attachments_ticket_created_idx');
                $this->dropIndexIfExists($table, 'ticket_attachments', 'ticket_attachments_uploaded_by_created_idx');
            });
        }
    }

    private function addIndexIfPossible(Blueprint $table, string $tableName, array $columns, string $indexName): void
    {
        if (! $this->hasColumns($tableName, $columns)) {
            return;
        }

        if ($this->indexExists($tableName, $indexName)) {
            return;
        }

        $table->index($columns, $indexName);
    }

    private function dropIndexIfExists(Blueprint $table, string $tableName, string $indexName): void
    {
        if (! $this->indexExists($tableName, $indexName)) {
            return;
        }

        $table->dropIndex($indexName);
    }

    private function hasColumns(string $tableName, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($tableName, $column)) {
                return false;
            }
        }

        return true;
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('$tableName')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $database = Schema::getConnection()->getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $tableName)
            ->where('index_name', $indexName)
            ->exists();
    }
};