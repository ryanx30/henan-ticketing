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
            $this->menu('dashboard', 'Dashboard', '/dashboard', 'dashboard', 'operations'),
            $this->menu('new-ticket', 'New Ticket', '/tickets/create', 'new-tickets', 'operations', in_array($role, ['cs', 'admin'], true)),
            $this->menu('tickets', 'Tickets', '/tickets', 'ticket', 'operations', in_array($role, ['cs', 'admin', 'supervisor'], true)),
            $this->menu('resolver-inbox', 'Resolver Inbox', '/resolver-inbox', 'inbox', 'operations'),
            $this->menu('reports', 'Reports', '/reports', 'reports', 'operations'),

            $this->menu('case-analytics', 'Case Analytics', '/case-analytics', 'analytics', 'it_monitoring', in_array($role, ['it', 'admin', 'supervisor'], true)),
            $this->menu('my-queue', 'My Queue', '/it/my-queue', 'queue', 'it_monitoring', in_array($role, ['it', 'admin'], true)),
            $this->menu('team-queue', 'Team Queue', '/it/team-queue', 'team-queue', 'it_monitoring', in_array($role, ['it', 'admin', 'supervisor'], true)),
            $this->menu('history', 'History', '/it/history', 'history', 'it_monitoring', in_array($role, ['it', 'admin', 'supervisor'], true)),

            $this->menu('users', 'Users', '/admin/users', 'users', 'system_control', $role === 'admin' && Route::has('admin.users.index')),
            $this->menu('master-data', 'Master Data', '/admin/master-data', 'database', 'system_control', in_array($role, ['admin', 'supervisor'], true) && Route::has('admin.master-data.index')),
            $this->menu('audit-logs', 'Audit Logs', '/admin/audit-logs', 'clipboard', 'system_control', $role === 'admin' && Route::has('admin.audit-logs.index')),
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
            'menus' => array_values(array_filter($menus, fn ($menu) => $menu['show'])),
            'profile_url' => route('profile.edit'),
            'logout_url' => route('logout'),
        ], 'Navigation loaded');
    }

    private function menu(
        string $key,
        string $label,
        string $path,
        string $icon,
        string $group,
        bool $show = true
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'href' => $path,
            'url' => $path,
            'path' => $path,
            'icon' => $icon,
            'group' => $group,
            'show' => $show,
        ];
    }
}
