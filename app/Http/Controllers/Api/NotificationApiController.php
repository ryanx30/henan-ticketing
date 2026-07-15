<?php

namespace App\Http\Controllers\Api;

use App\Services\Notifications\NotificationPayloadService;
use Illuminate\Http\Request;

/**
 * Provides notification polling and per-user read/dismiss actions.
 */
class NotificationApiController extends BaseApiController
{
    public function index(Request $request, NotificationPayloadService $notifications)
    {
        $limit = max(1, min((int) $request->query('limit', 7), 20));

        return $this->successResponse(
            $notifications->payloadFor($request->user(), $limit),
            'Notifications loaded.'
        );
    }

    public function markAllAsRead(Request $request, NotificationPayloadService $notifications)
    {
        return $this->successResponse(
            $notifications->markAllAsRead($request->user()),
            'Notifications marked as read.'
        );
    }

    public function markAsRead(Request $request, NotificationPayloadService $notifications)
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:191'],
        ]);

        return $this->successResponse(
            $notifications->markAsRead($request->user(), $validated['key']),
            'Notification marked as read.'
        );
    }

    public function dismiss(Request $request, NotificationPayloadService $notifications)
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:191'],
        ]);

        return $this->successResponse(
            $notifications->dismiss($request->user(), $validated['key']),
            'Notification dismissed.'
        );
    }
}
