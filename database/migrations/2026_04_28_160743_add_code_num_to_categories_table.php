<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'code_num')) {
                $table->string('code_num', 2)->nullable()->after('id');
            }
        });

        // Optional: isi default code_num untuk data lama
        $rows = DB::table('categories')->orderBy('id')->get();
        $i = 1;
        foreach ($rows as $row) {
            if (empty($row->code_num)) {
                DB::table('categories')
                    ->where('id', $row->id)
                    ->update([
                        'code_num' => str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                    ]);
                $i++;
            }
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->unique('code_num');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'code_num')) {
                $table->dropUnique(['code_num']);
                $table->dropColumn('code_num');
            }
        });
    }
};