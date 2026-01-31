<?php

namespace App\Http\Controllers;

use App\Models\AssessmentStep;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Assessment Steps",
 *     description="API Endpoints for managing assessment steps"
 * )
 */
class AssessmentStepController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/assessment-steps",
     *     summary="Get all assessment steps",
     *     tags={"Assessment Steps"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="template_id",
     *         in="query",
     *         description="Filter by template ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of assessment steps"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = AssessmentStep::with(['template', 'questions']);

        if ($request->has('template_id')) {
            $query->where('template_id', $request->template_id);
        }

        $steps = $query->orderBy('order')->get();

        return response()->json([
            'success' => true,
            'data' => $steps
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/assessment-steps",
     *     summary="Create a new assessment step",
     *     tags={"Assessment Steps"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"template_id","title","order"},
     *             @OA\Property(property="template_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Personal Information"),
     *             @OA\Property(property="description", type="string", example="Basic personal details"),
     *             @OA\Property(property="order", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Step created successfully"
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:assessment_templates,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'required|integer|min:0',
        ]);

        $step = AssessmentStep::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'مرحله نیازسنجی با موفقیت ایجاد شد',
            'data' => $step->load('questions')
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/assessment-steps/{step}",
     *     summary="Get a specific assessment step",
     *     tags={"Assessment Steps"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="step",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Step details"
     *     ),
     *     @OA\Response(response=404, description="Step not found")
     * )
     */
    public function show(AssessmentStep $step)
    {
        return response()->json([
            'success' => true,
            'data' => $step->load(['template', 'questions'])
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/assessment-steps/{step}",
     *     summary="Update an assessment step",
     *     tags={"Assessment Steps"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="step",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="order", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Step updated successfully"
     *     ),
     *     @OA\Response(response=404, description="Step not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, AssessmentStep $step)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'sometimes|required|integer|min:0',
        ]);

        $step->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'مرحله نیازسنجی با موفقیت بروزرسانی شد',
            'data' => $step->load('questions')
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/assessment-steps/{step}",
     *     summary="Delete an assessment step",
     *     tags={"Assessment Steps"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="step",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Step deleted successfully"),
     *     @OA\Response(response=404, description="Step not found")
     * )
     */
    public function destroy(AssessmentStep $step)
    {
        $step->delete();

        return response()->json([
            'success' => true,
            'message' => 'مرحله نیازسنجی با موفقیت حذف شد'
        ], 204);
    }

    /**
     * @OA\Put(
     *     path="/api/assessment-steps/{step}/reorder",
     *     summary="Update step order",
     *     tags={"Assessment Steps"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="step",
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
    public function reorder(Request $request, AssessmentStep $step)
    {
        $validated = $request->validate([
            'order' => 'required|integer|min:0',
        ]);

        $step->update(['order' => $validated['order']]);

        return response()->json([
            'success' => true,
            'message' => 'ترتیب مرحله با موفقیت بروزرسانی شد',
            'data' => $step
        ]);
    }
}
