<?php

namespace App\Http\Controllers;

use App\Models\AssessmentQuestion;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Assessment Questions",
 *     description="API Endpoints for managing assessment questions"
 * )
 */
class AssessmentQuestionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/assessment-questions",
     *     summary="Get all assessment questions",
     *     tags={"Assessment Questions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="step_id",
     *         in="query",
     *         description="Filter by step ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of assessment questions"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = AssessmentQuestion::with('step');

        if ($request->has('step_id')) {
            $query->where('step_id', $request->step_id);
        }

        $questions = $query->orderBy('order')->get();

        return response()->json([
            'success' => true,
            'data' => $questions
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/assessment-questions",
     *     summary="Create a new assessment question",
     *     tags={"Assessment Questions"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"step_id","question","type","order"},
     *             @OA\Property(property="step_id", type="integer", example=1),
     *             @OA\Property(property="question", type="string", example="What is your preferred work style?"),
     *             @OA\Property(property="type", type="string", enum={"text","select","radio","checkbox","rating"}, example="select"),
     *             @OA\Property(property="options", type="array", @OA\Items(type="string"), example={"Remote","Hybrid","Office"}),
     *             @OA\Property(property="required", type="boolean", example=true),
     *             @OA\Property(property="order", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Question created successfully"
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'step_id' => 'required|exists:assessment_steps,id',
            'question' => 'required|string',
            'type' => 'required|in:text,select,radio,checkbox,rating',
            'options' => 'nullable|array',
            'options.*' => 'string',
            'required' => 'nullable|boolean',
            'order' => 'required|integer|min:0',
        ]);

        $question = AssessmentQuestion::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'سوال نیازسنجی با موفقیت ایجاد شد',
            'data' => $question
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/assessment-questions/{question}",
     *     summary="Get a specific assessment question",
     *     tags={"Assessment Questions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="question",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Question details"
     *     ),
     *     @OA\Response(response=404, description="Question not found")
     * )
     */
    public function show(AssessmentQuestion $question)
    {
        return response()->json([
            'success' => true,
            'data' => $question->load('step')
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/assessment-questions/{question}",
     *     summary="Update an assessment question",
     *     tags={"Assessment Questions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="question",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="question", type="string"),
     *             @OA\Property(property="type", type="string", enum={"text","select","radio","checkbox","rating"}),
     *             @OA\Property(property="options", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="required", type="boolean"),
     *             @OA\Property(property="order", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Question updated successfully"
     *     ),
     *     @OA\Response(response=404, description="Question not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, AssessmentQuestion $question)
    {
        $validated = $request->validate([
            'question' => 'sometimes|required|string',
            'type' => 'sometimes|required|in:text,select,radio,checkbox,rating',
            'options' => 'nullable|array',
            'options.*' => 'string',
            'required' => 'nullable|boolean',
            'order' => 'sometimes|required|integer|min:0',
        ]);

        $question->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'سوال نیازسنجی با موفقیت بروزرسانی شد',
            'data' => $question
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/assessment-questions/{question}",
     *     summary="Delete an assessment question",
     *     tags={"Assessment Questions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="question",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Question deleted successfully"),
     *     @OA\Response(response=404, description="Question not found")
     * )
     */
    public function destroy(AssessmentQuestion $question)
    {
        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'سوال نیازسنجی با موفقیت حذف شد'
        ], 204);
    }

    /**
     * @OA\Put(
     *     path="/api/assessment-questions/{question}/reorder",
     *     summary="Update question order",
     *     tags={"Assessment Questions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="question",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order"},
     *             @OA\Property(property="order", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order updated successfully"
     *     )
     * )
     */
    public function reorder(Request $request, AssessmentQuestion $question)
    {
        $validated = $request->validate([
            'order' => 'required|integer|min:0',
        ]);

        $question->update(['order' => $validated['order']]);

        return response()->json([
            'success' => true,
            'message' => 'ترتیب سوال با موفقیت بروزرسانی شد',
            'data' => $question
        ]);
    }
}
