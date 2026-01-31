<?php

namespace App\Http\Controllers;

use App\Models\ExamManager;
use App\Models\Exam;
use App\Models\Manager;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Exam Assignments",
 *     description="API Endpoints for managing exam assignments to managers"
 * )
 */
class ExamManagerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/exam-assignments",
     *     summary="Get all exam assignments",
     *     tags={"Exam Assignments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="exam_id",
     *         in="query",
     *         description="Filter by exam ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="manager_id",
     *         in="query",
     *         description="Filter by manager ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"assigned","started","completed","expired"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of exam assignments"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = ExamManager::with(['exam', 'manager']);

        if ($request->has('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }

        if ($request->has('manager_id')) {
            $query->where('manager_id', $request->manager_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/exam-assignments",
     *     summary="Assign exam to manager(s)",
     *     tags={"Exam Assignments"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"exam_id","manager_ids"},
     *             @OA\Property(property="exam_id", type="integer", example=1),
     *             @OA\Property(property="manager_ids", type="array", @OA\Items(type="integer"), example={1,2,3}),
     *             @OA\Property(property="due_date", type="string", format="date", example="2026-02-01"),
     *             @OA\Property(property="max_attempts", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Exam assigned successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'manager_ids' => 'required|array',
            'manager_ids.*' => 'exists:managers,id',
            'due_date' => 'nullable|date|after:today',
            'max_attempts' => 'nullable|integer|min:1',
        ]);

        $assignments = [];
        foreach ($validated['manager_ids'] as $managerId) {
            // Check if already assigned
            $existing = ExamManager::where('exam_id', $validated['exam_id'])
                ->where('manager_id', $managerId)
                ->first();

            if ($existing) {
                continue; // Skip if already assigned
            }

            $assignment = ExamManager::create([
                'exam_id' => $validated['exam_id'],
                'manager_id' => $managerId,
                'assigned_date' => now()->toDateString(),
                'due_date' => $validated['due_date'] ?? null,
                'status' => 'assigned',
                'attempts' => 0,
                'max_attempts' => $validated['max_attempts'] ?? 1,
            ]);

            $assignments[] = $assignment;
        }

        return response()->json([
            'success' => true,
            'message' => 'آزمون با موفقیت به متقاضیان تخصیص داده شد',
            'data' => $assignments
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/exam-assignments/{assignment}",
     *     summary="Get exam assignment details",
     *     tags={"Exam Assignments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="assignment",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assignment details"
     *     )
     * )
     */
    public function show(ExamManager $assignment)
    {
        return response()->json([
            'success' => true,
            'data' => $assignment->load(['exam.questions', 'manager'])
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/exam-assignments/{assignment}",
     *     summary="Update exam assignment",
     *     tags={"Exam Assignments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="assignment",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="due_date", type="string", format="date"),
     *             @OA\Property(property="max_attempts", type="integer"),
     *             @OA\Property(property="status", type="string", enum={"assigned","started","completed","expired"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assignment updated successfully"
     *     )
     * )
     */
    public function update(Request $request, ExamManager $assignment)
    {
        $validated = $request->validate([
            'due_date' => 'nullable|date',
            'max_attempts' => 'nullable|integer|min:1',
            'status' => 'nullable|in:assigned,started,completed,expired',
        ]);

        $assignment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تخصیص آزمون بروزرسانی شد',
            'data' => $assignment
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/exam-assignments/{assignment}",
     *     summary="Delete exam assignment",
     *     tags={"Exam Assignments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="assignment",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Assignment deleted successfully")
     * )
     */
    public function destroy(ExamManager $assignment)
    {
        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'تخصیص آزمون حذف شد'
        ], 204);
    }

    /**
     * @OA\Post(
     *     path="/api/exam-assignments/{assignment}/start",
     *     summary="Mark exam as started",
     *     tags={"Exam Assignments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="assignment",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Exam started"
     *     )
     * )
     */
    public function start(ExamManager $assignment)
    {
        if ($assignment->status !== 'assigned') {
            return response()->json([
                'success' => false,
                'message' => 'این آزمون قبلاً شروع شده است'
            ], 409);
        }

        if ($assignment->attempts >= $assignment->max_attempts) {
            return response()->json([
                'success' => false,
                'message' => 'تعداد دفعات مجاز شرکت در آزمون به پایان رسیده است'
            ], 403);
        }

        $assignment->update([
            'status' => 'started',
            'attempts' => $assignment->attempts + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'آزمون شروع شد',
            'data' => $assignment
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/exam-assignments/{assignment}/complete",
     *     summary="Mark exam as completed",
     *     tags={"Exam Assignments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="assignment",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Exam completed"
     *     )
     * )
     */
    public function complete(ExamManager $assignment)
    {
        if ($assignment->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'این آزمون قبلاً تکمیل شده است'
            ], 409);
        }

        $assignment->update([
            'status' => 'completed',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'آزمون تکمیل شد',
            'data' => $assignment
        ]);
    }
}
