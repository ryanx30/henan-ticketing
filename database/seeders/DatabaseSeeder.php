<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1) Buat admin dari seeder khusus
        $this->call(
            AdminUserSeeder::class,
        );

        // 2) Buat 1 user testing (misal role CS)
        User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@example.com',
            'role'  => 'cs',
        ]);
    }
}
