<?php

use Illuminate\Console\Scheduling\Schedule;

// Schedule
app()->booted(function () {
    /** @var Schedule $schedule */
    $schedule = app(Schedule::class);

    // Auto-close IT tickets resolved before today. Runs once after midnight to keep the daily workflow deterministic.
    $schedule->command('tickets:auto-close-resolved')
        ->dailyAt('00:05')
        ->withoutOverlapping();

    // Phase 4: Daily analytics snapshot. Runs after auto-close so yesterday's data is already finalized.
    $schedule->command('analytics:daily-snapshot')
        ->dailyAt('00:30')
        ->withoutOverlapping();
});
