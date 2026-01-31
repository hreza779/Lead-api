<?php

namespace App\Http\Controllers;

use App\Models\ExamSet;
use App\Models\ExamSetItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Exam Sets",
 *     description="API Endpoints for managing exam sets"
 * )
 */
class ExamSetController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/exam-sets",
     *     summary="Get all exam sets",
     *     tags={"Exam Sets"},
     *     security={{"sanctum":{}}},
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
     *         @OA\Schema(type="string", enum={"pending","in_progress","completed"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of exam sets"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = ExamSet::with(['manager', 'assessment', 'items.exam']);

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
     *     path="/api/exam-sets",
     *     summary="Create a new exam set",
     *     tags={"Exam Sets"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"manager_id","title","exam_ids"},
     *             @OA\Property(property="manager_id", type="integer", example=1),
     *             @OA\Property(property="assessment_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Onboarding Exams"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="exam_ids", type="array", @OA\Items(type="integer"), example={1,2,3}),
     *             @OA\Property(property="assigned_date", type="string", format="date"),
     *             @OA\Property(property="exam_date", type="string", format="date"),
     *             @OA\Property(property="due_date", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Exam set created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'manager_id' => 'required|exists:managers,id',
            'assessment_id' => 'nullable|exists:assessments,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'exam_ids' => 'required|array',
            'exam_ids.*' => 'exists:exams,id',
            'assigned_date' => 'nullable|date',
            'exam_date' => 'nullable|date',
            'due_date' => 'nullable|date',
        ]);

        // Generate username and password
        $username = 'exam_' . Str::random(8);
        $password = Str::random(12);

        $examSetData = $request->only(['manager_id', 'assessment_id', 'title', 'description', 'assigned_date', 'exam_date', 'due_date']);
        $examSetData['username'] = $username;
        $examSetData['password'] = bcrypt($password);
        $examSetData['status'] = 'pending';

        $examSet = ExamSet::create($examSetData);

        // Create exam set items
        foreach ($validated['exam_ids'] as $index => $examId) {
            ExamSetItem::create([
                'exam_set_id' => $examSet->id,
                'exam_id' => $examId,
                'order' => $index + 1,
                'status' => 'not_started',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'مجموعه آزمون با موفقیت ایجاد شد',
            'data' => [
                'exam_set' => $examSet->load('items.exam'),
                'credentials' => [
                    'username' => $username,
                    'password' => $password, // Only shown once at creation
                ]
            ]
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/exam-sets/{examSet}",
     *     summary="Get exam set details",
     *     tags={"Exam Sets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="examSet",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Exam set details"
     *     )
     * )
     */
    public function show(ExamSet $examSet)
    {
        return response()->json([
            'success' => true,
            'data' => $examSet->load(['manager', 'assessment', 'items.exam.questions', 'results'])
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/exam-sets/{examSet}",
     *     summary="Update exam set",
     *     tags={"Exam Sets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="examSet",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="exam_date", type="string", format="date"),
     *             @OA\Property(property="due_date", type="string", format="date"),
     *             @OA\Property(property="status", type="string", enum={"pending","in_progress","completed"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Exam set updated successfully"
     *     )
     * )
     */
    public function update(Request $request, ExamSet $examSet)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'exam_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'status' => 'nullable|in:pending,in_progress,completed',
        ]);

        $examSet->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'مجموعه آزمون بروزرسانی شد',
            'data' => $examSet->load('items.exam')
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/exam-sets/{examSet}",
     *     summary="Delete exam set",
     *     tags={"Exam Sets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="examSet",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Exam set deleted successfully")
     * )
     */
    public function destroy(ExamSet $examSet)
    {
        $examSet->delete();

        return response()->json([
            'success' => true,
            'message' => 'مجموعه آزمون حذف شد'
        ], 204);
    }

    /**
     * @OA\Post(
     *     path="/api/exam-sets/{examSet}/exams",
     *     summary="Add exams to exam set",
     *     tags={"Exam Sets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="examSet",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"exam_ids"},
     *             @OA\Property(property="exam_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Exams added successfully"
     *     )
     * )
     */
    public function addExams(Request $request, ExamSet $examSet)
    {
        $validated = $request->validate([
            'exam_ids' => 'required|array',
            'exam_ids.*' => 'exists:exams,id',
        ]);

        $currentMaxOrder = $examSet->items()->max('order') ?? 0;

        foreach ($validated['exam_ids'] as $index => $examId) {
            // Check if already exists
            $exists = ExamSetItem::where('exam_set_id', $examSet->id)
                ->where('exam_id', $examId)
                ->exists();

            if (!$exists) {
                ExamSetItem::create([
                    'exam_set_id' => $examSet->id,
                    'exam_id' => $examId,
                    'order' => $currentMaxOrder + $index + 1,
                    'status' => 'not_started',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'آزمون‌ها به مجموعه اضافه شدند',
            'data' => $examSet->load('items.exam')
        ]);
    }
}
