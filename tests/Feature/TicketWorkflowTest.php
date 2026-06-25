<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\IssueType;
use App\Models\Priority;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use App\Services\TicketWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_transition_uses_workflow_service_and_writes_history_and_audit_log(): void
    {
        $user = User::factory()->create(['role' => 'it']);
        $ticket = $this->makeTicket(['status' => 'new']);

        $service = app(TicketWorkflowService::class);

        $updated = $service->transition(
            $ticket,
            'in_progress',
            $user,
            'Start investigation.',
            ['action' => 'status_changed']
        );

        $this->assertSame('in_progress', $updated->status);

        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'from_status' => 'new',
            'to_status' => 'in_progress',
            'changed_by' => $user->id,
            'note' => 'Start investigation.',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'status_changed',
            'entity_type' => 'ticket',
            'entity_id' => $ticket->id,
        ]);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'it']);
        $ticket = $this->makeTicket(['status' => 'closed']);

        $response = $this
            ->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson("/api/it/tickets/{$ticket->id}/status", [
                'status' => 'in_progress',
            ]);

        $response->assertStatus(422);
        $this->assertSame('closed', $ticket->fresh()->status);
    }

    public function test_claim_ticket_uses_workflow_service_and_writes_history_and_audit_log(): void
    {
        $user = User::factory()->create(['role' => 'it']);
        $ticket = $this->makeTicket(['status' => 'new', 'holder_id' => null, 'claimed_at' => null]);

        $response = $this
            ->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson("/api/it/tickets/{$ticket->id}/claim");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.holder_id', $user->id);

        $this->assertNotNull($ticket->fresh()->claimed_at);

        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'from_status' => 'new',
            'to_status' => 'in_progress',
            'changed_by' => $user->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'claimed',
            'entity_type' => 'ticket',
            'entity_id' => $ticket->id,
        ]);
    }

    public function test_auto_close_command_uses_workflow_service_and_writes_history_and_audit_log(): void
    {
        $ticket = $this->makeTicket([
            'status' => 'resolved',
            'resolved_at' => now()->subDay()->startOfDay(),
            'updated_at' => now()->subDay()->startOfDay(),
        ]);

        $this->artisan('tickets:auto-close-resolved')
            ->expectsOutput('Auto-closed resolved tickets: 1')
            ->assertExitCode(0);

        $this->assertSame('closed', $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->closed_at);

        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'from_status' => 'resolved',
            'to_status' => 'closed',
            'changed_by' => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => null,
            'actor_name' => 'System',
            'actor_role' => 'system',
            'action' => 'auto_closed',
            'entity_type' => 'ticket',
            'entity_id' => $ticket->id,
        ]);
    }

    public function test_non_canonical_status_alias_is_rejected_by_api_boundary(): void
    {
        $user = User::factory()->create(['role' => 'it']);
        $ticket = $this->makeTicket(['status' => 'new']);

        $response = $this
            ->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson("/api/it/tickets/{$ticket->id}/status", [
                'status' => 'ongoing',
                'note' => 'UI must send canonical status values only.',
            ]);

        $response->assertStatus(422);
        $this->assertSame('new', $ticket->fresh()->status);
    }

    private function makeTicket(array $overrides = []): Ticket
    {
        $user = User::factory()->create(['role' => 'cs']);
        $team = Team::query()->firstOrCreate(
            ['code' => 'it'],
            ['code_num' => '1', 'name' => 'IT', 'is_active' => true]
        );
        $priority = Priority::query()->firstOrCreate(
            ['code' => 'medium'],
            ['code_num' => '3', 'name' => 'Medium', 'sort_order' => 3, 'is_active' => true]
        );
        $category = Category::query()->firstOrCreate(
            ['slug' => 'general'],
            ['code_num' => '01', 'name' => 'General', 'is_active' => true]
        );
        $issueType = IssueType::query()->firstOrCreate(
            ['slug' => 'general-issue'],
            ['category_id' => $category->id, 'code_num' => '001', 'name' => 'General Issue', 'is_active' => true]
        );

        return Ticket::query()->create(array_merge([
            'ticket_code' => '101001300001',
            'title' => 'Test ticket',
            'description' => 'Test description',
            'status' => 'new',
            'priority' => $priority->code,
            'team' => $team->code,
            'category' => $category->name,
            'issue_type' => $issueType->name,
            'team_id' => $team->id,
            'priority_id' => $priority->id,
            'category_id' => $category->id,
            'issue_type_id' => $issueType->id,
            'created_by' => $user->id,
        ], $overrides));
    }
}
