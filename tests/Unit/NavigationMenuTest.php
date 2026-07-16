<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\NavigationMenu;
use Tests\TestCase;

class NavigationMenuTest extends TestCase
{
    public function test_admin_can_see_every_navigation_item(): void
    {
        $keys = $this->menuKeysForRole(User::ROLE_ADMIN);

        $this->assertSame([
            'dashboard',
            'new-ticket',
            'tickets',
            'resolver-inbox',
            'my-queue',
            'team-queue',
            'history',
            'reports',
            'case-analytics',
            'users',
            'master-data',
            'audit-logs',
        ], $keys);
    }

    public function test_supervisor_menu_matches_read_and_monitoring_access(): void
    {
        $keys = $this->menuKeysForRole(User::ROLE_SUPERVISOR);

        $this->assertNotContains('new-ticket', $keys);
        $this->assertContains('my-queue', $keys);
        $this->assertContains('master-data', $keys);
        $this->assertNotContains('users', $keys);
        $this->assertNotContains('audit-logs', $keys);
    }

    public function test_cs_menu_is_limited_to_operational_and_reporting_pages(): void
    {
        $this->assertSame([
            'dashboard',
            'new-ticket',
            'tickets',
            'resolver-inbox',
            'reports',
        ], $this->menuKeysForRole(User::ROLE_CS));
    }

    public function test_it_menu_matches_resolver_workflow_pages(): void
    {
        $this->assertSame([
            'dashboard',
            'resolver-inbox',
            'my-queue',
            'team-queue',
            'history',
            'reports',
            'case-analytics',
            'users',
            'master-data',
            'audit-logs',
        ], $this->menuKeysForRole(User::ROLE_IT));
    }

    public function test_menu_groups_follow_the_sidebar_information_architecture(): void
    {
        $groups = NavigationMenu::groupsForUser($this->userForRole(User::ROLE_ADMIN));

        $this->assertSame([
            'operations',
            'work_management',
            'insights',
            'system_control',
        ], array_keys($groups));

        $this->assertSame('Reports & Insights', $groups['insights']['label']);
        $this->assertSame(
            ['reports', 'case-analytics'],
            array_column($groups['insights']['items'], 'key')
        );
    }

    public function test_menu_roles_can_be_reused_by_routes(): void
    {
        $this->assertSame(['admin', 'head_cs', 'cs'], NavigationMenu::rolesFor('new-ticket'));
        $this->assertSame('role:admin,head_cs,cs', NavigationMenu::roleMiddlewareFor('new-ticket'));

        $this->assertSame(['admin', 'supervisor', 'it'], NavigationMenu::rolesFor('my-queue'));
        $this->assertSame('role:admin,supervisor,it', NavigationMenu::roleMiddlewareFor('my-queue'));
    }

    private function menuKeysForRole(string $role): array
    {
        return array_column(NavigationMenu::flatForUser($this->userForRole($role)), 'key');
    }

    private function userForRole(string $role): User
    {
        return new User([
            'name' => ucfirst($role),
            'email' => $role . '@example.test',
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
