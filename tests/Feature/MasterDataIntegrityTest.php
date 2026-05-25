<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\IssueType;
use App\Models\Priority;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketSequence;
use App\Models\User;
use App\Services\TicketCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_data_validation_rejects_numeric_team_and_priority_system_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $teamResponse = $this
            ->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/admin/master-data/teams', [
                'code_num' => '4',
                'name' => 'Back Office',
                'code' => '4',
                'is_active' => true,
            ]);

        $teamResponse->assertStatus(422);

        $priorityResponse = $this
            ->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/admin/master-data/priorities', [
                'code_num' => '5',
                'name' => 'Urgent',
                'code' => '5',
                'sort_order' => 5,
                'is_active' => true,
            ]);

        $priorityResponse->assertStatus(422);
    }

    public function test_category_code_num_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Category::query()->create([
            'code_num' => '01',
            'name' => 'Account',
            'slug' => 'account',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/admin/master-data/categories', [
                'code_num' => '01',
                'name' => 'Account Duplicate',
                'slug' => 'account-duplicate',
                'is_active' => true,
            ]);

        $response->assertStatus(422);
    }

    public function test_issue_type_code_num_must_be_unique_per_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoryA = Category::query()->create([
            'code_num' => '01',
            'name' => 'Account',
            'slug' => 'account',
            'is_active' => true,
        ]);
        $categoryB = Category::query()->create([
            'code_num' => '02',
            'name' => 'Trading',
            'slug' => 'trading',
            'is_active' => true,
        ]);

        IssueType::query()->create([
            'category_id' => $categoryA->id,
            'code_num' => '001',
            'name' => 'Login Problem',
            'slug' => 'login-problem',
            'is_active' => true,
        ]);

        $duplicateSameCategory = $this
            ->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/admin/master-data/issue-types', [
                'category_id' => $categoryA->id,
                'code_num' => '001',
                'name' => 'Different Name',
                'slug' => 'different-name',
                'is_active' => true,
            ]);

        $duplicateSameCategory->assertStatus(422);

        $sameCodeDifferentCategory = $this
            ->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/admin/master-data/issue-types', [
                'category_id' => $categoryB->id,
                'code_num' => '001',
                'name' => 'Order Problem',
                'slug' => 'order-problem',
                'is_active' => true,
            ]);

        $sameCodeDifferentCategory->assertOk();
    }

    public function test_create_ticket_requires_master_data_ids_and_generates_fixed_numeric_ticket_code(): void
    {
        $user = User::factory()->create(['role' => 'cs']);
        [$team, $category, $issueType, $priority] = $this->masterData();

        $response = $this
            ->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/tickets', [
                'title' => 'Client cannot login',
                'description' => 'Client reported a login issue from mobile app.',
                'team_id' => $team->id,
                'category_id' => $category->id,
                'issue_type_id' => $issueType->id,
                'priority_id' => $priority->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $ticketCode = (string) $response->json('data.ticket_code');

        $this->assertMatchesRegularExpression('/^\d{12}$/', $ticketCode);
        $this->assertStringStartsWith('1010012', $ticketCode);

        $this->assertDatabaseHas('tickets', [
            'ticket_code' => $ticketCode,
            'team_id' => $team->id,
            'category_id' => $category->id,
            'issue_type_id' => $issueType->id,
            'priority_id' => $priority->id,
            'team' => 'it',
            'priority' => 'high',
        ]);
    }

    public function test_edit_ticket_does_not_regenerate_ticket_code(): void
    {
        $user = User::factory()->create(['role' => 'cs']);
        [$team, $category, $issueType, $priority] = $this->masterData();
        $ticket = $this->ticket($user, $team, $category, $issueType, $priority, '101001200001');

        $response = $this
            ->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson("/api/tickets/{$ticket->id}", [
                'title' => 'Client cannot login - updated',
                'description' => 'Updated description.',
                'status' => 'in_progress',
                'team_id' => $team->id,
                'category_id' => $category->id,
                'issue_type_id' => $issueType->id,
                'priority_id' => $priority->id,
            ]);

        $response->assertOk();

        $this->assertSame('101001200001', $ticket->fresh()->ticket_code);
    }

    public function test_ticket_sequence_generates_unique_fixed_numeric_codes(): void
    {
        [$team, $category, $issueType, $priority] = $this->masterData();
        $service = app(TicketCodeService::class);

        $first = $service->generate($team, $category, $issueType, $priority);
        $second = $service->generate($team, $category, $issueType, $priority);

        $this->assertMatchesRegularExpression('/^\d{12}$/', $first);
        $this->assertMatchesRegularExpression('/^\d{12}$/', $second);
        $this->assertNotSame($first, $second);
        $this->assertSame('101001200001', $first);
        $this->assertSame('101001200002', $second);

        $this->assertDatabaseHas('ticket_sequences', [
            'prefix' => '1010012',
            'last_number' => 2,
        ]);
    }

    public function test_master_data_normalize_command_seeds_and_validates_core_mapping(): void
    {
        $this->artisan('master-data:normalize')
            ->assertExitCode(0);

        $this->assertDatabaseHas('teams', ['code' => 'it', 'code_num' => '1']);
        $this->assertDatabaseHas('teams', ['code' => 'finance', 'code_num' => '2']);
        $this->assertDatabaseHas('teams', ['code' => 'compliance', 'code_num' => '3']);

        $this->assertDatabaseHas('priorities', ['code' => 'critical', 'code_num' => '1']);
        $this->assertDatabaseHas('priorities', ['code' => 'high', 'code_num' => '2']);
        $this->assertDatabaseHas('priorities', ['code' => 'medium', 'code_num' => '3']);
        $this->assertDatabaseHas('priorities', ['code' => 'low', 'code_num' => '4']);
    }

    private function masterData(): array
    {
        $team = Team::query()->create([
            'code_num' => '1',
            'name' => 'IT',
            'code' => 'it',
            'is_active' => true,
        ]);

        $priority = Priority::query()->create([
            'code_num' => '2',
            'name' => 'High',
            'code' => 'high',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'code_num' => '01',
            'name' => 'Account',
            'slug' => 'account',
            'is_active' => true,
        ]);

        $issueType = IssueType::query()->create([
            'category_id' => $category->id,
            'code_num' => '001',
            'name' => 'Login Problem',
            'slug' => 'login-problem',
            'is_active' => true,
        ]);

        return [$team, $category, $issueType, $priority];
    }

    private function ticket(User $user, Team $team, Category $category, IssueType $issueType, Priority $priority, string $ticketCode): Ticket
    {
        return Ticket::query()->create([
            'ticket_code' => $ticketCode,
            'title' => 'Original title',
            'description' => 'Original description',
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
        ]);
    }
}
