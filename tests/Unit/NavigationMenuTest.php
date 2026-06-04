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
            'reports',
            'case-analytics',
            'my-queue',
            'team-queue',
            'history',
            'users',
            'master-data',
            'audit-logs',
        ], $keys);
    }

    public function test_supervisor_can_see_everything_except_users_and_audit_logs(): void
    {
        $keys = $this->menuKeysForRole(User::ROLE_SUPERVISOR);

        $this->assertContains('new-ticket', $keys);
        $this->assertContains('my-queue', $keys);
        $this->assertContains('master-data', $keys);
        $this->assertNotContains('users', $keys);
        $this->assertNotContains('audit-logs', $keys);
    }

    public function test_cs_menu_is_limited_to_operational_pages(): void
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
            'reports',
            'case-analytics',
            'my-queue',
            'team-queue',
            'history',
        ], $this->menuKeysForRole(User::ROLE_IT));
    }

    public function test_menu_roles_can_be_reused_by_routes(): void
    {
        $this->assertSame(['admin', 'supervisor', 'cs'], NavigationMenu::rolesFor('new-ticket'));
        $this->assertSame('role:admin,supervisor,cs', NavigationMenu::roleMiddlewareFor('new-ticket'));

        $this->assertSame(['admin', 'supervisor', 'it'], NavigationMenu::rolesFor('my-queue'));
        $this->assertSame('role:admin,supervisor,it', NavigationMenu::roleMiddlewareFor('my-queue'));
    }

    private function menuKeysForRole(string $role): array
    {
        $user = new User([
            'name' => ucfirst($role),
            'email' => $role . '@example.test',
            'role' => $role,
            'is_active' => true,
        ]);

        return array_column(NavigationMenu::flatForUser($user), 'key');
    }
}
