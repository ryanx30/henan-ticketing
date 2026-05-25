<?php

namespace Tests\Feature\Tickets;

use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesTicketFixtures;
use Tests\TestCase;

class TicketLifecycleFeatureTest extends TestCase
{
    use RefreshDatabase;
    use MakesTicketFixtures;

    public function test_cs_can_create_ticket_with_master_data_ids(): void
    {
        $cs = $this->activeUser(User::ROLE_CS);
        [$team, $category, $issueType, $priority] = $this->masterData();

        $response = $this->actingAs($cs)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/tickets', [
                'title' => 'Login failure from mobile app',
                'description' => 'Client cannot login using registered email.',
                'team_id' => $team->id,
                'category_id' => $category->id,
                'issue_type_id' => $issueType->id,
                'priority_id' => $priority->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'new')
            ->assertJsonPath('data.status_label', 'New');

        $this->assertDatabaseHas('tickets', [
            'title' => 'Login failure from mobile app',
            'created_by' => $cs->id,
            'status' => 'new',
            'team_id' => $team->id,
        ]);
    }


    public function test_non_it_ticket_is_auto_closed_on_create(): void
    {
        $cs = $this->activeUser(User::ROLE_CS);
        [$team, $category, $issueType, $priority] = $this->masterData('finance');

        $response = $this->actingAs($cs)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/tickets', [
                'title' => 'Finance confirmation request',
                'description' => 'Client asks finance team to confirm transaction mutation.',
                'team_id' => $team->id,
                'category_id' => $category->id,
                'issue_type_id' => $issueType->id,
                'priority_id' => $priority->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'closed');

        $ticketId = $response->json('data.id');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticketId,
            'team' => 'finance',
            'status' => 'closed',
            'holder_id' => null,
            'claimed_at' => null,
            'sla_deadline_at' => null,
        ]);

        $ticket = \App\Models\Ticket::query()->findOrFail($ticketId);

        $this->assertNotNull($ticket->resolved_at);
        $this->assertNotNull($ticket->closed_at);

        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticketId,
            'from_status' => null,
            'to_status' => 'closed',
            'changed_by' => $cs->id,
        ]);
    }

    public function test_cs_can_update_own_ticket_without_regenerating_ticket_code(): void
    {
        $cs = $this->activeUser(User::ROLE_CS);
        $ticket = $this->ticketFor($cs, ['ticket_code' => '101001200001']);
        [$team, $category, $issueType, $priority] = $this->masterData();

        $response = $this->actingAs($cs)
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson("/api/tickets/{$ticket->id}", [
                'title' => 'Updated login issue',
                'description' => 'Updated description',
                'status' => 'in_progress',
                'team_id' => $team->id,
                'category_id' => $category->id,
                'issue_type_id' => $issueType->id,
                'priority_id' => $priority->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.ticket_code', '101001200001')
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_it_can_claim_unassigned_it_ticket(): void
    {
        $cs = $this->activeUser(User::ROLE_CS);
        $it = $this->activeUser(User::ROLE_IT);
        $ticket = $this->ticketFor($cs, ['status' => 'new', 'holder_id' => null]);

        $response = $this->actingAs($it)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson("/api/it/tickets/{$ticket->id}/claim");

        $response->assertOk()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.holder_id', $it->id);

        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'from_status' => 'new',
            'to_status' => 'in_progress',
            'changed_by' => $it->id,
        ]);
    }

    public function test_reopening_resolved_ticket_clears_completion_timestamps(): void
    {
        $cs = $this->activeUser(User::ROLE_CS);
        $it = $this->activeUser(User::ROLE_IT);
        $ticket = $this->ticketFor($cs, [
            'status' => 'resolved',
            'holder_id' => $it->id,
            'resolved_at' => now()->subHour(),
            'closed_at' => null,
        ]);

        $response = $this->actingAs($it)
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson("/api/it/tickets/{$ticket->id}/status", [
                'status' => 'in_progress',
                'note' => 'Reopened after client replied.',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $ticket->refresh();
        $this->assertNull($ticket->resolved_at);
        $this->assertNull($ticket->closed_at);

        $this->assertTrue(TicketStatusHistory::query()
            ->where('ticket_id', $ticket->id)
            ->where('from_status', 'resolved')
            ->where('to_status', 'in_progress')
            ->exists());
    }
}
