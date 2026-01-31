<?php

namespace App\Http\Controllers;

use App\Models\AssessmentTemplate;
use App\Models\AssessmentStep;
use App\Models\AssessmentQuestion;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Assessment Templates",
 *     description="API Endpoints for managing assessment templates"
 * )
 */
class AssessmentTemplateController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/assessment-templates",
     *     summary="Get all assessment templates",
     *     tags={"Assessment Templates"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"active","draft","archived"})
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of assessment templates"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = AssessmentTemplate::with(['steps.questions', 'creator']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/assessment-templates",
     *     summary="Create a new assessment template",
     *     tags={"Assessment Templates"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","category","estimated_time"},
     *             @OA\Property(property="name", type="string", example="Employee Onboarding"),
     *             @OA\Property(property="description", type="string", example="Initial assessment for new employees"),
     *             @OA\Property(property="category", type="string", example="HR"),
     *             @OA\Property(property="estimated_time", type="integer", example=30),
     *             @OA\Property(property="status", type="string", enum={"active","draft","archived"}, example="draft"),
     *             @OA\Property(
     *                 property="steps",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="order", type="integer"),
     *                     @OA\Property(
     *                         property="questions",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="question", type="string"),
     *                             @OA\Property(property="type", type="string", enum={"text","select","radio","checkbox","rating"}),
     *                             @OA\Property(property="options", type="array", @OA\Items(type="string")),
     *                             @OA\Property(property="required", type="boolean"),
     *                             @OA\Property(property="order", type="integer")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Template created successfully"
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:255',
            'estimated_time' => 'required|integer|min:1',
            'status' => 'nullable|in:active,draft,archived',
            'steps' => 'nullable|array',
            'steps.*.title' => 'required|string|max:255',
            'steps.*.description' => 'nullable|string',
            'steps.*.order' => 'required|integer|min:0',
            'steps.*.questions' => 'nullable|array',
            'steps.*.questions.*.question' => 'required|string',
            'steps.*.questions.*.type' => 'required|in:text,select,radio,checkbox,rating',
            'steps.*.questions.*.options' => 'nullable|array',
            'steps.*.questions.*.required' => 'nullable|boolean',
            'steps.*.questions.*.order' => 'required|integer|min:0',
        ]);

        $templateData = $request->only(['name', 'description', 'category', 'estimated_time', 'status']);
        $templateData['created_by'] = auth()->id();
        $templateData['status'] = $templateData['status'] ?? 'draft';

        $template = AssessmentTemplate::create($templateData);

        // Create steps and questions if provided
        if ($request->has('steps') && is_array($request->steps)) {
            foreach ($request->steps as $stepData) {
                $questions = $stepData['questions'] ?? [];
                unset($stepData['questions']);

                $stepData['template_id'] = $template->id;
                $step = AssessmentStep::create($stepData);

                // Create questions for this step
                if (!empty($questions)) {
                    foreach ($questions as $questionData) {
                        $questionData['step_id'] = $step->id;
                        AssessmentQuestion::create($questionData);
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'قالب نیازسنجی با موفقیت ایجاد شد',
            'data' => $template->load('steps.questions')
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/assessment-templates/{template}",
     *     summary="Get a specific assessment template",
     *     tags={"Assessment Templates"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="template",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Template details"
     *     ),
     *     @OA\Response(response=404, description="Template not found")
     * )
     */
    public function show(AssessmentTemplate $template)
    {
        return response()->json([
            'success' => true,
            'data' => $template->load(['steps.questions', 'creator'])
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/assessment-templates/{template}",
     *     summary="Update an assessment template",
     *     tags={"Assessment Templates"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="template",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="category", type="string"),
     *             @OA\Property(property="estimated_time", type="integer"),
     *             @OA\Property(property="status", type="string", enum={"active","draft","archived"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Template updated successfully"
     *     ),
     *     @OA\Response(response=404, description="Template not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, AssessmentTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|required|string|max:255',
            'estimated_time' => 'sometimes|required|integer|min:1',
            'status' => 'nullable|in:active,draft,archived',
        ]);

        $template->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'قالب نیازسنجی با موفقیت بروزرسانی شد',
            'data' => $template->load('steps.questions')
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/assessment-templates/{template}",
     *     summary="Delete an assessment template",
     *     tags={"Assessment Templates"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="template",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Template deleted successfully"),
     *     @OA\Response(response=404, description="Template not found")
     * )
     */
    public function destroy(AssessmentTemplate $template)
    {
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'قالب نیازسنجی با موفقیت حذف شد'
        ], 204);
    }
}
