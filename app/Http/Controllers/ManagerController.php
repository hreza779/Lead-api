<?php

namespace App\Http\Controllers;

use App\Models\Manager;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Tag(
 *     name="Managers",
 *     description="API Endpoints for managing company managers"
 * )
 */
class ManagerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/managers",
     *     summary="Get all managers",
     *     tags={"Managers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="company_id",
     *         in="query",
     *         description="Filter by company ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of managers"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Manager::with(['user', 'company']);

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/managers",
     *     summary="Add a manager to a company",
     *     tags={"Managers"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"company_id", "name", "phone"},
     *             @OA\Property(property="company_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Jane Doe"),
     *             @OA\Property(property="phone", type="string", example="09123456789"),
     *             @OA\Property(property="position", type="string", nullable=true, example="HR Manager"),
     *             @OA\Property(property="department", type="string", nullable=true, example="Human Resources")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Manager added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="مدیر با موفقیت به شرکت اضافه شد"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User is not the owner of the company",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما اجازه افزودن مدیر به این شرکت را ندارید یا شرکت یافت نشد.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict - User is already a manager for this company",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="این کاربر قبلاً به عنوان مدیر در این شرکت ثبت شده است")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(\App\Http\Requests\StoreManagerRequest $request)
    {
        // Check strict ownership
        $company = Company::where('id', $request->company_id)
            ->where('owner_id', auth()->id())
            ->first();

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'شما اجازه افزودن مدیر به این شرکت را ندارید یا شرکت یافت نشد.'
            ], 403);
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            // Find or create user
            $user = User::firstOrCreate(
                ['phone' => $request->phone],
                [
                    'name' => $request->name,
                    'password' => Hash::make(Str::random(10)), // Random password for new users
                    'role' => 'manager',
                    'status' => 'active',
                    'email' => null, // Explicitly set nullable fields if needed
                ]
            );

            // Check if manager already exists for this company
            $existingManager = Manager::where('company_id', $request->company_id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingManager) {
                return response()->json([
                    'success' => false,
                    'message' => 'این کاربر قبلاً به عنوان مدیر در این شرکت ثبت شده است',
                    'data' => $existingManager->load('user')
                ], 409);
            }

            // Create manager record
            $manager = Manager::create([
                'company_id' => $request->company_id,
                'user_id' => $user->id,
                'position' => $request->position ?? null,
                'department' => $request->department ?? null,
                'status' => 'active',
                'assessment_status' => 'not_started', // Using default from migration
                'exam_status' => 'not_started', // Using default from migration
                'can_view_results' => false,
            ]);

            // Assign all assessment templates to the manager
            $templates = \App\Models\AssessmentTemplate::all();
            foreach ($templates as $template) {
                \App\Models\Assessment::create([
                    'manager_id' => $manager->id,
                    'template_id' => $template->id,
                    'status' => 'draft',
                    'current_step' => 1,
                    'answers' => [],
                    'score' => 0,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'مدیر با موفقیت به شرکت اضافه شد و ارزیابی‌ها تخصیص یافتند',
                'data' => $manager->load(['user', 'company'])
            ], 201);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/managers/{manager}",
     *     summary="Get manager details",
     *     tags={"Managers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="manager",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Manager details"
     *     )
     * )
     */
    public function show(Manager $manager)
    {
        return response()->json([
            'success' => true,
            'data' => $manager->load(['user', 'company'])
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/managers/{manager}",
     *     summary="Update manager details",
     *     tags={"Managers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="manager",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="position", type="string"),
     *             @OA\Property(property="department", type="string"),
     *             @OA\Property(property="can_view_results", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Manager updated successfully"
     *     )
     * )
     */
    public function update(Request $request, Manager $manager)
    {
        $validated = $request->validate([
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'can_view_results' => 'nullable|boolean',
        ]);

        $manager->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'اطلاعات مدیر بروزرسانی شد',
            'data' => $manager->load('user')
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/managers/{manager}",
     *     summary="Remove manager from company",
     *     tags={"Managers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="manager",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Manager removed successfully")
     * )
     */
    public function destroy(Manager $manager)
    {
        $manager->delete();
        return response()->json([
            'success' => true,
            'message' => 'مدیر با موفقیت حذف شد'
        ], 204);
    }
}
