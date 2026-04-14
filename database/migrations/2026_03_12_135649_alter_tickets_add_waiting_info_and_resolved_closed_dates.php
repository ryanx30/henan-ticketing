<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Add columns
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('claimed_at');
            }
            if (!Schema::hasColumn('tickets', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('resolved_at');
            }
        });
        
        // 2) Update enum (add waiting_info)
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('new','in_progress','waiting_info','resolved','closed') NOT NULL DEFAULT 'new'");
    }

    public function down(): void
    {
        // revert enum (drop waiting_info)
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('new','in_progress','resolved','closed') NOT NULL DEFAULT 'new'");

        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'closed_at')) {
                $table->dropColumn('closed_at');
            }
            if (Schema::hasColumn('tickets', 'resolved_at')) {
                $table->dropColumn('resolved_at');
            }
        });
    }
};