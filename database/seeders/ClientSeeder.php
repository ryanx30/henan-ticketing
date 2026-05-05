<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Ticket;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('clients') || !Schema::hasColumn('tickets', 'client_id')) {
            $this->command->warn('ClientSeeder skipped: clients table or tickets.client_id column is not available. Run migrations first.');
            return;
        }

        $createdOrLinked = 0;

        Ticket::query()
            ->where(function ($query) {
                $query->whereNotNull('client_name')
                    ->orWhereNotNull('client_email')
                    ->orWhereNotNull('client_contact');
            })
            ->orderBy('id')
            ->chunkById(200, function ($tickets) use (&$createdOrLinked) {
                foreach ($tickets as $ticket) {
                    $client = Client::resolveForTicket([
                        'client_id' => $ticket->client_id,
                        'client_name' => $ticket->client_name,
                        'client_contact' => $ticket->client_contact,
                        'client_email' => $ticket->client_email,
                    ]);

                    if (!$client) {
                        continue;
                    }

                    if ((int) $ticket->client_id !== (int) $client->id) {
                        $ticket->forceFill(['client_id' => $client->id])->save();
                    }

                    $createdOrLinked++;
                }
            });

        $this->command->info("Client directory backfilled successfully. {$createdOrLinked} ticket(s) linked to clients.");
    }
}
