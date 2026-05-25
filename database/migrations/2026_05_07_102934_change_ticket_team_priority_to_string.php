<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tickets')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `tickets` MODIFY `team` VARCHAR(50) NOT NULL DEFAULT 'it'");
            DB::statement("ALTER TABLE `tickets` MODIFY `priority` VARCHAR(50) NOT NULL DEFAULT 'medium'");

            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'team')) {
                $table->string('team', 50)->default('it')->change();
            }

            if (Schema::hasColumn('tickets', 'priority')) {
                $table->string('priority', 50)->default('medium')->change();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tickets')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `tickets` MODIFY `team` ENUM('it', 'finance', 'compliance') NOT NULL DEFAULT 'it'");
            DB::statement("ALTER TABLE `tickets` MODIFY `priority` ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium'");

            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'team')) {
                $table->enum('team', ['it', 'finance', 'compliance'])->default('it')->change();
            }

            if (Schema::hasColumn('tickets', 'priority')) {
                $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium')->change();
            }
        });
    }
};
