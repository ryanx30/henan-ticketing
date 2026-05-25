<?php

namespace Tests\Support;

use App\Models\Category;
use App\Models\IssueType;
use App\Models\Priority;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;

trait MakesTicketFixtures
{
    protected function activeUser(string $role, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'is_active' => true,
        ], $attributes));
    }

    protected function masterData(string $teamCode = 'it', string $priorityCode = 'high'): array
    {
        $team = Team::query()->firstOrCreate(
            ['code' => $teamCode],
            ['code_num' => $teamCode === 'it' ? '1' : '2', 'name' => strtoupper($teamCode), 'is_active' => true]
        );

        $priority = Priority::query()->firstOrCreate(
            ['code' => $priorityCode],
            ['code_num' => $priorityCode === 'high' ? '2' : '3', 'name' => ucfirst($priorityCode), 'sort_order' => 2, 'is_active' => true]
        );

        $category = Category::query()->firstOrCreate(
            ['slug' => 'account'],
            ['code_num' => '01', 'name' => 'Account', 'is_active' => true]
        );

        $issueType = IssueType::query()->firstOrCreate(
            ['slug' => 'login-problem'],
            ['category_id' => $category->id, 'code_num' => '001', 'name' => 'Login Problem', 'is_active' => true]
        );

        return [$team, $category, $issueType, $priority];
    }

    protected function ticketFor(User $creator, array $overrides = []): Ticket
    {
        [$team, $category, $issueType, $priority] = $this->masterData(
            $overrides['team'] ?? 'it',
            $overrides['priority'] ?? 'high'
        );

        return Ticket::query()->create(array_merge([
            'ticket_code' => $overrides['ticket_code'] ?? (string) random_int(100000000000, 999999999999),
            'title' => 'Client cannot login',
            'description' => 'Client reported login issue from mobile application.',
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
        ], $overrides));
    }
}
