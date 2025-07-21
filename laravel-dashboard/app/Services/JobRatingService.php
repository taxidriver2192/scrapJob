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
            $overallScore = $aiResponse['overall_score'] ?? 0;
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
                'overall_score' => $overallScore,
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
                'overall_score' => $overallScore,
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
     * Build the AI prompt based on job, company, and user information
     */
    private function buildJobRatingPrompt(JobPosting $job, ?Company $company, User $user): string
    {
        // Format job information
        $jobTitle = $job->title ?? self::NOT_SPECIFIED;
        $companyName = $company?->name ?? $job->company_name ?? self::NOT_SPECIFIED;
        $location = $job->location ?? self::NOT_SPECIFIED;
        $applicants = $job->applicants ? "{$job->applicants} applicants" : self::NOT_SPECIFIED;
        $workType = $job->work_type ?? self::NOT_SPECIFIED;
        $skills = $job->skills ? json_encode($job->skills) : self::NOT_SPECIFIED;
        $descriptionAvailable = (!empty($job->description) && strlen($job->description) > 50) ? 'Yes' : 'No';

        // Format user profile information
        $userProfile = $this->buildUserProfileText($user);

        // Build the prompt (inspired by the Go script)
        return sprintf('You are an expert job matching AI that provides REALISTIC and ACCURATE evaluations based on AVAILABLE data only.

ANALYZE THIS JOB:
Job ID: %s
Title: %s
Company: %s
Location: %s
Applicants: %s
Work Type: %s
Skills: %s
Description Available: %s

CANDIDATE PROFILE:
%s

SCORING GUIDELINES (0-100, be REALISTIC and use available data):

**LOCATION MATCH:**
• User preferred location or close by: 90-100 (perfect)
• Same country/region: 70-85 (good)
• Remote work available and user wants remote: 85-95 (very good)
• Different country but remote mentioned: 60-80 (possible)
• Empty/unclear location: 40 (unknown, assume average)

**TECH MATCH:**
Analyze job title + skills array + any description for matches with user skills/experience:
• 5+ technology matches: 90-100 (excellent match)
• 3-4 technology matches: 75-85 (good match)
• 1-2 technology matches: 50-70 (possible match)
• Related technologies: 40-60 (transferable)
• No clear tech match but development role: 20-40 (unlikely)
• Non-technical role: 0-20 (poor match)

**COMPANY FIT:**
Based on company information and user preferences:
• Company size matches user preference: 80-100
• Industry matches user experience/interest: 75-90
• Company values align with user profile: 70-85
• Limited company information: 50 (neutral)

**SENIORITY MATCH:**
Analyze job title against user experience level:
• Perfect seniority match: 90-100
• Slight level difference: 70-85
• User overqualified: 60-75
• User underqualified: 30-50
• Management role but user doesn\'t want management: 10-30

**WORK TYPE MATCH:**
Based on job work type and user preferences:
• Perfect match (remote/hybrid/onsite): 90-100
• Acceptable alternative: 70-85
• Not preferred but workable: 50-65
• Strongly conflicts with preference: 20-40

IMPORTANT: Work with available data only. If information is missing, don\'t penalize - focus on what IS available. Provide specific reasoning for each score.

Return ONLY this JSON (no markdown formatting, all scores must be integers):

{
  "job_id": "%s",
  "overall_score": [INTEGER_WEIGHTED_AVERAGE],
  "scores": {
    "location": [INTEGER_SCORE],
    "tech_match": [INTEGER_SCORE],
    "company_fit": [INTEGER_SCORE],
    "seniority_match": [INTEGER_SCORE],
    "work_type_match": [INTEGER_SCORE]
  },
  "reasoning": {
    "location": "Location assessment based on user preferences",
    "tech_match": "Technology match analysis with user skills",
    "company_fit": "Company size/culture fit assessment",
    "seniority_match": "Seniority level analysis",
    "work_type_match": "Work arrangement match analysis",
    "summary": "Brief overall job fit assessment"
  },
  "confidence": [0-100_HOW_CONFIDENT_ARE_YOU]
}',
            $job->job_id,
            $jobTitle,
            $companyName,
            $location,
            $applicants,
            $workType,
            $skills,
            $descriptionAvailable,
            $userProfile,
            $job->job_id
        );
    }

    /**
     * Build user profile text from available user data
     */
    private function buildUserProfileText(User $user): string
    {
        $profile = [];

        $this->addBasicInfo($profile, $user);
        $this->addExperienceInfo($profile, $user);
        $this->addPreferences($profile, $user);
        $this->addEducationInfo($profile, $user);

        return empty($profile)
            ? "• Profile information not yet completed - please update your profile for better job matching"
            : implode("\n", $profile);
    }

    private function addBasicInfo(array &$profile, User $user): void
    {
        if ($user->preferred_location) {
            $profile[] = "• Lives in: {$user->preferred_location}";
        }

        if ($user->years_of_experience) {
            $profile[] = "• Years of experience: {$user->years_of_experience}";
        }
    }

    private function addExperienceInfo(array &$profile, User $user): void
    {
        if ($user->current_job_title) {
            $profile[] = "• Current role: {$user->current_job_title}";
        }

        if ($user->current_company) {
            $profile[] = "• Current company: {$user->current_company}";
        }

        if ($user->industry) {
            $profile[] = "• Industry: {$user->industry}";
        }

        if ($user->skills && is_array($user->skills)) {
            $skillsText = $this->processArrayToString($user->skills);
            if ($skillsText) {
                $profile[] = "• Skills: {$skillsText}";
            }
        }

        if ($user->career_summary) {
            $profile[] = "• Career summary: {$user->career_summary}";
        }
    }

    /**
     * Process an array of mixed types into a comma-separated string
     */
    private function processArrayToString(array $items): ?string
    {
        $processedItems = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                // If it's an array, json encode it or extract meaningful values
                $processedItems[] = json_encode($item);
            } elseif (is_string($item) && !empty(trim($item))) {
                $processedItems[] = trim($item);
            } elseif (!is_null($item)) {
                // Convert other types to string
                $processedItems[] = (string) $item;
            }
        }

        return !empty($processedItems) ? implode(', ', $processedItems) : null;
    }

    private function addPreferences(array &$profile, User $user): void
    {
        if ($user->preferred_job_type) {
            $profile[] = "• Preferred job type: {$user->preferred_job_type}";
        }

        $this->addBooleanPreferences($profile, $user);
        $this->addSalaryExpectations($profile, $user);
    }

    private function addBooleanPreferences(array &$profile, User $user): void
    {
        if ($user->remote_work_preference !== null) {
            $remote = $user->remote_work_preference ? 'Yes' : 'No';
            $profile[] = "• Remote work preference: {$remote}";
        }

        if ($user->willing_to_relocate !== null) {
            $relocate = $user->willing_to_relocate ? 'Yes' : 'No';
            $profile[] = "• Willing to relocate: {$relocate}";
        }

        if ($user->open_to_management !== null) {
            $management = $user->open_to_management ? 'Yes' : 'No';
            $profile[] = "• Open to management roles: {$management}";
        }
    }

    private function addSalaryExpectations(array &$profile, User $user): void
    {
        if ($user->salary_expectation_min || $user->salary_expectation_max) {
            $currency = $user->currency ?? 'USD';
            $min = $user->salary_expectation_min ? number_format($user->salary_expectation_min) : '?';
            $max = $user->salary_expectation_max ? number_format($user->salary_expectation_max) : '?';
            $profile[] = "• Salary expectation: {$min}-{$max} {$currency}";
        }
    }

    private function addEducationInfo(array &$profile, User $user): void
    {
        if ($user->highest_education) {
            $profile[] = "• Education: {$user->highest_education}";
        }

        if ($user->field_of_study) {
            $profile[] = "• Field of study: {$user->field_of_study}";
        }

        if ($user->languages && is_array($user->languages)) {
            $languagesText = $this->processArrayToString($user->languages);
            if ($languagesText) {
                $profile[] = "• Languages: {$languagesText}";
            }
        }
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
