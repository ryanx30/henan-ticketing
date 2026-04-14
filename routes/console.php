<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;
use App\Models\Ticket;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Command: auto-close ticket yang status=resolved dan resolved_at-nya sudah lewat ganti hari
 * Rule: kalau resolved_at < startOfToday => set closed
 */
Artisan::command('tickets:auto-close-resolved', function () {
    $todayStart = now()->startOfDay();

    // kalau ada ticket resolved tapi resolved_at null (legacy), kita fallback ke updated_at
    $affected = Ticket::query()
        ->where('team', 'it')
        ->where('status', 'resolved')
        ->where(function ($q) use ($todayStart) {
            $q->whereNotNull('resolved_at')->where('resolved_at', '<', $todayStart)
              ->orWhere(function ($qq) use ($todayStart) {
                  $qq->whereNull('resolved_at')->where('updated_at', '<', $todayStart);
              });
        })
        ->update([
            'status'    => 'closed',
            'closed_at' => now(),
        ]);

    $this->info("Auto-closed resolved tickets: {$affected}");
})->purpose('Auto close resolved IT tickets after day change');

// Schedule (Laravel 11 style)
app()->booted(function () {
    /** @var Schedule $schedule */
    $schedule = app(Schedule::class);

    $schedule->command('tickets:auto-close-resolved')->everyFiveMinutes();
});