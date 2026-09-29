<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErpNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = ErpNotification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->type,
                    'title' => $n->title,
                    'message' => $n->message,
                    'link' => $n->link,
                    'icon' => $n->icon ?: 'Bell',
                    'unread' => is_null($n->read_at),
                    'time' => $n->created_at->diffForHumans(null, true, true) . ' ago',
                    'createdAt' => $n->created_at->toIso8601String(),
                ];
            });

        $unreadCount = ErpNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'notifications' => $notifications,
                'unreadCount' => $unreadCount,
            ],
        ]);
    }

    public function markRead(int $id, Request $request): JsonResponse
    {
        $n = ErpNotification::where('user_id', $request->user()->id)->findOrFail($id);
        $n->update(['read_at' => now()]);

        return response()->json([
            'status' => 'success',
            'data' => ['success' => true],
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        ErpNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'status' => 'success',
            'data' => ['success' => true],
        ]);
    }
}
