<?php

namespace App\Console\Commands;

use App\Services\DailyTicketSnapshotService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

/**
 * Backfill daily_ticket_stats dari data historis yang sudah ada.
 *
 * Jalankan SEKALI setelah migration:
 *   php artisan analytics:backfill-stats
 *   php artisan analytics:backfill-stats --days=90
 *   php artisan analytics:backfill-stats --from=2026-01-01
 *   php artisan analytics:backfill-stats --days=90 --dry-run
 */
class BackfillDailyTicketStats extends Command
{
    protected $signature = 'analytics:backfill-stats
                            {--days=365   : Jumlah hari ke belakang dari kemarin.}
                            {--from=      : Mulai dari tanggal ini (Y-m-d). Override --days.}
                            {--dry-run    : Hitung tapi jangan simpan ke database.}';

    protected $description = 'Backfill daily_ticket_stats dari data historis yang sudah ada.';

    public function __construct(
        private DailyTicketSnapshotService $snapshotService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $dates  = $this->resolveDates();

        if (empty($dates)) {
            $this->error('Tidak ada tanggal yang valid.');
            return Command::FAILURE;
        }

        $totalDays = count($dates);
        $this->info("Backfill {$totalDays} hari" . ($dryRun ? ' (DRY RUN — tidak disimpan)' : '') . '...');
        $this->newLine();

        $bar = $this->output->createProgressBar($totalDays);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->start();

        $successCount = 0;
        $failCount    = 0;

        foreach ($dates as $date) {
            $bar->setMessage($date->toDateString());

            try {
                if (!$dryRun) {
                    $this->snapshotService->snapshot($date);
                }

                $successCount++;
            } catch (Throwable $e) {
                report($e);
                $this->newLine();
                $this->error("  ✗ Gagal {$date->toDateString()}: {$e->getMessage()}");
                $failCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Selesai. Sukses: {$successCount}, Gagal: {$failCount}.");

        if ($dryRun) {
            $this->comment('DRY RUN — tidak ada data yang disimpan ke database.');
        }

        return $failCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /** @return Carbon[] */
    private function resolveDates(): array
    {
        $from = $this->option('from');

        if ($from) {
            try {
                $startDate = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
            } catch (Throwable) {
                $this->error("Format tanggal tidak valid: {$from}. Gunakan Y-m-d.");
                return [];
            }
        } else {
            $days      = max(1, (int) $this->option('days'));
            $startDate = now()->subDays($days)->startOfDay();
        }

        $endDate = now()->subDay()->startOfDay(); // sampai kemarin

        if ($startDate->gt($endDate)) {
            $this->error('Tanggal mulai lebih besar dari kemarin. Tidak ada yang di-backfill.');
            return [];
        }

        $dates  = [];
        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            $dates[] = $cursor->copy();
            $cursor->addDay();
        }

        return $dates;
    }
}
