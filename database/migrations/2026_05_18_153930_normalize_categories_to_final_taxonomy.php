<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Keep only the final business taxonomy active for new ticket classification.
     *
     * Historical categories are not deleted because existing tickets and issue types
     * may still reference them through foreign keys. They are only deactivated so
     * they disappear from active forms while old reports remain readable.
     */
    public function up(): void
    {
        $now = now();

        $finalCategories = [
            ['code_num' => '01', 'name' => 'Account', 'slug' => 'account'],
            ['code_num' => '02', 'name' => 'Trading', 'slug' => 'trading'],
            ['code_num' => '03', 'name' => 'Fund', 'slug' => 'fund'],
            ['code_num' => '04', 'name' => 'Compliance', 'slug' => 'compliance'],
            ['code_num' => '05', 'name' => 'System', 'slug' => 'system'],
            ['code_num' => '06', 'name' => 'General', 'slug' => 'general'],
        ];

        $finalSlugs = array_column($finalCategories, 'slug');

        DB::table('categories')
            ->whereNotIn('slug', $finalSlugs)
            ->update([
                'is_active' => false,
                'updated_at' => $now,
            ]);

        foreach ($finalCategories as $category) {
            $existingId = DB::table('categories')
                ->where('slug', $category['slug'])
                ->value('id');

            if ($existingId) {
                DB::table('categories')
                    ->where('id', $existingId)
                    ->update([
                        'code_num' => $category['code_num'],
                        'name' => $category['name'],
                        'is_active' => true,
                        'updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('categories')->insert([
                'code_num' => $category['code_num'],
                'name' => $category['name'],
                'slug' => $category['slug'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('categories')
            ->whereIn('slug', ['access', 'incident', 'request', 'finance-ops'])
            ->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }
};
