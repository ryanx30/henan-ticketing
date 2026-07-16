<?php

namespace Tests\Feature\Navigation;

use App\Models\ResolverMessage;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Navigation\SidebarNavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_badges_only_count_current_actionable_sidebar_items(): void
    {
        $it = User::factory()->create(['role' => User::ROLE_IT]);
        $cs = User::factory()->create(['role' => User::ROLE_CS]);

        $unassigned = $this->ticket($cs, [
            'status' => 'new',
            'holder_id' => null,
        ]);

        $assigned = $this->ticket($cs, [
            'status' => 'in_progress',
            'holder_id' => $it->id,
        ]);

        $closed = $this->ticket($cs, [
            'status' => 'closed',
            'holder_id' => $it->id,
        ]);

        ResolverMessage::query()->create([
            'ticket_id' => $assigned->id,
            'from_user_id' => $cs->id,
            'to_user_id' => $it->id,
            'subject' => 'Additional information',
            'body' => 'The requested information is available.',
            'is_read' => false,
        ]);

        ResolverMessage::query()->create([
            'ticket_id' => $unassigned->id,
            'from_user_id' => $cs->id,
            'to_user_id' => $it->id,
            'subject' => 'Read message',
            'body' => 'This message has already been read.',
            'is_read' => true,
            'read_at' => now(),
        ]);

        ResolverMessage::query()->create([
            'ticket_id' => $closed->id,
            'from_user_id' => $cs->id,
            'to_user_id' => $it->id,
            'subject' => 'Closed ticket message',
            'body' => 'Closed ticket messages are excluded from the badge.',
            'is_read' => false,
        ]);

        $service = app(SidebarNavigationService::class);

        $this->assertSame([
            'resolver_inbox' => 1,
            'my_queue' => 1,
            'team_queue' => 1,
        ], $service->badgeCountsFor($it));
    }

    public function test_sidebar_endpoint_returns_role_aware_badges(): void
    {
        $it = User::factory()->create(['role' => User::ROLE_IT]);
        $cs = User::factory()->create(['role' => User::ROLE_CS]);

        $this->ticket($cs, [
            'status' => 'new',
            'holder_id' => null,
        ]);

        $response = $this->actingAs($it)
            ->getJson('/api/navigation/sidebar-badges');

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.badges.team_queue', 1)
            ->assertJsonPath('data.badges.my_queue', 0);
    }

    private function ticket(User $creator, array $attributes = []): Ticket
    {
        return Ticket::query()->create(array_merge([
            'title' => 'Sidebar badge ticket',
            'description' => 'Ticket used to validate sidebar badge counts.',
            'status' => 'new',
            'priority' => 'medium',
            'team' => 'it',
            'created_by' => $creator->id,
        ], $attributes));
    }
}
