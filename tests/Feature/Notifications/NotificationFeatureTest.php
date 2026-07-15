<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationState;
use App\Models\ResolverMessage;
use App\Models\TicketStatusHistory;
use App\Models\User;
use App\Services\Notifications\NotificationPayloadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesTicketFixtures;
use Tests\TestCase;

class NotificationFeatureTest extends TestCase
{
    use MakesTicketFixtures;
    use RefreshDatabase;

    public function test_it_action_notifications_are_deduplicated_by_ticket(): void
    {
        $creator = $this->activeUser(User::ROLE_CS);
        $it = $this->activeUser(User::ROLE_IT);
        $ticket = $this->ticketFor($creator, [
            'status' => 'new',
            'holder_id' => null,
            'sla_deadline_at' => now()->subMinutes(5),
        ]);

        $payload = app(NotificationPayloadService::class)->payloadFor($it, 20);
        $ticketItems = collect($payload['latest'])
            ->where('ticket_id', $ticket->id)
            ->values();

        $this->assertCount(1, $ticketItems);
        $this->assertSame('sla_breached', $ticketItems->first()['type']);
        $this->assertSame(1, $payload['action_count']);
        $this->assertSame(1, $payload['unread_count']);
    }

    public function test_waiting_info_notifies_cs_but_does_not_remain_in_it_my_queue(): void
    {
        $creator = $this->activeUser(User::ROLE_CS);
        $headCs = $this->activeUser(User::ROLE_HEAD_CS);
        $it = $this->activeUser(User::ROLE_IT);
        $ticket = $this->ticketFor($creator, [
            'status' => 'waiting_info',
            'holder_id' => $it->id,
            'sla_deadline_at' => null,
        ]);

        $service = app(NotificationPayloadService::class);
        $itPayload = $service->payloadFor($it, 20);
        $csPayload = $service->payloadFor($creator, 20);
        $headCsPayload = $service->payloadFor($headCs, 20);

        $this->assertFalse(collect($itPayload['latest'])->contains(
            fn (array $item): bool => $item['ticket_id'] === $ticket->id
        ));
        $this->assertTrue(collect($csPayload['latest'])->contains(
            fn (array $item): bool => $item['ticket_id'] === $ticket->id && $item['type'] === 'waiting_info'
        ));
        $this->assertTrue(collect($headCsPayload['latest'])->contains(
            fn (array $item): bool => $item['ticket_id'] === $ticket->id && $item['type'] === 'waiting_info'
        ));
    }

    public function test_mark_all_read_updates_notification_state_and_resolver_messages(): void
    {
        $creator = $this->activeUser(User::ROLE_CS);
        $it = $this->activeUser(User::ROLE_IT);
        $ticket = $this->ticketFor($creator, [
            'status' => 'new',
            'holder_id' => null,
            'sla_deadline_at' => null,
        ]);

        $message = ResolverMessage::query()->create([
            'ticket_id' => $ticket->id,
            'from_user_id' => $creator->id,
            'to_user_id' => $it->id,
            'subject' => 'Client evidence',
            'body' => 'Additional screenshot is available.',
            'is_read' => false,
        ]);

        $response = $this
            ->actingAs($it)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/notifications/read-all');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.unread_count', 0)
            ->assertJsonPath('data.action_count', 1);

        $this->assertTrue($message->fresh()->is_read);
        $this->assertDatabaseHas('notification_states', [
            'user_id' => $it->id,
            'notification_key' => 'resolver_message:' . $message->id,
        ]);
        $this->assertDatabaseHas('notification_states', [
            'user_id' => $it->id,
            'notification_key' => 'ticket:' . $ticket->id . ':team_queue',
        ]);
    }

    public function test_it_receives_a_new_action_when_waiting_information_is_returned(): void
    {
        $creator = $this->activeUser(User::ROLE_CS);
        $it = $this->activeUser(User::ROLE_IT);
        $ticket = $this->ticketFor($creator, [
            'status' => 'in_progress',
            'holder_id' => $it->id,
            'sla_deadline_at' => null,
        ]);

        $history = TicketStatusHistory::query()->create([
            'ticket_id' => $ticket->id,
            'from_status' => 'waiting_info',
            'to_status' => 'in_progress',
            'changed_by' => $creator->id,
            'changed_at' => now(),
            'note' => 'Requested information supplied.',
        ]);

        $payload = app(NotificationPayloadService::class)->payloadFor($it, 20);
        $item = collect($payload['latest'])->first(
            fn (array $item): bool => $item['ticket_id'] === $ticket->id
        );

        $this->assertSame('information_received', $item['type']);
        $this->assertSame('ticket_status_history:' . $history->id, $item['key']);
        $this->assertTrue($item['requires_action']);
    }

    public function test_informational_notification_can_be_dismissed_but_action_alert_cannot(): void
    {
        $creator = $this->activeUser(User::ROLE_CS);
        $it = $this->activeUser(User::ROLE_IT);
        $resolvedTicket = $this->ticketFor($creator, [
            'status' => 'resolved',
            'holder_id' => $it->id,
            'resolved_at' => now(),
            'sla_deadline_at' => null,
        ]);

        $history = TicketStatusHistory::query()->create([
            'ticket_id' => $resolvedTicket->id,
            'from_status' => 'in_progress',
            'to_status' => 'resolved',
            'changed_by' => $it->id,
            'changed_at' => now(),
            'note' => 'Issue resolved.',
        ]);

        $dismissResponse = $this
            ->actingAs($creator)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/notifications/dismiss', [
                'key' => 'ticket_status_history:' . $history->id,
            ]);

        $dismissResponse->assertOk();
        $state = NotificationState::query()
            ->where('user_id', $creator->id)
            ->where('notification_key', 'ticket_status_history:' . $history->id)
            ->firstOrFail();
        $this->assertNotNull($state->dismissed_at);

        $waitingTicket = $this->ticketFor($creator, [
            'ticket_code' => '101001300002',
            'status' => 'waiting_info',
            'holder_id' => $it->id,
            'sla_deadline_at' => null,
        ]);

        $waitingNotification = collect(app(NotificationPayloadService::class)->payloadFor($creator, 20)['latest'])
            ->first(fn (array $item): bool => $item['ticket_id'] === $waitingTicket->id);

        $actionResponse = $this
            ->actingAs($creator)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/notifications/dismiss', [
                'key' => $waitingNotification['key'],
            ]);

        $actionResponse->assertStatus(422)
            ->assertJsonPath('status', false);
    }
}
