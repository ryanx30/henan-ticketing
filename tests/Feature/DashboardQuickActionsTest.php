<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardQuickActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cs_dashboard_shows_cs_quick_actions(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CS]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeText('+ Create Ticket')
            ->assertSeeText('View My Tickets')
            ->assertDontSeeText('Manage Users');
    }

    public function test_head_cs_dashboard_shows_team_review_quick_actions(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_HEAD_CS]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeText('Review Waiting Info')
            ->assertSeeText('View Team Tickets')
            ->assertDontSeeText('+ Create Ticket');
    }

    public function test_admin_dashboard_shows_administration_quick_actions(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeText('+ Create Ticket')
            ->assertSeeText('Manage Users')
            ->assertSeeText('View Audit Logs');
    }

    public function test_it_dashboard_shows_queue_quick_actions(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_IT]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeText('Open My Queue')
            ->assertSeeText('Open Team Queue')
            ->assertDontSeeText('+ Create Ticket');
    }
}
