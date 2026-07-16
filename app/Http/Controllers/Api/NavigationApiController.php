<?php

namespace App\Http\Controllers\Api;

use App\Services\Navigation\SidebarNavigationService;
use App\Services\Notifications\NotificationPayloadService;
use App\Support\NavigationMenu;
use Illuminate\Http\Request;

/**
 * Returns authenticated user navigation data from the centralized navigation menu definition.
 */
class NavigationApiController extends BaseApiController
{
    public function index(
        Request $request,
        NotificationPayloadService $notifications,
        SidebarNavigationService $sidebarNavigation
    ) {
        $user = $request->user();
        $role = $user->role;
        $notificationPayload = $notifications->payloadFor($user);
        $sidebarBadges = $sidebarNavigation->badgeCountsFor($user);

        return $this->successResponse([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'role_label' => NavigationMenu::roleLabel($role),
            ],
            'notification_count' => $notificationPayload['count'],
            'notifications' => $notificationPayload,
            'sidebar_badges' => $sidebarBadges,
            'menus' => $sidebarNavigation->flatForUser($user, $sidebarBadges),
            'menu_groups' => $sidebarNavigation->groupsForUser($user, $sidebarBadges),
            'profile_url' => route('profile.edit'),
            'logout_url' => route('logout'),
        ], 'Navigation loaded');
    }

    public function sidebarBadges(Request $request, SidebarNavigationService $sidebarNavigation)
    {
        return $this->successResponse([
            'badges' => $sidebarNavigation->badgeCountsFor($request->user()),
        ], 'Sidebar badges loaded');
    }
}
