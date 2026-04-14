<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // MySQL: enum -> varchar pakai statement
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'new'");
    }

    public function down(): void
    {
        // balikin ke enum awal (tanpa waiting_info)
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('new','in_progress','resolved','closed') NOT NULL DEFAULT 'new'");
    }
};