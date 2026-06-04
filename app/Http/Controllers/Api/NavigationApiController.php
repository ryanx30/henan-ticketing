<?php

namespace App\Http\Controllers\Api;

use App\Services\Notifications\NotificationPayloadService;
use App\Support\NavigationMenu;
use Illuminate\Http\Request;

/**
 * Returns authenticated user navigation data from the centralized navigation menu definition.
 */
class NavigationApiController extends BaseApiController
{
    public function index(Request $request, NotificationPayloadService $notifications)
    {
        $user = $request->user();
        $role = $user->role;

        $notificationPayload = $notifications->payloadFor($user);

        return $this->successResponse([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'role_label' => NavigationMenu::roleLabel($role),
            ],
            'notification_count' => $notificationPayload['count'],
            'notifications' => $notificationPayload,
            'menus' => NavigationMenu::flatForUser($user),
            'menu_groups' => NavigationMenu::groupsForUser($user),
            'profile_url' => route('profile.edit'),
            'logout_url' => route('logout'),
        ], 'Navigation loaded');
    }
}
