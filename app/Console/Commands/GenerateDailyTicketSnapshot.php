<?php

namespace App\Console\Commands;

use App\Services\DailyTicketSnapshotService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

/**
 * Generates daily ticket statistics used by reporting and case analytics.
 */
class GenerateDailyTicketSnapshot extends Command
{
    /**
     * Contoh penggunaan:
     *   php artisan analytics:daily-snapshot              → kemarin (default produksi)
     *   php artisan analytics:daily-snapshot --date=2026-05-01
     *   php artisan analytics:daily-snapshot --backfill=30
     */
    protected $signature = 'analytics:daily-snapshot
                            {--date=     : Tanggal target (Y-m-d). Default: kemarin.}
                            {--backfill= : Jumlah hari ke belakang dari kemarin untuk di-backfill.}';

    protected $description = 'Generate (atau re-generate) daily_ticket_stats snapshot untuk satu atau beberapa hari.';

    public function __construct(
        private DailyTicketSnapshotService $snapshotService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dates = $this->resolveDates();

        if (empty($dates)) {
            $this->error('Tidak ada tanggal yang bisa diproses.');
            return Command::FAILURE;
        }

        $successCount = 0;
        $failCount    = 0;

        foreach ($dates as $date) {
            try {
                $this->line("→ Generating snapshot untuk {$date->toDateString()}...");
                $dimensionCount = $this->snapshotService->snapshot($date);
                $this->info("  ✓ {$date->toDateString()} — {$dimensionCount} dimensi diproses.");
                $successCount++;
            } catch (Throwable $e) {
                report($e);
                $this->error("  ✗ Gagal snapshot {$date->toDateString()}: {$e->getMessage()}");
                $failCount++;
            }
        }

        $this->newLine();
        $this->info("Selesai. Sukses: {$successCount} hari, Gagal: {$failCount} hari.");

        return $failCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /** @return Carbon[] */
    private function resolveDates(): array
    {
        $backfill = (int) $this->option('backfill');

        if ($backfill > 0) {
            $dates = [];
            for ($i = $backfill; $i >= 1; $i--) {
                $dates[] = now()->subDays($i)->startOfDay();
            }
            return $dates;
        }

        $dateOption = $this->option('date');

        if ($dateOption) {
            try {
                return [Carbon::createFromFormat('Y-m-d', $dateOption)->startOfDay()];
            } catch (Throwable) {
                $this->error("Format tanggal tidak valid: {$dateOption}. Gunakan Y-m-d.");
                return [];
            }
        }

        // Default: kemarin
        return [now()->subDay()->startOfDay()];
    }
}
