<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\IssueType;
use App\Models\Priority;
use App\Models\ResolverMessage;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cs_dashboard_shows_all_active_tickets_and_separates_my_tickets(): void
    {
        $cs = User::factory()->create(['role' => User::ROLE_CS]);
        $otherCs = User::factory()->create(['role' => User::ROLE_CS]);

        $ownTicket = $this->makeTicket(['created_by' => $cs->id, 'title' => 'Own CS ticket']);
        $otherTicket = $this->makeTicket(['created_by' => $otherCs->id, 'title' => 'Other CS active ticket']);

        $response = $this
            ->actingAs($cs)
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/dashboard');

        $response->assertOk()->assertJsonPath('success', true);

        $activeTickets = collect($response->json('data.active_tickets'));
        $myTickets = collect($response->json('data.my_tickets'));

        $this->assertTrue($activeTickets->contains('id', $ownTicket->id));
        $this->assertTrue($activeTickets->contains('id', $otherTicket->id));
        $this->assertTrue($myTickets->contains('id', $ownTicket->id));
        $this->assertFalse($myTickets->contains('id', $otherTicket->id));
    }


    public function test_cs_can_list_and_view_tickets_created_by_other_users(): void
    {
        $cs = User::factory()->create(['role' => User::ROLE_CS]);
        $otherCs = User::factory()->create(['role' => User::ROLE_CS]);

        $otherTicket = $this->makeTicket([
            'created_by' => $otherCs->id,
            'title' => 'Ticket visible to other CS monitors',
            'status' => 'closed',
        ]);

        $listResponse = $this
            ->actingAs($cs)
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/tickets?status=closed');

        $listResponse->assertOk()->assertJsonPath('success', true);

        $tickets = collect($listResponse->json('data'));
        $this->assertTrue($tickets->contains('id', $otherTicket->id));

        $detailResponse = $this
            ->actingAs($cs)
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/tickets/' . $otherTicket->id);

        $detailResponse->assertOk()->assertJsonPath('success', true);
    }

    public function test_resolver_inbox_preview_is_scoped_to_message_participants(): void
    {
        $cs = User::factory()->create(['role' => User::ROLE_CS]);
        $otherCs = User::factory()->create(['role' => User::ROLE_CS]);
        $it = User::factory()->create(['role' => User::ROLE_IT]);

        $visibleTicket = $this->makeTicket(['created_by' => $cs->id]);
        $hiddenTicket = $this->makeTicket(['created_by' => $otherCs->id]);

        $visibleMessage = ResolverMessage::query()->create([
            'ticket_id' => $visibleTicket->id,
            'from_user_id' => $it->id,
            'to_user_id' => $cs->id,
            'subject' => 'Visible resolver update',
            'body' => 'This message belongs to the logged-in CS user.',
        ]);

        ResolverMessage::query()->create([
            'ticket_id' => $hiddenTicket->id,
            'from_user_id' => $it->id,
            'to_user_id' => $otherCs->id,
            'subject' => 'Hidden resolver update',
            'body' => 'This message belongs to another user.',
        ]);

        $response = $this
            ->actingAs($cs)
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/dashboard');

        $response->assertOk()->assertJsonPath('success', true);

        $messages = collect($response->json('data.resolver_inbox'));

        $this->assertTrue($messages->contains('id', $visibleMessage->id));
        $this->assertFalse($messages->contains('subject', 'Hidden resolver update'));
    }

    private function makeTicket(array $overrides = []): Ticket
    {
        $creator = User::factory()->create(['role' => User::ROLE_CS]);
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
            'ticket_code' => '1010013' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'title' => 'Dashboard ticket',
            'description' => 'Dashboard data isolation test ticket.',
            'status' => 'new',
            'priority' => $priority->code,
            'team' => $team->code,
            'category' => $category->name,
            'issue_type' => $issueType->name,
            'team_id' => $team->id,
            'priority_id' => $priority->id,
            'category_id' => $category->id,
            'issue_type_id' => $issueType->id,
            'created_by' => $creator->id,
            'sla_deadline_at' => now()->addHours(4),
        ], $overrides));
    }
}
