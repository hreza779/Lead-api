<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Support Messages",
 *     description="API Endpoints for managing support ticket messages"
 * )
 */
class SupportMessageController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/support-tickets/{ticket}/messages",
     *     summary="Get messages for a ticket",
     *     tags={"Support Messages"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="ticket",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of messages"
     *     )
     * )
     */
    public function index(SupportTicket $ticket)
    {
        if (auth()->user()->role !== 'admin' && $ticket->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $ticket->messages()->with('user')->get()
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/support-tickets/{ticket}/messages",
     *     summary="Add a message (reply) to a ticket",
     *     tags={"Support Messages"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="ticket",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"message"},
     *             @OA\Property(property="message", type="string", example="Here is more info..."),
     *             @OA\Property(property="attachments", type="array", @OA\Items(type="string"), description="List of file paths/URLs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Message added successfully"
     *     )
     * )
     */
    public function store(Request $request, SupportTicket $ticket)
    {
        if (auth()->user()->role !== 'admin' && $ticket->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'message' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'string', // Assuming strings/urls for now
        ]);

        $user = auth()->user();
        $type = $user->role === 'admin' ? 'admin' : 'user';

        $message = $ticket->messages()->create([
            'user_id' => $user->id,
            'message' => $validated['message'],
            'type' => $type,
            'attachments' => $validated['attachments'] ?? [],
            'is_read' => false,
        ]);

        // Update ticket status based on who replied
        if ($type === 'admin') {
            $ticket->update(['status' => 'waiting_for_user']);
        } else {
            $ticket->update(['status' => 'in_progress']); // or open
        }

        return response()->json([
            'success' => true,
            'message' => 'پیام شما با موفقیت ثبت شد',
            'data' => $message->load('user')
        ], 201);
    }
}
