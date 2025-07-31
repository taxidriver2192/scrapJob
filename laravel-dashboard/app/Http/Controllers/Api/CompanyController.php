<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CompanyController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/companies/exists",
     *     summary="Check if a company exists by name",
     *     tags={"Companies"},
     *     security={{"ApiKeyAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Example Company Ltd")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Company existence check result",
     *         @OA\JsonContent(
     *             @OA\Property(property="exists", type="boolean", example=true),
     *             @OA\Property(property="company", type="object", nullable=true,
     *                 @OA\Property(property="company_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Example Company Ltd"),
     *                 @OA\Property(property="vat", type="string", nullable=true, example="NL123456789B01"),
     *                 @OA\Property(property="status", type="string", nullable=true, example="Active"),
     *                 @OA\Property(property="address", type="string", nullable=true, example="123 Business Street"),
     *                 @OA\Property(property="zipcode", type="string", nullable=true, example="1234AB"),
     *                 @OA\Property(property="city", type="string", nullable=true, example="Amsterdam"),
     *                 @OA\Property(property="website", type="string", nullable=true, example="https://example.com"),
     *                 @OA\Property(property="email", type="string", nullable=true, example="info@example.com"),
     *                 @OA\Property(property="employees", type="integer", nullable=true, example=50),
     *                 @OA\Property(property="industrycode", type="string", nullable=true, example="6201"),
     *                 @OA\Property(property="industrydesc", type="string", nullable=true, example="Computer programming activities"),
     *                 @OA\Property(property="companytype", type="string", nullable=true, example="BV")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function exists(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $company = Company::where('name', $request->name)->first();

        return response()->json([
            'exists' => !is_null($company),
            'company' => $company ? [
                'company_id' => $company->company_id,
                'name' => $company->name,
                'vat' => $company->vat,
                'status' => $company->status,
                'address' => $company->address,
                'zipcode' => $company->zipcode,
                'city' => $company->city,
                'website' => $company->website,
                'email' => $company->email,
                'employees' => $company->employees,
                'industrycode' => $company->industrycode,
                'industrydesc' => $company->industrydesc,
                'companytype' => $company->companytype,
            ] : null
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/companies",
     *     summary="Create a new company",
     *     tags={"Companies"},
     *     security={{"ApiKeyAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Example Company Ltd"),
     *             @OA\Property(property="vat", type="string", nullable=true, example="NL123456789B01"),
     *             @OA\Property(property="status", type="string", nullable=true, example="Active"),
     *             @OA\Property(property="address", type="string", nullable=true, example="123 Business Street"),
     *             @OA\Property(property="zipcode", type="string", nullable=true, example="1234AB"),
     *             @OA\Property(property="city", type="string", nullable=true, example="Amsterdam"),
     *             @OA\Property(property="protected", type="boolean", nullable=true, example=false),
     *             @OA\Property(property="phone", type="string", nullable=true, example="+31 20 1234567"),
     *             @OA\Property(property="website", type="string", nullable=true, example="https://example.com"),
     *             @OA\Property(property="email", type="string", nullable=true, example="info@example.com"),
     *             @OA\Property(property="fax", type="string", nullable=true, example="+31 20 7654321"),
     *             @OA\Property(property="startdate", type="string", format="date", nullable=true, example="2020-01-01"),
     *             @OA\Property(property="enddate", type="string", format="date", nullable=true, example="2025-12-31"),
     *             @OA\Property(property="employees", type="integer", nullable=true, example=50),
     *             @OA\Property(property="industrycode", type="string", nullable=true, example="6201"),
     *             @OA\Property(property="industrydesc", type="string", nullable=true, example="Computer programming activities"),
     *             @OA\Property(property="companytype", type="string", nullable=true, example="BV"),
     *             @OA\Property(property="companydesc", type="string", nullable=true, example="A software development company"),
     *             @OA\Property(property="owners", type="array", nullable=true, @OA\Items(type="object")),
     *             @OA\Property(property="financial_summary", type="array", nullable=true, @OA\Items(type="object")),
     *             @OA\Property(property="error", type="array", nullable=true, @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Company created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Company created successfully"),
     *             @OA\Property(property="company", type="object",
     *                 @OA\Property(property="company_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Example Company Ltd"),
     *                 @OA\Property(property="vat", type="string", nullable=true, example="NL123456789B01"),
     *                 @OA\Property(property="status", type="string", nullable=true, example="Active"),
     *                 @OA\Property(property="address", type="string", nullable=true, example="123 Business Street"),
     *                 @OA\Property(property="zipcode", type="string", nullable=true, example="1234AB"),
     *                 @OA\Property(property="city", type="string", nullable=true, example="Amsterdam"),
     *                 @OA\Property(property="website", type="string", nullable=true, example="https://example.com"),
     *                 @OA\Property(property="email", type="string", nullable=true, example="info@example.com"),
     *                 @OA\Property(property="employees", type="integer", nullable=true, example=50),
     *                 @OA\Property(property="industrycode", type="string", nullable=true, example="6201"),
     *                 @OA\Property(property="industrydesc", type="string", nullable=true, example="Computer programming activities"),
     *                 @OA\Property(property="companytype", type="string", nullable=true, example="BV")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Company already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Company with this name already exists"),
     *             @OA\Property(property="company", type="object",
     *                 @OA\Property(property="company_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Example Company Ltd")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An error occurred while creating the company"),
     *             @OA\Property(property="error", type="string", example="Database connection failed")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'vat' => 'nullable|string|max:50',
                'status' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:500',
                'zipcode' => 'nullable|string|max:20',
                'city' => 'nullable|string|max:100',
                'protected' => 'nullable|boolean',
                'phone' => 'nullable|string|max:50',
                'website' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'fax' => 'nullable|string|max:50',
                'startdate' => 'nullable|date',
                'enddate' => 'nullable|date',
                'employees' => 'nullable|integer|min:0',
                'industrycode' => 'nullable|string|max:20',
                'industrydesc' => 'nullable|string|max:500',
                'companytype' => 'nullable|string|max:100',
                'companydesc' => 'nullable|string',
                'owners' => 'nullable|array',
                'financial_summary' => 'nullable|array',
                'error' => 'nullable|array',
            ]);

            // Check if company already exists
            $existingCompany = Company::where('name', $validated['name'])->first();
            if ($existingCompany) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company with this name already exists',
                    'company' => [
                        'company_id' => $existingCompany->company_id,
                        'name' => $existingCompany->name,
                    ]
                ], 409);
            }

            $company = Company::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Company created successfully',
                'company' => [
                    'company_id' => $company->company_id,
                    'name' => $company->name,
                    'vat' => $company->vat,
                    'status' => $company->status,
                    'address' => $company->address,
                    'zipcode' => $company->zipcode,
                    'city' => $company->city,
                    'website' => $company->website,
                    'email' => $company->email,
                    'employees' => $company->employees,
                    'industrycode' => $company->industrycode,
                    'industrydesc' => $company->industrydesc,
                    'companytype' => $company->companytype,
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the company',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/companies/{id}",
     *     summary="Get company by ID",
     *     tags={"Companies"},
     *     security={{"ApiKeyAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Company details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="company", type="object",
     *                 @OA\Property(property="company_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Example Company Ltd"),
     *                 @OA\Property(property="vat", type="string", nullable=true, example="NL123456789B01"),
     *                 @OA\Property(property="status", type="string", nullable=true, example="Active"),
     *                 @OA\Property(property="address", type="string", nullable=true, example="123 Business Street"),
     *                 @OA\Property(property="zipcode", type="string", nullable=true, example="1234AB"),
     *                 @OA\Property(property="city", type="string", nullable=true, example="Amsterdam"),
     *                 @OA\Property(property="protected", type="boolean", nullable=true, example=false),
     *                 @OA\Property(property="phone", type="string", nullable=true, example="+31 20 1234567"),
     *                 @OA\Property(property="website", type="string", nullable=true, example="https://example.com"),
     *                 @OA\Property(property="email", type="string", nullable=true, example="info@example.com"),
     *                 @OA\Property(property="fax", type="string", nullable=true, example="+31 20 7654321"),
     *                 @OA\Property(property="startdate", type="string", format="date", nullable=true, example="2020-01-01"),
     *                 @OA\Property(property="enddate", type="string", format="date", nullable=true, example="2025-12-31"),
     *                 @OA\Property(property="employees", type="integer", nullable=true, example=50),
     *                 @OA\Property(property="industrycode", type="string", nullable=true, example="6201"),
     *                 @OA\Property(property="industrydesc", type="string", nullable=true, example="Computer programming activities"),
     *                 @OA\Property(property="companytype", type="string", nullable=true, example="BV"),
     *                 @OA\Property(property="companydesc", type="string", nullable=true, example="A software development company"),
     *                 @OA\Property(property="owners", type="array", nullable=true, @OA\Items(type="object")),
     *                 @OA\Property(property="financial_summary", type="array", nullable=true, @OA\Items(type="object")),
     *                 @OA\Property(property="error", type="array", nullable=true, @OA\Items(type="object")),
     *                 @OA\Property(property="created_at", type="string", format="datetime", example="2024-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="datetime", example="2024-01-01T00:00:00.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Company not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Company not found")
     *         )
     *     )
     * )
     */
    public function show($id): JsonResponse
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'company' => [
                'company_id' => $company->company_id,
                'name' => $company->name,
                'vat' => $company->vat,
                'status' => $company->status,
                'address' => $company->address,
                'zipcode' => $company->zipcode,
                'city' => $company->city,
                'protected' => $company->protected,
                'phone' => $company->phone,
                'website' => $company->website,
                'email' => $company->email,
                'fax' => $company->fax,
                'startdate' => $company->startdate,
                'enddate' => $company->enddate,
                'employees' => $company->employees,
                'industrycode' => $company->industrycode,
                'industrydesc' => $company->industrydesc,
                'companytype' => $company->companytype,
                'companydesc' => $company->companydesc,
                'owners' => $company->owners,
                'financial_summary' => $company->financial_summary,
                'error' => $company->error,
                'created_at' => $company->created_at,
                'updated_at' => $company->updated_at,
            ]
        ]);
    }
}
