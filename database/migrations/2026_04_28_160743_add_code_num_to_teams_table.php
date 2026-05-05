<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            if (!Schema::hasColumn('teams', 'code_num')) {
                $table->string('code_num', 1)->nullable()->after('id');
            }
        });

        // Mapping default untuk data lama
        $mapping = [
            'it' => '1',
            'finance' => '2',
            'compliance' => '3',
        ];

        $rows = DB::table('teams')->get();
        $fallback = 4;

        foreach ($rows as $row) {
            if (empty($row->code_num)) {
                $codeKey = strtolower((string) ($row->code ?? ''));
                $codeNum = $mapping[$codeKey] ?? (string) $fallback;

                DB::table('teams')
                    ->where('id', $row->id)
                    ->update(['code_num' => $codeNum]);

                if (!isset($mapping[$codeKey])) {
                    $fallback++;
                }
            }
        }

        Schema::table('teams', function (Blueprint $table) {
            $table->unique('code_num');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            if (Schema::hasColumn('teams', 'code_num')) {
                $table->dropUnique(['code_num']);
                $table->dropColumn('code_num');
            }
        });
    }
};