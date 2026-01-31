<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Notifications",
 *     description="API Endpoints for managing notifications"
 * )
 */
class NotificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     summary="Get user's notifications",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="is_read",
     *         in="query",
     *         description="Filter by read status (1 or 0)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of notifications"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Notification::where('user_id', auth()->id());

        if ($request->has('is_read')) {
            $query->where('is_read', $request->is_read);
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->get()
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/notifications",
     *     summary="Send a manual notification (Admin only)",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "title", "message"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Welcome!"),
     *             @OA\Property(property="message", type="string", example="Welcome to our platform."),
     *             @OA\Property(property="type", type="string", example="system"),
     *             @OA\Property(property="action_url", type="string", example="/dashboard")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Notification sent successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        // Simple admin check (assuming role based)
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|string|max:50',
            'action_url' => 'nullable|url|max:255',
        ]);

        $notification = Notification::create([
            'user_id' => $validated['user_id'],
            'title' => $validated['title'],
            'message' => $validated['message'],
            'type' => $validated['type'] ?? 'system',
            'is_read' => false,
            'action_url' => $validated['action_url'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'اعلان با موفقیت ارسال شد',
            'data' => $notification
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/notifications/{notification}/read",
     *     summary="Mark notification as read",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="notification",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification marked as read"
     *     )
     * )
     */
    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'اعلان به عنوان خوانده شده علامت‌گذاری شد',
            'data' => $notification
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/notifications/mark-all-read",
     *     summary="Mark all notifications as read",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="All notifications marked as read"
     *     )
     * )
     */
    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'تمام اعلان‌ها به عنوان خوانده شده علامت‌گذاری شدند'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/notifications/{notification}",
     *     summary="Delete notification",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="notification",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Notification deleted successfully")
     * )
     */
    public function destroy(Notification $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'اعلان حذف شد'
        ], 204);
    }
}
