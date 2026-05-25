<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_ticket_stats', function (Blueprint $table) {
            $table->id();

            // Tanggal snapshot — satu baris per kombinasi (date, team_id, priority_id)
            $table->date('stat_date')->index();

            // Dimensi agregasi — null berarti "semua"
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->unsignedBigInteger('priority_id')->nullable()->index();

            // --- Volume ---
            $table->unsignedInteger('tickets_created')->default(0);
            $table->unsignedInteger('tickets_resolved')->default(0);
            $table->unsignedInteger('tickets_closed')->default(0);
            $table->unsignedInteger('tickets_auto_closed')->default(0);
            $table->unsignedInteger('tickets_reopened')->default(0);

            // --- SLA ---
            $table->unsignedInteger('sla_breached')->default(0);
            $table->unsignedInteger('sla_met')->default(0);

            // Sum + count dipisah supaya bisa digabung lintas hari tanpa distorsi rata-rata
            $table->unsignedBigInteger('first_response_seconds_sum')->default(0);
            $table->unsignedInteger('first_response_count')->default(0);

            $table->unsignedBigInteger('resolution_seconds_sum')->default(0);
            $table->unsignedInteger('resolution_count')->default(0);

            // Snapshot jumlah ticket yang masih open di akhir hari (untuk trend line)
            $table->unsignedInteger('open_at_end_of_day')->default(0);

            $table->timestamps();

            // Unique: satu baris per (date, team, priority)
            $table->unique(['stat_date', 'team_id', 'priority_id'], 'dts_date_team_priority_unique');

            $table->foreign('team_id')->references('id')->on('teams')->nullOnDelete();
            $table->foreign('priority_id')->references('id')->on('priorities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_ticket_stats');
    }
};
