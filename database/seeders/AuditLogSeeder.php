<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\ResolverMessage;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Support\AuditLogger;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        AuditLog::query()->delete();

        Ticket::query()
            ->with(['creator'])
            ->orderBy('id')
            ->chunk(100, function ($tickets) {
                foreach ($tickets as $ticket) {
                    $actor = $ticket->creator;

                    AuditLog::create([
                        'actor_id' => $actor?->id,
                        'actor_name' => $actor?->name,
                        'actor_email' => $actor?->email,
                        'actor_role' => $actor?->role,
                        'action' => 'created',
                        'entity_type' => 'ticket',
                        'entity_id' => $ticket->id,
                        'entity_label' => AuditLogger::ticketLabel($ticket),
                        'description' => 'Created ticket ' . AuditLogger::ticketLabel($ticket) . ': ' . $ticket->title,
                        'before_values' => null,
                        'after_values' => $this->snapshotTicket($ticket),
                        'ip_address' => null,
                        'user_agent' => 'AuditLogSeeder backfill',
                        'created_at' => $ticket->created_at,
                        'updated_at' => $ticket->created_at,
                    ]);
                }
            });

        TicketStatusHistory::query()
            ->with(['ticket', 'changer'])
            ->orderBy('id')
            ->chunk(100, function ($histories) {
                foreach ($histories as $history) {
                    if (!$history->ticket) {
                        continue;
                    }

                    $actor = $history->changer;

                    AuditLog::create([
                        'actor_id' => $actor?->id,
                        'actor_name' => $actor?->name,
                        'actor_email' => $actor?->email,
                        'actor_role' => $actor?->role,
                        'action' => $history->from_status ? 'status_changed' : 'created',
                        'entity_type' => 'ticket',
                        'entity_id' => $history->ticket_id,
                        'entity_label' => AuditLogger::ticketLabel($history->ticket),
                        'description' => $history->from_status
                            ? 'Changed ticket status from ' . $history->from_status . ' to ' . $history->to_status . ' for ' . AuditLogger::ticketLabel($history->ticket)
                            : 'Initial status set to ' . $history->to_status . ' for ' . AuditLogger::ticketLabel($history->ticket),
                        'before_values' => $history->from_status ? ['status' => $history->from_status] : null,
                        'after_values' => ['status' => $history->to_status, 'note' => $history->note],
                        'ip_address' => null,
                        'user_agent' => 'AuditLogSeeder backfill',
                        'created_at' => $history->changed_at ?: $history->created_at,
                        'updated_at' => $history->changed_at ?: $history->updated_at,
                    ]);
                }
            });

        ResolverMessage::query()
            ->with(['sender', 'ticket'])
            ->orderBy('id')
            ->chunk(100, function ($messages) {
                foreach ($messages as $message) {
                    $actor = $message->sender;

                    AuditLog::create([
                        'actor_id' => $actor?->id,
                        'actor_name' => $actor?->name,
                        'actor_email' => $actor?->email,
                        'actor_role' => $actor?->role,
                        'action' => 'sent',
                        'entity_type' => 'resolver_message',
                        'entity_id' => $message->id,
                        'entity_label' => $message->subject,
                        'description' => 'Sent resolver message' . ($message->ticket ? ' for ' . AuditLogger::ticketLabel($message->ticket) : ''),
                        'before_values' => null,
                        'after_values' => [
                            'id' => $message->id,
                            'ticket_id' => $message->ticket_id,
                            'from_user_id' => $message->from_user_id,
                            'to_user_id' => $message->to_user_id,
                            'subject' => $message->subject,
                            'is_read' => (bool) $message->is_read,
                        ],
                        'ip_address' => null,
                        'user_agent' => 'AuditLogSeeder backfill',
                        'created_at' => $message->created_at,
                        'updated_at' => $message->updated_at,
                    ]);
                }
            });
    }

    protected function snapshotTicket(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'ticket_code' => $ticket->ticket_code,
            'title' => $ticket->title,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'team' => $ticket->team,
            'category' => $ticket->category,
            'issue_type' => $ticket->issue_type,
            'created_by' => $ticket->created_by,
            'holder_id' => $ticket->holder_id,
        ];
    }
}
