<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('priority_id')->constrained('priorities')->cascadeOnDelete();
            $table->unsignedInteger('hours');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['team_id', 'priority_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_rules');
    }
};