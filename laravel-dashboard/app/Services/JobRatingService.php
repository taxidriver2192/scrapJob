<?php

namespace App\Services;

use App\Models\User;
use App\Models\JobPosting;
use App\Models\Company;
use App\Exceptions\OpenAiException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\JobRating;

class JobRatingService
{
    private const NOT_SPECIFIED = 'not specified';

    private OpenAiService $openAiService;

    public function __construct(OpenAiService $openAiService)
    {
        $this->openAiService = $openAiService;
    }

    /**
     * Rate a job using AI based on user profile and job information
     */
    public function rateJobForUser(JobPosting $job, ?User $user = null): JobRating
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            throw new OpenAiException('User must be authenticated to rate jobs');
        }

        // Check if this user has already rated this job with AI
        $existingRating = JobRating::where('job_id', $job->job_id)
            ->where('user_id', $user->id)
            ->where('rating_type', 'ai_rating')
            ->first();

        if ($existingRating) {
            return $existingRating;
        }

        // Get company information
        $company = Company::find($job->company_id);

        // Build the prompt
        $prompt = $this->buildJobRatingPrompt($job, $company, $user);

        // Prepare metadata about what information was available
        $metadata = $this->prepareMetadata($job, $company, $user);

        try {
            // Get AI response
            $response = $this->openAiService->generateChatCompletion(
                $prompt,
                'gpt-4o-mini', // Using the same model as Go script
                0.1, // Low temperature for consistent results
                1000
            );

            // Calculate cost
            $usage = $response['usage'] ?? [];
            $promptTokens = $usage['prompt_tokens'] ?? 0;
            $completionTokens = $usage['completion_tokens'] ?? 0;
            $totalTokens = $usage['total_tokens'] ?? 0;
            $cost = $this->openAiService->calculateCost($promptTokens, $completionTokens, $response['model']);

            // Parse the AI response JSON to extract scores
            $aiResponse = json_decode($response['content'], true);
            if (!$aiResponse || !is_array($aiResponse)) {
                throw new OpenAiException('Invalid AI response format - could not parse JSON');
            }

            // Extract scores from the AI response
            $locationScore = $aiResponse['scores']['location'] ?? 0;
            $techScore = $aiResponse['scores']['tech_match'] ?? 0;
            $companySizeScore = $aiResponse['scores']['company_fit'] ?? 0;
            $seniorityScore = $aiResponse['scores']['seniority_match'] ?? 0;
            $workTypeScore = $aiResponse['scores']['work_type_match'] ?? 0;
            $confidence = $aiResponse['confidence'] ?? 0;
            $reasoning = $aiResponse['reasoning'] ?? [];

            // Create the rating record
            $rating = JobRating::create([
                'job_id' => $job->job_id,
                'user_id' => $user->id,
                'prompt' => $prompt,
                'response' => $response['content'],
                'model' => $response['model'],
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'cost' => $cost,
                'metadata' => $metadata,
                'overall_score' => 0, // Overall score is no longer used
                'location_score' => $locationScore,
                'tech_score' => $techScore,
                'team_size_score' => $companySizeScore,
                'leadership_score' => $seniorityScore,
                'criteria' => json_encode($reasoning),
                'rating_type' => 'ai_rating',
                'rated_at' => Carbon::now(),
            ]);

            Log::info('AI job rating created', [
                'rating_id' => $rating->id,
                'job_id' => $job->job_id,
                'user_id' => $user->id,
                'model' => $response['model'],
                'location_score' => $locationScore,
                'tech_score' => $techScore,
                'company_size_score' => $companySizeScore,
                'seniority_score' => $seniorityScore,
                'confidence' => $confidence,
                'cost' => $cost,
                'tokens' => $totalTokens,
                'prompt_length' => strlen($prompt),
                'response_length' => strlen($response['content']),
            ]);

            return $rating;

        } catch (OpenAiException $e) {
            Log::error('Failed to rate job with AI', [
                'job_id' => $job->job_id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Build the AI prompt (no overall_score field)
     */
    private function buildJobRatingPrompt(JobPosting $job, ?Company $company, User $user): string
    {
        // ---- 1. Job JSON ----
        $jobData = [
            'job_id'       => $job->job_id,
            'title'        => $job->title,
            'company_name' => $company?->name ?? $job->company_name ?? self::NOT_SPECIFIED,
            'location'     => $job->location,
            'work_type'    => $job->work_type,
            'skills'       => $job->skills,
            'summary'      => $job->brief_summary_of_job
                              ?: str($job->description)->limit(500),
        ];

        // ---- 2. Candidate JSON ----
        $userData = [
            'user_id'                => $user->id,
            'preferred_location'     => $user->preferred_location,
            'years_of_experience'    => $user->years_of_experience,
            'skills'                 => $user->skills,
            'preferred_job_type'     => $user->preferred_job_type,
            'remote_work_preference' => $user->remote_work_preference,
            'willing_to_relocate'    => $user->willing_to_relocate,
            'open_to_management'     => $user->open_to_management,
            'highest_education'      => $user->highest_education,
            'field_of_study'         => $user->field_of_study,
        ];

        // ---- 3. Company JSON (minimal) ----
        $companyData = $company ? [
            'industry'  => $company->industrydesc,
            'employees' => $company->employees,
        ] : new \stdClass();

        $jobJson     = json_encode($jobData,     JSON_UNESCAPED_UNICODE);
        $userJson    = json_encode($userData,    JSON_UNESCAPED_UNICODE);
        $companyJson = json_encode($companyData, JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are an AI that scores how well a candidate fits a job posting.

INPUT:
{
  "job": $jobJson,
  "candidate": $userJson,
  "company": $companyJson
}

TASK:
• Score these five criteria on a 0-100 scale: location, tech_match, company_fit, seniority_match, work_type_match.  
• Provide one short sentence of reasoning per criterion.  
• Add a single-sentence summary.  
• Output a confidence (0-100) based on information completeness.  

OUTPUT: valid JSON only, **no overall_score**, exactly this shape:

{
  "job_id": <integer>,
  "scores": {
    "location": <integer>,
    "tech_match": <integer>,
    "company_fit": <integer>,
    "seniority_match": <integer>,
    "work_type_match": <integer>
  },
  "reasoning": {
    "location": "<text>",
    "tech_match": "<text>",
    "company_fit": "<text>",
    "seniority_match": "<text>",
    "work_type_match": "<text>",
    "summary": "<text>"
  },
  "confidence": <integer>
}

Return nothing outside the JSON object.
PROMPT;
    }

    /**
     * Prepare metadata about what information was available for the rating
     */
    private function prepareMetadata(JobPosting $job, ?Company $company, User $user): array
    {
        return [
            'job_data_available' => [
                'title' => !empty($job->title),
                'location' => !empty($job->location),
                'description' => !empty($job->description) && strlen($job->description) > 50,
                'skills' => !empty($job->skills),
                'work_type' => !empty($job->work_type),
                'applicants' => !empty($job->applicants),
            ],
            'company_data_available' => [
                'name' => !empty($company?->name),
                'employees' => !empty($company?->employees),
                'industry' => !empty($company?->industrydesc),
                'description' => !empty($company?->companydesc),
                'website' => !empty($company?->website),
            ],
            'user_data_available' => [
                'location' => !empty($user->preferred_location),
                'experience' => !empty($user->years_of_experience),
                'skills' => !empty($user->skills),
                'career_summary' => !empty($user->career_summary),
                'preferences' => !empty($user->preferred_job_type),
                'education' => !empty($user->highest_education),
                'current_role' => !empty($user->current_job_title),
            ],
            'profile_completeness' => $this->calculateProfileCompleteness($user),
        ];
    }

    /**
     * Calculate how complete the user's profile is
     */
    private function calculateProfileCompleteness(User $user): float
    {
        $fields = [
            'preferred_location', 'years_of_experience', 'skills', 'career_summary',
            'preferred_job_type', 'current_job_title', 'industry', 'highest_education',
            'remote_work_preference', 'open_to_management'
        ];

        $completed = 0;
        foreach ($fields as $field) {
            if (!empty($user->$field)) {
                $completed++;
            }
        }

        return round(($completed / count($fields)) * 100, 2);
    }
}
