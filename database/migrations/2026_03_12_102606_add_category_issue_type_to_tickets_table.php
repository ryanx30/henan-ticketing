<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('category', 50)->nullable()->after('team');
            $table->string('issue_type', 80)->nullable()->after('category');

            $table->index(['category', 'issue_type']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['category', 'issue_type']);
            $table->dropColumn(['category', 'issue_type']);
        });
    }
};