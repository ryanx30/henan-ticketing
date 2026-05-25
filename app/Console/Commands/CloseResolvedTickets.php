<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Services\TicketWorkflowService;
use Illuminate\Console\Command;
use Throwable;

class CloseResolvedTickets extends Command
{
    protected $signature = 'tickets:auto-close-resolved';
    protected $description = 'Auto close IT tickets that were resolved before today';

    public function __construct(
        private TicketWorkflowService $ticketWorkflowService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $todayStart = now()->startOfDay();
        $closedCount = 0;

        Ticket::query()
            ->forTeamCode('it')
            ->where('status', TicketWorkflowService::STATUS_RESOLVED)
            ->where(function ($query) use ($todayStart) {
                $query->where('resolved_at', '<', $todayStart)
                    ->orWhere(function ($fallback) use ($todayStart) {
                        $fallback->whereNull('resolved_at')
                            ->where('updated_at', '<', $todayStart);
                    });
            })
            ->orderBy('id')
            ->chunkById(100, function ($tickets) use (&$closedCount) {
                foreach ($tickets as $ticket) {
                    try {
                        $this->ticketWorkflowService->autoClose(
                            $ticket,
                            'Auto-closed by scheduled command after resolved day changed.',
                            [
                                'user_agent' => 'artisan tickets:auto-close-resolved',
                            ]
                        );

                        $closedCount++;
                    } catch (Throwable $e) {
                        report($e);
                        $this->error('Failed to auto-close ticket ID ' . $ticket->id . ': ' . $e->getMessage());
                    }
                }
            });

        $this->info("Auto-closed resolved tickets: {$closedCount}");

        return Command::SUCCESS;
    }
}
