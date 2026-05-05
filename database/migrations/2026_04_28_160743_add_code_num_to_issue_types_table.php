<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issue_types', function (Blueprint $table) {
            if (!Schema::hasColumn('issue_types', 'code_num')) {
                $table->string('code_num', 3)->nullable()->after('category_id');
            }
        });

        // Isi default code_num per category
        $categories = DB::table('categories')->pluck('id');

        foreach ($categories as $categoryId) {
            $rows = DB::table('issue_types')
                ->where('category_id', $categoryId)
                ->orderBy('id')
                ->get();

            $i = 1;
            foreach ($rows as $row) {
                if (empty($row->code_num)) {
                    DB::table('issue_types')
                        ->where('id', $row->id)
                        ->update([
                            'code_num' => str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                        ]);
                    $i++;
                }
            }
        }

        Schema::table('issue_types', function (Blueprint $table) {
            $table->unique(['category_id', 'code_num'], 'issue_types_category_id_code_num_unique');
        });
    }

    public function down(): void
    {
        Schema::table('issue_types', function (Blueprint $table) {
            if (Schema::hasColumn('issue_types', 'code_num')) {
                $table->dropUnique('issue_types_category_id_code_num_unique');
                $table->dropColumn('code_num');
            }
        });
    }
};