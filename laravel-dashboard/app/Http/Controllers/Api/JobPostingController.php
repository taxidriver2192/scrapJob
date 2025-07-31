<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class JobPostingController extends Controller
{
    /**
     * Check if a job posting exists by LinkedIn job ID
     *
     * @OA\Get(
     *     path="/jobs/exists",
     *     tags={"Job Postings"},
     *     summary="Check if a job posting exists by LinkedIn job ID",
     *     description="Returns whether a job posting with the given LinkedIn job ID exists in the database",
     *     @OA\Parameter(
     *         name="linkedin_job_id",
     *         in="query",
     *         required=true,
     *         description="LinkedIn job ID to search for",
     *         @OA\Schema(type="string", maxLength=255)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job posting existence check result",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="exists", type="boolean", description="Whether the job posting exists")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     security={{"ApiKeyAuth":{}}}
     * )
     */
    public function exists(Request $request): JsonResponse
    {
        $request->validate([
            'linkedin_job_id' => 'required|string|max:255'
        ]);

        $jobPosting = JobPosting::where('linkedin_job_id', $request->linkedin_job_id)->first();

        return response()->json([
            'exists' => !is_null($jobPosting)
        ]);
    }

    /**
     * Create a new job posting
     *
     * @OA\Post(
     *     path="/jobs",
     *     tags={"Job Postings"},
     *     summary="Create a new job posting",
     *     description="Creates a new job posting in the database",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"linkedin_job_id", "title", "company_id"},
     *             @OA\Property(property="linkedin_job_id", type="string", maxLength=255, description="LinkedIn job ID (unique)"),
     *             @OA\Property(property="title", type="string", maxLength=255, description="Job title"),
     *             @OA\Property(property="company_id", type="integer", description="Company ID (must exist in companies table)"),
     *             @OA\Property(property="location", type="string", maxLength=255, nullable=true, description="Job location"),
     *             @OA\Property(property="description", type="string", nullable=true, description="Job description"),
     *             @OA\Property(property="brief_summary_of_job", type="string", nullable=true, description="Brief job summary"),
     *             @OA\Property(property="apply_url", type="string", format="url", maxLength=1000, nullable=true, description="Application URL"),
     *             @OA\Property(property="posted_date", type="string", format="date", nullable=true, description="Date when job was posted"),
     *             @OA\Property(property="applicants", type="integer", minimum=0, nullable=true, description="Number of applicants"),
     *             @OA\Property(property="work_type", type="string", maxLength=100, nullable=true, description="Work type (remote, hybrid, onsite)"),
     *             @OA\Property(property="skills", type="array", @OA\Items(type="string", maxLength=100), nullable=true, description="Required skills"),
     *             @OA\Property(property="openai_adresse", type="string", maxLength=500, nullable=true, description="AI-processed address"),
     *             @OA\Property(property="latitude", type="number", format="float", minimum=-90, maximum=90, nullable=true, description="Latitude coordinate"),
     *             @OA\Property(property="longitude", type="number", format="float", minimum=-180, maximum=180, nullable=true, description="Longitude coordinate"),
     *             @OA\Property(property="job_post_closed_date", type="string", format="date", nullable=true, description="Date when job posting closes"),
     *             @OA\Property(property="city", type="string", maxLength=100, nullable=true, description="City"),
     *             @OA\Property(property="zipcode", type="string", maxLength=20, nullable=true, description="Postal code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Job posting created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Job posting created successfully"),
     *             @OA\Property(
     *                 property="job_posting",
     *                 type="object",
     *                 @OA\Property(property="job_id", type="integer"),
     *                 @OA\Property(property="linkedin_job_id", type="string"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="company_id", type="integer"),
     *                 @OA\Property(property="company_name", type="string", nullable=true),
     *                 @OA\Property(property="location", type="string", nullable=true),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="brief_summary_of_job", type="string", nullable=true),
     *                 @OA\Property(property="apply_url", type="string", nullable=true),
     *                 @OA\Property(property="posted_date", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="applicants", type="integer", nullable=true),
     *                 @OA\Property(property="work_type", type="string", nullable=true),
     *                 @OA\Property(property="skills", type="array", @OA\Items(type="string"), nullable=true),
     *                 @OA\Property(property="city", type="string", nullable=true),
     *                 @OA\Property(property="zipcode", type="string", nullable=true),
     *                 @OA\Property(property="latitude", type="number", format="float", nullable=true),
     *                 @OA\Property(property="longitude", type="number", format="float", nullable=true),
     *                 @OA\Property(property="job_post_closed_date", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="is_closed", type="boolean")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Job posting already exists",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Job posting with this LinkedIn ID already exists"),
     *             @OA\Property(
     *                 property="job_posting",
     *                 type="object",
     *                 @OA\Property(property="job_id", type="integer"),
     *                 @OA\Property(property="linkedin_job_id", type="string"),
     *                 @OA\Property(property="title", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An error occurred while creating the job posting"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     security={{"ApiKeyAuth":{}}}
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'linkedin_job_id' => 'required|string|max:255|unique:job_postings,linkedin_job_id',
                'title' => 'required|string|max:255',
                'company_id' => 'required|integer|exists:companies,company_id',
                'location' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'brief_summary_of_job' => 'nullable|string',
                'apply_url' => 'nullable|url|max:1000',
                'posted_date' => 'nullable|date',
                'applicants' => 'nullable|integer|min:0',
                'work_type' => 'nullable|string|max:100',
                'skills' => 'nullable|array',
                'skills.*' => 'string|max:100',
                'openai_adresse' => 'nullable|string|max:500',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'job_post_closed_date' => 'nullable|date',
                'city' => 'nullable|string|max:100',
                'zipcode' => 'nullable|string|max:20',
            ]);

            // Check if job posting already exists
            $existingJob = JobPosting::where('linkedin_job_id', $validated['linkedin_job_id'])->first();
            if ($existingJob) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job posting with this LinkedIn ID already exists',
                    'job_posting' => [
                        'job_id' => $existingJob->job_id,
                        'linkedin_job_id' => $existingJob->linkedin_job_id,
                        'title' => $existingJob->title,
                    ]
                ], 409);
            }

            $jobPosting = JobPosting::create($validated);

            // Load the company relationship
            $jobPosting->load('company');

            return response()->json([
                'success' => true,
                'message' => 'Job posting created successfully',
                'job_posting' => [
                    'job_id' => $jobPosting->job_id,
                    'linkedin_job_id' => $jobPosting->linkedin_job_id,
                    'title' => $jobPosting->title,
                    'company_id' => $jobPosting->company_id,
                    'company_name' => $jobPosting->company?->name,
                    'location' => $jobPosting->location,
                    'description' => $jobPosting->description,
                    'brief_summary_of_job' => $jobPosting->brief_summary_of_job,
                    'apply_url' => $jobPosting->apply_url,
                    'posted_date' => $jobPosting->posted_date,
                    'applicants' => $jobPosting->applicants,
                    'work_type' => $jobPosting->work_type,
                    'skills' => $jobPosting->skills,
                    'city' => $jobPosting->city,
                    'zipcode' => $jobPosting->zipcode,
                    'latitude' => $jobPosting->latitude,
                    'longitude' => $jobPosting->longitude,
                    'job_post_closed_date' => $jobPosting->job_post_closed_date,
                    'is_closed' => $jobPosting->isClosed(),
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
                'message' => 'An error occurred while creating the job posting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job posting by ID
     *
     * @OA\Get(
     *     path="/jobs/{id}",
     *     tags={"Job Postings"},
     *     summary="Get job posting by ID",
     *     description="Returns detailed information about a specific job posting",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Job posting ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job posting details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="job_posting",
     *                 type="object",
     *                 @OA\Property(property="job_id", type="integer"),
     *                 @OA\Property(property="linkedin_job_id", type="string"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="company_id", type="integer"),
     *                 @OA\Property(property="company_name", type="string", nullable=true),
     *                 @OA\Property(property="location", type="string", nullable=true),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="brief_summary_of_job", type="string", nullable=true),
     *                 @OA\Property(property="apply_url", type="string", nullable=true),
     *                 @OA\Property(property="posted_date", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="applicants", type="integer", nullable=true),
     *                 @OA\Property(property="work_type", type="string", nullable=true),
     *                 @OA\Property(property="skills", type="array", @OA\Items(type="string"), nullable=true),
     *                 @OA\Property(property="openai_adresse", type="string", nullable=true),
     *                 @OA\Property(property="latitude", type="number", format="float", nullable=true),
     *                 @OA\Property(property="longitude", type="number", format="float", nullable=true),
     *                 @OA\Property(property="job_post_closed_date", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="city", type="string", nullable=true),
     *                 @OA\Property(property="zipcode", type="string", nullable=true),
     *                 @OA\Property(property="is_closed", type="boolean"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Job posting not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Job posting not found")
     *         )
     *     ),
     *     security={{"ApiKeyAuth":{}}}
     * )
     */
    public function show($id): JsonResponse
    {
        $jobPosting = JobPosting::with('company')->find($id);

        if (!$jobPosting) {
            return response()->json([
                'success' => false,
                'message' => 'Job posting not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'job_posting' => [
                'job_id' => $jobPosting->job_id,
                'linkedin_job_id' => $jobPosting->linkedin_job_id,
                'title' => $jobPosting->title,
                'company_id' => $jobPosting->company_id,
                'company_name' => $jobPosting->company?->name,
                'location' => $jobPosting->location,
                'description' => $jobPosting->description,
                'brief_summary_of_job' => $jobPosting->brief_summary_of_job,
                'apply_url' => $jobPosting->apply_url,
                'posted_date' => $jobPosting->posted_date,
                'applicants' => $jobPosting->applicants,
                'work_type' => $jobPosting->work_type,
                'skills' => $jobPosting->skills,
                'openai_adresse' => $jobPosting->openai_adresse,
                'latitude' => $jobPosting->latitude,
                'longitude' => $jobPosting->longitude,
                'job_post_closed_date' => $jobPosting->job_post_closed_date,
                'city' => $jobPosting->city,
                'zipcode' => $jobPosting->zipcode,
                'is_closed' => $jobPosting->isClosed(),
                'created_at' => $jobPosting->created_at,
                'updated_at' => $jobPosting->updated_at,
            ]
        ]);
    }

    /**
     * Get job posting by LinkedIn job ID
     *
     * @OA\Get(
     *     path="/jobs/linkedin/{linkedinJobId}",
     *     tags={"Job Postings"},
     *     summary="Get job posting by LinkedIn job ID",
     *     description="Returns detailed information about a specific job posting using LinkedIn job ID",
     *     @OA\Parameter(
     *         name="linkedinJobId",
     *         in="path",
     *         required=true,
     *         description="LinkedIn job ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job posting details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="job_posting",
     *                 type="object",
     *                 @OA\Property(property="job_id", type="integer"),
     *                 @OA\Property(property="linkedin_job_id", type="string"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="company_id", type="integer"),
     *                 @OA\Property(property="company_name", type="string", nullable=true),
     *                 @OA\Property(property="location", type="string", nullable=true),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="brief_summary_of_job", type="string", nullable=true),
     *                 @OA\Property(property="apply_url", type="string", nullable=true),
     *                 @OA\Property(property="posted_date", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="applicants", type="integer", nullable=true),
     *                 @OA\Property(property="work_type", type="string", nullable=true),
     *                 @OA\Property(property="skills", type="array", @OA\Items(type="string"), nullable=true),
     *                 @OA\Property(property="openai_adresse", type="string", nullable=true),
     *                 @OA\Property(property="latitude", type="number", format="float", nullable=true),
     *                 @OA\Property(property="longitude", type="number", format="float", nullable=true),
     *                 @OA\Property(property="job_post_closed_date", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="city", type="string", nullable=true),
     *                 @OA\Property(property="zipcode", type="string", nullable=true),
     *                 @OA\Property(property="is_closed", type="boolean"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Job posting not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Job posting not found")
     *         )
     *     ),
     *     security={{"ApiKeyAuth":{}}}
     * )
     */
    public function showByLinkedInId($linkedinJobId): JsonResponse
    {
        $jobPosting = JobPosting::with('company')->where('linkedin_job_id', $linkedinJobId)->first();

        if (!$jobPosting) {
            return response()->json([
                'success' => false,
                'message' => 'Job posting not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'job_posting' => [
                'job_id' => $jobPosting->job_id,
                'linkedin_job_id' => $jobPosting->linkedin_job_id,
                'title' => $jobPosting->title,
                'company_id' => $jobPosting->company_id,
                'company_name' => $jobPosting->company?->name,
                'location' => $jobPosting->location,
                'description' => $jobPosting->description,
                'brief_summary_of_job' => $jobPosting->brief_summary_of_job,
                'apply_url' => $jobPosting->apply_url,
                'posted_date' => $jobPosting->posted_date,
                'applicants' => $jobPosting->applicants,
                'work_type' => $jobPosting->work_type,
                'skills' => $jobPosting->skills,
                'openai_adresse' => $jobPosting->openai_adresse,
                'latitude' => $jobPosting->latitude,
                'longitude' => $jobPosting->longitude,
                'job_post_closed_date' => $jobPosting->job_post_closed_date,
                'city' => $jobPosting->city,
                'zipcode' => $jobPosting->zipcode,
                'is_closed' => $jobPosting->isClosed(),
                'created_at' => $jobPosting->created_at,
                'updated_at' => $jobPosting->updated_at,
            ]
        ]);
    }
}
