<?php

namespace App\Http\Controllers\Api;

use App\Models\ResolverMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class NavigationApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user->role;

        $notificationCount = ResolverMessage::query()
            ->where('to_user_id', $user->id)
            ->where('is_read', false)
            ->count();

        $menus = [
            // =========================
            // Operations
            // =========================
            [
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'href' => route('dashboard'),
                'icon' => 'dashboard',
                'group' => 'operations',
                'show' => true,
            ],
            [
                'key' => 'new-ticket',
                'label' => 'New Ticket',
                'href' => route('tickets.create'),
                'icon' => 'new-tickets',
                'group' => 'operations',
                'show' => in_array($role, ['cs', 'admin', 'supervisor'], true),
            ],
            [
                'key' => 'tickets',
                'label' => 'Tickets',
                'href' => route('tickets.index'),
                'icon' => 'ticket',
                'group' => 'operations',
                'show' => in_array($role, ['cs', 'admin', 'supervisor'], true),
            ],
            [
                'key' => 'resolver-inbox',
                'label' => 'Resolver Inbox',
                'href' => route('resolver-inbox.index'),
                'icon' => 'inbox',
                'group' => 'operations',
                'show' => true,
            ],
            [
                'key' => 'reports',
                'label' => 'Reports',
                'href' => route('reports.index'),
                'icon' => 'reports',
                'group' => 'operations',
                'show' => true,
            ],

            // =========================
            // IT Monitoring
            // =========================
            [
                'key' => 'case-analytics',
                'label' => 'Case Analytics',
                'href' => route('case-analytics.index'),
                'icon' => 'analytics',
                'group' => 'it_monitoring',
                'show' => in_array($role, ['it', 'admin', 'supervisor'], true),
            ],
            [
                'key' => 'my-queue',
                'label' => 'My Queue',
                'href' => route('it.my-queue'),
                'icon' => 'queue',
                'group' => 'it_monitoring',
                'show' => in_array($role, ['it', 'admin', 'supervisor'], true),
            ],
            [
                'key' => 'team-queue',
                'label' => 'Team Queue',
                'href' => route('it.team-queue'),
                'icon' => 'team-queue',
                'group' => 'it_monitoring',
                'show' => in_array($role, ['it', 'admin', 'supervisor'], true),
            ],
            [
                'key' => 'history',
                'label' => 'History',
                'href' => route('it.history'),
                'icon' => 'history',
                'group' => 'it_monitoring',
                'show' => in_array($role, ['it', 'admin', 'supervisor'], true),
            ],

            // =========================
            // System Control
            // =========================
            [
                'key' => 'users',
                'label' => 'Users',
                'href' => Route::has('admin.users.index') ? route('admin.users.index') : '#',
                'icon' => 'users',
                'group' => 'system_control',
                'show' => $role === 'admin' && Route::has('admin.users.index'),
            ],
            [
                'key' => 'master-data',
                'label' => 'Master Data',
                'href' => Route::has('admin.master-data.index') ? route('admin.master-data.index') : '#',
                'icon' => 'database',
                'group' => 'system_control',
                'show' => in_array($role, ['admin', 'supervisor'], true) && Route::has('admin.master-data.index'),
            ],
            [
                'key' => 'audit-logs',
                'label' => 'Audit Logs',
                'href' => Route::has('admin.audit-logs.index') ? route('admin.audit-logs.index') : '#',
                'icon' => 'clipboard',
                'group' => 'system_control',
                'show' => $role === 'admin' && Route::has('admin.audit-logs.index'),
            ],
        ];

        return $this->success([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'role_label' => match ($role) {
                    'cs' => 'Customer Service',
                    'it' => 'IT',
                    'admin' => 'Administrator',
                    'supervisor' => 'Supervisor',
                    default => ucfirst($role),
                },
            ],
            'notification_count' => $notificationCount,
            'menus' => array_values(array_filter($menus, fn ($m) => $m['show'])),
            'profile_url' => route('profile.edit'),
            'logout_url' => route('logout'),
        ], 'Navigation loaded');
    }
}