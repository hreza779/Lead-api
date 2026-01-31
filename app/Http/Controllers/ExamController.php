<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Exams",
 *     description="API Endpoints for managing exams"
 * )
 */
class ExamController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/exams",
     *     summary="Get all exams",
     *     tags={"Exams"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"active","draft","archived"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of exams"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Exam::with(['questions', 'creator']);

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
     *     path="/api/exams",
     *     summary="Create a new exam",
     *     tags={"Exams"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","duration","passing_score"},
     *             @OA\Property(property="title", type="string", example="Math Exam"),
     *             @OA\Property(property="description", type="string", example="Basic math questions"),
     *             @OA\Property(property="duration", type="integer", example=60),
     *             @OA\Property(property="passing_score", type="integer", example=70),
     *             @OA\Property(property="status", type="string", enum={"active","draft","archived"}, example="draft"),
     *             @OA\Property(
     *                 property="question_ids",
     *                 type="array",
     *                 description="Array of question IDs to attach",
     *                 @OA\Items(type="integer"),
     *                 example={1,2,3}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Exam created successfully"
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'required|integer|min:1',
            'passing_score' => 'required|integer|min:0|max:100',
            'status' => 'nullable|in:active,draft,archived',
            'question_ids' => 'nullable|array',
            'question_ids.*' => 'exists:questions,id',
        ]);

        $examData = $request->only(['title', 'description', 'duration', 'passing_score', 'status']);
        $examData['created_by'] = auth()->id();
        $examData['status'] = $examData['status'] ?? 'draft';

        $exam = Exam::create($examData);

        // Attach questions if provided
        if ($request->has('question_ids') && is_array($request->question_ids)) {
            $questionsWithOrder = [];
            foreach ($request->question_ids as $index => $questionId) {
                $questionsWithOrder[$questionId] = ['order' => $index + 1];
            }
            $exam->questions()->attach($questionsWithOrder);
        }

        return response()->json([
            'success' => true,
            'message' => 'آزمون با موفقیت ایجاد شد',
            'data' => $exam->load('questions')
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/exams/{exam}",
     *     summary="Get a specific exam",
     *     tags={"Exams"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="exam",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Exam details"
     *     ),
     *     @OA\Response(response=404, description="Exam not found")
     * )
     */
    public function show(Exam $exam)
    {
        return response()->json([
            'success' => true,
            'data' => $exam->load(['questions', 'creator'])
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/exams/{exam}",
     *     summary="Update an exam",
     *     tags={"Exams"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="exam",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="duration", type="integer"),
     *             @OA\Property(property="passing_score", type="integer"),
     *             @OA\Property(property="status", type="string", enum={"active","draft","archived"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Exam updated successfully"
     *     ),
     *     @OA\Response(response=404, description="Exam not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'sometimes|required|integer|min:1',
            'passing_score' => 'sometimes|required|integer|min:0|max:100',
            'status' => 'nullable|in:active,draft,archived',
        ]);

        $exam->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'آزمون با موفقیت بروزرسانی شد',
            'data' => $exam->load('questions')
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/exams/{exam}",
     *     summary="Delete an exam",
     *     tags={"Exams"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="exam",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Exam deleted successfully"),
     *     @OA\Response(response=404, description="Exam not found")
     * )
     */
    public function destroy(Exam $exam)
    {
        $exam->delete();

        return response()->json([
            'success' => true,
            'message' => 'آزمون با موفقیت حذف شد'
        ], 204);
    }

    /**
     * @OA\Post(
     *     path="/api/exams/{exam}/questions",
     *     summary="Attach questions to exam",
     *     tags={"Exams"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="exam",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"questions"},
     *             @OA\Property(
     *                 property="questions",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="question_id", type="integer"),
     *                     @OA\Property(property="order", type="integer")
     *                 ),
     *                 example={{"question_id":1,"order":1},{"question_id":2,"order":2}}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Questions attached successfully"
     *     )
     * )
     */
    public function attachQuestions(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'questions' => 'required|array',
            'questions.*.question_id' => 'required|exists:questions,id',
            'questions.*.order' => 'required|integer|min:0',
        ]);

        $questionsToAttach = [];
        foreach ($validated['questions'] as $question) {
            $questionsToAttach[$question['question_id']] = ['order' => $question['order']];
        }

        $exam->questions()->syncWithoutDetaching($questionsToAttach);

        return response()->json([
            'success' => true,
            'message' => 'سوالات با موفقیت به آزمون اضافه شدند',
            'data' => $exam->load('questions')
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/exams/{exam}/questions/{question}",
     *     summary="Detach question from exam",
     *     tags={"Exams"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="exam",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="question",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Question detached successfully"
     *     )
     * )
     */
    public function detachQuestion(Exam $exam, Question $question)
    {
        $exam->questions()->detach($question->id);

        return response()->json([
            'success' => true,
            'message' => 'سوال از آزمون حذف شد',
            'data' => $exam->load('questions')
        ]);
    }
}
