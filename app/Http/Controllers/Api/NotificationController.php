<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->libraryNotifications()
                ->with(['borrowing.book'])
                ->latest()
                ->paginate(20),
        );
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['is_read' => true]);

        return response()->json(['data' => $notification->fresh()]);
    }
}
