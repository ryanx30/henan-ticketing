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
        // 1) Admin utama dan Master Data source of truth
        $this->call([
            AdminUserSeeder::class,
            MasterDataSeeder::class,
        ]);

        // 2) User dummy CS
        User::updateOrCreate(
            ['email' => 'cs@henanputihrai.com'],
            [
                'name' => 'Test CS',
                'role' => 'cs',
                'password' => bcrypt('password'),
            ]
        );

        // 3) User dummy IT
        User::updateOrCreate(
            ['email' => 'it@henanputihrai.com'],
            [
                'name' => 'Test IT',
                'role' => 'it',
                'password' => bcrypt('password'),
            ]
        );

        // 4) User dummy SPV
        User::updateOrCreate(
            ['email' => 'spv@henanputihrai.com'],
            [
                'name' => 'Test SPV',
                'role' => 'supervisor',
                'password' => bcrypt('password'),
            ]
        );

        // 5) Seeder ticket random
        $this->call([
            TicketSeeder::class,
        ]);
    }
}