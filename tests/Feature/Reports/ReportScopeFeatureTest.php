<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesTicketFixtures;
use Tests\TestCase;

class ReportScopeFeatureTest extends TestCase
{
    use RefreshDatabase;
    use MakesTicketFixtures;

    public function test_cs_my_scope_only_returns_own_tickets(): void
    {
        $owner = $this->activeUser(User::ROLE_CS);
        $other = $this->activeUser(User::ROLE_CS);

        $ownTicket = $this->ticketFor($owner, ['status' => 'new', 'created_at' => now()]);
        $this->ticketFor($other, ['status' => 'new', 'created_at' => now()]);

        $response = $this->actingAs($owner)
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/reports?scope=my&range=7d');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $codes = collect($response->json('data.rows'))->pluck('ticket_code')->all();
        $this->assertContains('T-' . $ownTicket->ticket_code, $codes);
        $this->assertCount(1, $codes);
    }

    public function test_non_elevated_all_scope_is_downgraded_to_my(): void
    {
        $owner = $this->activeUser(User::ROLE_CS);
        $other = $this->activeUser(User::ROLE_CS);

        $this->ticketFor($owner, ['status' => 'new', 'created_at' => now()]);
        $this->ticketFor($other, ['status' => 'new', 'created_at' => now()]);

        $response = $this->actingAs($owner)
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/reports?scope=all&range=7d');

        $response->assertOk()
            ->assertJsonPath('data.meta.scope', 'my');

        $this->assertCount(1, $response->json('data.rows'));
    }

    public function test_admin_all_scope_can_see_organization_tickets(): void
    {
        $admin = $this->activeUser(User::ROLE_ADMIN);
        $first = $this->activeUser(User::ROLE_CS);
        $second = $this->activeUser(User::ROLE_CS);

        $this->ticketFor($first, ['status' => 'new', 'created_at' => now()]);
        $this->ticketFor($second, ['status' => 'new', 'created_at' => now()]);

        $response = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/reports?scope=all&range=7d');

        $response->assertOk()
            ->assertJsonPath('data.meta.scope', 'all');

        $this->assertGreaterThanOrEqual(2, count($response->json('data.rows')));
    }
}
