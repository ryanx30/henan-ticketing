<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Defines menu visibility, role access, active states, and navigation payloads.
 */
class NavigationMenu
{
    private const GROUPS = [
        'operations' => 'Operations',
        'it_monitoring' => 'IT Monitoring',
        'system_control' => 'System Control',
    ];

    // ========= MENU DEFINITIONS =========

    private const ITEMS = [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'path' => '/dashboard',
            'route' => 'dashboard',
            'icon' => 'dashboard',
            'group' => 'operations',
            'roles' => ['admin', 'supervisor', 'head_cs', 'cs', 'it'],
            'active_patterns' => ['dashboard'],
        ],
        [
            'key' => 'new-ticket',
            'label' => 'New Ticket',
            'path' => '/tickets/create',
            'route' => 'tickets.create',
            'icon' => 'new-tickets',
            'group' => 'operations',
            'roles' => ['admin', 'head_cs', 'cs'],
            'active_patterns' => ['tickets/create'],
        ],
        [
            'key' => 'tickets',
            'label' => 'Tickets',
            'path' => '/tickets',
            'route' => 'tickets.index',
            'icon' => 'ticket',
            'group' => 'operations',
            'roles' => ['admin', 'supervisor', 'head_cs', 'cs'],
            'active_patterns' => ['tickets', 'tickets/*'],
            'inactive_patterns' => ['tickets/create'],
        ],
        [
            'key' => 'resolver-inbox',
            'label' => 'Resolver Inbox',
            'path' => '/resolver-inbox',
            'route' => 'resolver-inbox.index',
            'icon' => 'inbox',
            'group' => 'operations',
            'roles' => ['admin', 'supervisor', 'head_cs', 'cs', 'it'],
            'active_patterns' => ['resolver-inbox', 'resolver-inbox/*'],
        ],
        [
            'key' => 'reports',
            'label' => 'Reports',
            'path' => '/reports',
            'route' => 'reports.index',
            'icon' => 'reports',
            'group' => 'operations',
            'roles' => ['admin', 'supervisor', 'head_cs', 'cs', 'it'],
            'active_patterns' => ['reports', 'reports/*'],
        ],
        [
            'key' => 'case-analytics',
            'label' => 'Case Analytics',
            'path' => '/case-analytics',
            'route' => 'case-analytics.index',
            'icon' => 'analytics',
            'group' => 'it_monitoring',
            'roles' => ['admin', 'supervisor', 'it'],
            'active_patterns' => ['case-analytics', 'case-analytics/*'],
        ],
        [
            'key' => 'my-queue',
            'label' => 'My Queue',
            'path' => '/it/my-queue',
            'route' => 'it.my-queue',
            'icon' => 'queue',
            'group' => 'it_monitoring',
            'roles' => ['admin', 'supervisor', 'it'],
            'active_patterns' => ['it/my-queue', 'it/my-queue/*'],
        ],
        [
            'key' => 'team-queue',
            'label' => 'Team Queue',
            'path' => '/it/team-queue',
            'route' => 'it.team-queue',
            'icon' => 'team-queue',
            'group' => 'it_monitoring',
            'roles' => ['admin', 'supervisor', 'it'],
            'active_patterns' => ['it/team-queue', 'it/team-queue/*'],
        ],
        [
            'key' => 'history',
            'label' => 'History',
            'path' => '/it/history',
            'route' => 'it.history',
            'icon' => 'history',
            'group' => 'it_monitoring',
            'roles' => ['admin', 'supervisor', 'it'],
            'active_patterns' => ['it/history', 'it/history/*'],
        ],
        [
            'key' => 'users',
            'label' => 'Users',
            'path' => '/admin/users',
            'route' => 'admin.users.index',
            'icon' => 'users',
            'group' => 'system_control',
            'roles' => ['admin', 'it'],
            'active_patterns' => ['admin/users', 'admin/users/*'],
        ],
        [
            'key' => 'master-data',
            'label' => 'Master Data',
            'path' => '/admin/master-data',
            'route' => 'admin.master-data.index',
            'icon' => 'database',
            'group' => 'system_control',
            'roles' => ['admin', 'supervisor', 'head_cs', 'it'],
            'active_patterns' => ['admin/master-data', 'admin/master-data/*'],
        ],
        [
            'key' => 'audit-logs',
            'label' => 'Audit Logs',
            'path' => '/admin/audit-logs',
            'route' => 'admin.audit-logs.index',
            'icon' => 'clipboard',
            'group' => 'system_control',
            'roles' => ['admin', 'it'],
            'active_patterns' => ['admin/audit-logs', 'admin/audit-logs/*'],
        ],
    ];

    public static function groupsForUser(?Authenticatable $user): array
    {
        $items = self::flatForUser($user);
        $groups = [];

        foreach (self::GROUPS as $key => $label) {
            $groupItems = array_values(array_filter(
                $items,
                fn (array $item): bool => $item['group'] === $key
            ));

            if ($groupItems === []) {
                continue;
            }

            $groups[$key] = [
                'key' => $key,
                'label' => $label,
                'items' => $groupItems,
            ];
        }

        return $groups;
    }

    // ========= MENU BUILDERS =========

    public static function flatForUser(?Authenticatable $user): array
    {
        return array_values(array_map(
            fn (array $item): array => self::normalizeItem($item),
            array_values(array_filter(
                self::ITEMS,
                fn (array $item): bool => self::canSee($user, $item)
            ))
        ));
    }


    public static function rolesFor(string $key, array $fallback = []): array
    {
        foreach (self::ITEMS as $item) {
            if (($item['key'] ?? null) === $key) {
                return $item['roles'] ?? [];
            }
        }

        return $fallback;
    }

    public static function roleMiddlewareFor(string $key, array $fallback = []): string
    {
        $roles = self::rolesFor($key, $fallback);

        return 'role:' . implode(',', $roles);
    }

    public static function canUserAccess(?Authenticatable $user, string $key): bool
    {
        foreach (self::ITEMS as $item) {
            if (($item['key'] ?? null) === $key) {
                return self::canSee($user, $item);
            }
        }

        return false;
    }

    public static function roleLabel(?string $role): string
    {
        return match ($role) {
            'cs' => 'Customer Service',
            'head_cs' => 'Head CS',
            'it' => 'IT',
            'admin' => 'Administrator',
            'supervisor' => 'Supervisor',
            default => ucfirst((string) $role),
        };
    }

    private static function canSee(?Authenticatable $user, array $item): bool
    {
        if (! $user) {
            return false;
        }

        $role = (string) ($user->role ?? '');

        $roles = $item['roles'] ?? [];

        if (! in_array($role, $roles, true)) {
            return false;
        }

        $routeName = $item['route'] ?? null;

        return ! $routeName || Route::has($routeName);
    }

    private static function normalizeItem(array $item): array
    {
        $href = self::href($item);

        return [
            'key' => $item['key'],
            'label' => $item['label'],
            'href' => $href,
            'url' => $href,
            'path' => $href,
            'route' => $item['route'] ?? null,
            'icon' => $item['icon'],
            'group' => $item['group'],
            'group_label' => self::GROUPS[$item['group']] ?? Str::of($item['group'])->headline()->toString(),
            'active' => self::isActive($item),
            'active_patterns' => $item['active_patterns'] ?? [],
            'inactive_patterns' => $item['inactive_patterns'] ?? [],
            'show' => true,
        ];
    }

    private static function href(array $item): string
    {
        $routeName = $item['route'] ?? null;

        if ($routeName && Route::has($routeName)) {
            return route($routeName, absolute: false);
        }

        return $item['path'];
    }

    private static function isActive(array $item): bool
    {
        foreach ($item['inactive_patterns'] ?? [] as $pattern) {
            if (request()->is($pattern)) {
                return false;
            }
        }

        foreach ($item['active_patterns'] ?? [] as $pattern) {
            if (request()->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
