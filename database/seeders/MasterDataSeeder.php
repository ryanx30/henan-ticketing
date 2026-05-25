<?php

namespace Database\Seeders;

use App\Services\MasterDataIntegrityService;
use Illuminate\Database\Seeder;
use Throwable;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        try {
            $summary = app(MasterDataIntegrityService::class)->normalize(false);
        } catch (Throwable $e) {
            $this->command?->error($e->getMessage());
            throw $e;
        }

        $this->command?->info(sprintf(
            'Master Data ready. Teams:%d Priorities:%d Categories:%d Issue Types:%d SLA Rules:%d',
            $summary['teams'],
            $summary['priorities'],
            $summary['categories'],
            $summary['issue_types'],
            $summary['sla_rules'],
        ));
    }
}
