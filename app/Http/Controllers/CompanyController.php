<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Companies",
 *     description="API Endpoints for managing companies"
 * )
 */
class CompanyController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/companies",
     *     summary="Get all companies",
     *     tags={"Companies"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="owner_id",
     *         in="query",
     *         description="Filter by owner ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of companies"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Company::with('owner');

        if ($request->has('owner_id')) {
            $query->where('owner_id', $request->owner_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/companies/my-companies",
     *     summary="Get companies associated with logged-in user (Owner or Manager)",
     *     tags={"Companies"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of user's companies"
     *     )
     * )
     */
    public function myCompanies()
    {
        $user = auth()->user();

        $companies = Company::where('owner_id', $user->id)
            ->orWhereHas('managers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with('owner')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $companies
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/companies",
     *     summary="Create a new company",
     *     tags={"Companies"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "manager_name", "manager_phone"},
     *             @OA\Property(property="name", type="string", example="Acme Corp"),
     *             @OA\Property(property="legal_name", type="string", example="Acme Corporation Ltd"),
     *             @OA\Property(property="phone", type="string", example="02188888888"),
     *             @OA\Property(property="email", type="string", example="info@acme.com"),
     *             @OA\Property(property="address", type="string", example="Tehran, Vanak Sq."),
     *             @OA\Property(property="website", type="string", example="https://acme.com"),
     *             @OA\Property(property="description", type="string", example="Best company ever"),
     *             @OA\Property(property="manager_name", type="string", example="John Doe"),
     *             @OA\Property(property="manager_phone", type="string", example="09123456789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Company created successfully"
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'manager_name' => 'required|string|max:255',
            'manager_phone' => 'required|string|regex:/^09[0-9]{9}$/'
        ]);

        $companyData = $request->only([
            'name',
            'legal_name',
            'phone',
            'email',
            'address',
            'website',
            'description'
        ]);

        $companyData['owner_id'] = auth()->id();
        $companyData['status'] = 'active';

        $company = Company::create($companyData);

        return response()->json([
            'success' => true,
            'message' => 'شرکت با موفقیت ایجاد شد',
            'data' => $company
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/companies/{company}",
     *     summary="Get company details",
     *     tags={"Companies"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="company",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Company details"
     *     )
     * )
     */
    public function show(Company $company)
    {
        return response()->json([
            'success' => true,
            'data' => $company->load('owner')
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/companies/{company}",
     *     summary="Update company",
     *     tags={"Companies"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="company",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="address", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Company updated successfully"
     *     )
     * )
     */
    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive,pending'
        ]);

        $company->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'اطلاعات شرکت بروزرسانی شد',
            'data' => $company
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/companies/{company}",
     *     summary="Delete company",
     *     tags={"Companies"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="company",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Company deleted successfully")
     * )
     */
    public function destroy(Company $company)
    {
        $company->delete();
        return response()->json([
            'success' => true,
            'message' => 'شرکت با موفقیت حذف شد'
        ], 204);
    }
}
