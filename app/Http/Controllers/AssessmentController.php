<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentTemplate;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Assessments",
 *     description="API Endpoints for managing assessments"
 * )
 */
class AssessmentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/assessments",
     *     summary="Get all assessments",
     *     tags={"Assessments"},
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
     *         @OA\Schema(type="string", enum={"draft","submitted"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of assessments"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Assessment::with(['manager', 'template']);

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
     *     path="/api/assessments",
     *     summary="Start a new assessment for a manager",
     *     tags={"Assessments"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"manager_id","template_id"},
     *             @OA\Property(property="manager_id", type="integer", example=1),
     *             @OA\Property(property="template_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Assessment started successfully"
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'manager_id' => 'required|exists:managers,id',
            'template_id' => 'required|exists:assessment_templates,id',
        ]);

        // Check if there's already a draft assessment for this manager and template
        $existingAssessment = Assessment::where('manager_id', $validated['manager_id'])
            ->where('template_id', $validated['template_id'])
            ->where('status', 'draft')
            ->first();

        if ($existingAssessment) {
            return response()->json([
                'success' => false,
                'message' => 'یک نیازسنجی در حال انجام برای این متقاضی وجود دارد',
                'data' => $existingAssessment->load(['manager', 'template'])
            ], 409);
        }

        $assessment = Assessment::create([
            'manager_id' => $validated['manager_id'],
            'template_id' => $validated['template_id'],
            'current_step' => 1,
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'نیازسنجی با موفقیت شروع شد',
            'data' => $assessment->load(['manager', 'template'])
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/assessments/{assessment}",
     *     summary="Get a specific assessment",
     *     tags={"Assessments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="assessment",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assessment details"
     *     ),
     *     @OA\Response(response=404, description="Assessment not found")
     * )
     */
    public function show(Assessment $assessment)
    {
        return response()->json([
            'success' => true,
            'data' => $assessment->load(['manager', 'template.steps.questions'])
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/assessments/{assessment}",
     *     summary="Update assessment (save progress)",
     *     tags={"Assessments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="assessment",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="current_step", type="integer", example=2),
     *             @OA\Property(
     *                 property="answers",
     *                 type="object",
     *                 example={"1":"Remote","2":"5 years"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assessment updated successfully"
     *     ),
     *     @OA\Response(response=404, description="Assessment not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, Assessment $assessment)
    {
        // Only allow updates to draft assessments
        if ($assessment->status === 'submitted') {
            return response()->json([
                'success' => false,
                'message' => 'نمی‌توانید نیازسنجی ثبت شده را ویرایش کنید'
            ], 403);
        }

        $validated = $request->validate([
            'current_step' => 'sometimes|required|integer|min:1',
            'answers' => 'sometimes|required|array',
        ]);

        $assessment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'پیشرفت نیازسنجی ذخیره شد',
            'data' => $assessment
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/assessments/{assessment}/submit",
     *     summary="Submit final assessment",
     *     tags={"Assessments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="assessment",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"answers"},
     *             @OA\Property(
     *                 property="answers",
     *                 type="object",
     *                 example={"1":"Remote","2":"5 years","3":"Yes"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assessment submitted successfully"
     *     )
     * )
     */
    public function submit(Request $request, Assessment $assessment)
    {
        // Only allow submitting draft assessments
        if ($assessment->status === 'submitted') {
            return response()->json([
                'success' => false,
                'message' => 'این نیازسنجی قبلاً ثبت شده است'
            ], 409);
        }

        $validated = $request->validate([
            'answers' => 'required|array',
        ]);

        $assessment->update([
            'answers' => $validated['answers'],
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'نیازسنجی با موفقیت ثبت شد',
            'data' => $assessment->load(['manager', 'template'])
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/assessments/{assessment}",
     *     summary="Delete an assessment",
     *     tags={"Assessments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="assessment",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Assessment deleted successfully"),
     *     @OA\Response(response=404, description="Assessment not found"),
     *     @OA\Response(response=403, description="Cannot delete submitted assessment")
     * )
     */
    public function destroy(Assessment $assessment)
    {
        // Only allow deleting draft assessments
        if ($assessment->status === 'submitted') {
            return response()->json([
                'success' => false,
                'message' => 'نمی‌توانید نیازسنجی ثبت شده را حذف کنید'
            ], 403);
        }

        $assessment->delete();

        return response()->json([
            'success' => true,
            'message' => 'نیازسنجی با موفقیت حذف شد'
        ], 204);
    }
}
