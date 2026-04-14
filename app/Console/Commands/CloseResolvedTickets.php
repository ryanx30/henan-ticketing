<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;

class CloseResolvedTickets extends Command
{
    protected $signature = 'tickets:auto-close-resolved';
    protected $description = 'Auto close tickets that have been resolved for >= 1 day';

    public function handle(): int
    {
        $count = Ticket::query()
            ->where('status', 'resolved')
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<=', now()->subDay())
            ->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);

        $this->info("Closed {$count} resolved tickets.");
        return Command::SUCCESS;
    }
}