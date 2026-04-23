<?php

namespace App\Http\Controllers\Api;

use App\Models\ResolverMessage;
use Illuminate\Http\Request;

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
            [
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'href' => route('dashboard'),
                'active' => request()->routeIs('dashboard'),
                'icon' => 'dashboard',
                'section' => 'top',
                'show' => true,
            ],
            [
                'key' => 'new-ticket',
                'label' => 'New Ticket',
                'href' => route('tickets.create'),
                'active' => request()->routeIs('tickets.create'),
                'icon' => 'new-tickets',
                'section' => 'top',
                'show' => in_array($role, ['cs', 'admin'], true),
            ],
            [
                'key' => 'case-analytics',
                'label' => 'Case Analytics',
                'href' => route('case-analytics.index'),
                'active' => request()->routeIs('case-analytics.index'),
                'icon' => 'analytics',
                'section' => 'top',
                'show' => in_array($role, ['it', 'admin'], true),
            ],
            [
                'key' => 'my-queue',
                'label' => 'My Queue',
                'href' => route('it.my-queue'),
                'active' => request()->routeIs('it.my-queue'),
                'icon' => 'queue',
                'section' => 'top',
                'show' => in_array($role, ['it', 'admin'], true),
            ],
            [
                'key' => 'team-queue',
                'label' => 'Team Queue',
                'href' => route('it.team-queue'),
                'active' => request()->routeIs('it.team-queue'),
                'icon' => 'team-queue',
                'section' => 'top',
                'show' => in_array($role, ['it', 'admin'], true),
            ],
            [
                'key' => 'history',
                'label' => 'History',
                'href' => route('it.history'),
                'active' => request()->routeIs('it.history'),
                'icon' => 'history',
                'section' => 'top',
                'show' => in_array($role, ['it', 'admin'], true),
            ],
            [
                'key' => 'tickets',
                'label' => 'Tickets',
                'href' => route('tickets.index'),
                'active' => request()->routeIs('tickets.index'),
                'icon' => 'ticket',
                'section' => 'bottom',
                'show' => in_array($role, ['cs', 'admin'], true),
            ],
            [
                'key' => 'resolver-inbox',
                'label' => 'Resolver Inbox',
                'href' => route('resolver-inbox.index'),
                'active' => request()->routeIs('resolver-inbox.index') || request()->routeIs('resolver-inbox.show'),
                'icon' => 'inbox',
                'section' => 'bottom',
                'show' => true,
            ],
            [
                'key' => 'reports',
                'label' => 'Reports',
                'href' => route('reports.index'),
                'active' => request()->routeIs('reports.index'),
                'icon' => 'reports',
                'section' => 'bottom',
                'show' => true,
            ],
        ];

        return $this->success([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'role_label' => $role === 'cs' ? 'Customer Service' : ucfirst($role),
            ],
            'notification_count' => $notificationCount,
            'menus' => array_values(array_filter($menus, fn ($m) => $m['show'])),
            'profile_url' => route('profile.edit'),
            'logout_url' => route('logout'),
        ], 'Navigation loaded');
    }
}