<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('holder_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->dateTime('claimed_at')->nullable()->after('holder_id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('holder_id');
            $table->dropColumn('claimed_at');
        });
    }
};