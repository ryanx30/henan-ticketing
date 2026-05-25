<?php

namespace App\Console\Commands;

use App\Services\MasterDataIntegrityService;
use Illuminate\Console\Command;
use Throwable;

class NormalizeMasterData extends Command
{
    protected $signature = 'master-data:normalize {--dry-run : Show planned normalization summary without writing changes}';

    protected $description = 'Normalize and validate master data code_num/system code values for ticket code generation.';

    public function handle(MasterDataIntegrityService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        try {
            $summary = $service->normalize($dryRun);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info($dryRun ? 'Master Data dry-run completed.' : 'Master Data normalized successfully.');
        $this->table(
            ['Area', 'Changed / Ensured'],
            [
                ['Teams', $summary['teams']],
                ['Priorities', $summary['priorities']],
                ['Categories', $summary['categories']],
                ['Issue Types', $summary['issue_types']],
                ['SLA Rules', $summary['sla_rules']],
            ]
        );

        foreach ($summary['warnings'] as $warning) {
            $this->warn($warning);
        }

        return self::SUCCESS;
    }
}
