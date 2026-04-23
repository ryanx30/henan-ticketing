<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1) Admin utama
        $this->call([
            AdminUserSeeder::class,
        ]);

        // 2) User dummy CS
        User::updateOrCreate(
            ['email' => 'cs@example.com'],
            [
                'name' => 'Test CS',
                'role' => 'cs',
                'password' => bcrypt('password'),
            ]
        );

        // 3) User dummy IT
        User::updateOrCreate(
            ['email' => 'it@example.com'],
            [
                'name' => 'Test IT',
                'role' => 'it',
                'password' => bcrypt('password'),
            ]
        );

        // 4) Seeder ticket random
        $this->call([
            TicketSeeder::class,
        ]);
    }
}