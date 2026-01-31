<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Support Tickets",
 *     description="API Endpoints for managing support tickets"
 * )
 */
class SupportTicketController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/support-tickets",
     *     summary="Get all support tickets",
     *     tags={"Support Tickets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"open","in_progress","waiting_for_user","closed"})
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="priority",
     *         in="query",
     *         description="Filter by priority",
     *         @OA\Schema(type="string", enum={"low","medium","high","urgent"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of support tickets"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = SupportTicket::with('user');

        // If not admin/support, only show own tickets
        // For now assuming role 'admin' exists for full access
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->get()
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/support-tickets",
     *     summary="Create a new support ticket",
     *     tags={"Support Tickets"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"subject", "category", "priority"},
     *             @OA\Property(property="subject", type="string", example="Issue with payment"),
     *             @OA\Property(property="category", type="string", example="financial"),
     *             @OA\Property(property="priority", type="string", enum={"low","medium","high","urgent"}, example="medium"),
     *             @OA\Property(property="message", type="string", example="I cannot process my payment...", description="Initial message")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ticket created successfully"
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'priority' => 'required|in:low,medium,high,urgent',
            'message' => 'required|string', // Initial message content
        ]);

        $ticket = SupportTicket::create([
            'user_id' => auth()->id(),
            'ticket_number' => 'TKT-' . strtoupper(Str::random(8)),
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'status' => 'open',
        ]);

        // Create initial message
        $ticket->messages()->create([
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'type' => 'user', // Assuming user created it
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تیکت پشتیبانی با موفقیت ایجاد شد',
            'data' => $ticket->load('messages')
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/support-tickets/{ticket}",
     *     summary="Get ticket details",
     *     tags={"Support Tickets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="ticket",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket details"
     *     )
     * )
     */
    public function show(SupportTicket $supportTicket)
    {
        // Add authorization check: user can only see own tickets unless admin
        if (auth()->user()->role !== 'admin' && $supportTicket->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $supportTicket->load(['messages.user', 'user'])
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/support-tickets/{ticket}",
     *     summary="Update ticket status/priority",
     *     tags={"Support Tickets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="ticket",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", enum={"open","in_progress","waiting_for_user","closed"}),
     *             @OA\Property(property="priority", type="string", enum={"low","medium","high","urgent"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket updated successfully"
     *     )
     * )
     */
    public function update(Request $request, SupportTicket $supportTicket)
    {
        // Usually only admin or owner can update.
        // Assuming admin can update everything, user can maybe only close?
        // For simplicity allow update if owner or admin.

        if (auth()->user()->role !== 'admin' && $supportTicket->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:open,in_progress,waiting_for_user,closed',
            'priority' => 'sometimes|in:low,medium,high,urgent',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'closed') {
            $validated['closed_at'] = now();
        }

        $supportTicket->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تیکت با موفقیت بروزرسانی شد',
            'data' => $supportTicket
        ]);
    }
}
