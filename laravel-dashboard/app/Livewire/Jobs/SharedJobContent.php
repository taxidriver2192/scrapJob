<?php

namespace App\Livewire\Jobs;

use App\Models\JobPosting;
use App\Services\JobRatingService;
use App\Exceptions\OpenAiException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use App\Models\JobRating;

class SharedJobContent extends Component
{
    public $jobPosting;
    public $rating;
    public $currentIndex;
    public $total;
    public $showNavigation;
    public $showBackButton;

    protected $listeners = [
        'openJobModal' => 'openModal',
        'refreshJobModal' => 'refreshModal',
        'closeJobModal' => 'closeModal',
        'updateJobId' => 'handleJobIdUpdate',
        'previousRating' => 'previousRating',
        'nextRating' => 'nextRating',
        'refreshJobRating' => 'refreshRating',
    ];

    public function mount($jobPosting, $rating = null, $currentIndex = null, $total = null, $showNavigation = true, $showBackButton = false)
    {
        $this->jobPosting = $jobPosting;
        $this->rating = $rating;
        $this->currentIndex = $currentIndex;
        $this->total = $total;
        $this->showNavigation = $showNavigation;
        $this->showBackButton = $showBackButton;
    }

    public function previousRating()
    {
        $this->dispatch('previousRating');
    }

    public function nextRating()
    {
        $this->dispatch('nextRating');
    }

    public function goBackToDashboard()
    {
        $this->dispatch('goBackToDashboard');
    }

    public function rateJobWithAi()
    {
        // Add debugging
        // Basic validation logging (keep minimal)
        if (!Auth::check() || !$this->jobPosting || !data_get($this->jobPosting, 'job_id')) {
            return;
        }

        if (!Auth::check()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You must be logged in to rate jobs.'
            ]);
            return;
        }

        if (!$this->jobPosting || !data_get($this->jobPosting, 'job_id')) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Unable to rate job - job information not found.'
            ]);
            return;
        }

        try {
            // Show loading notification
            $this->dispatch('notify', [
                'type' => 'info',
                'message' => 'AI is analyzing this job based on your profile... This may take a moment.'
            ]);

            Log::info('Starting AI job rating', ['job_id' => data_get($this->jobPosting, 'job_id')]);

            // Get the job posting model
            $jobPosting = JobPosting::where('job_id', data_get($this->jobPosting, 'job_id'))->first();

            if (!$jobPosting) {
                Log::error('Job posting not found', ['job_id' => data_get($this->jobPosting, 'job_id')]);
                throw new OpenAiException('Job posting not found in database');
            }

            Log::info('Job posting found, creating rating service');

            // Rate the job using the AI service
            $jobRatingService = app(JobRatingService::class);
            $result = $jobRatingService->rateJobForUser($jobPosting);
            // Update the component's rating so the view shows the new AI rating
            $this->rating = $result;

            Log::info('AI rating completed', [
                'rating_id' => $result->id,
                'cost' => $result->cost,
                'tokens' => $result->total_tokens,
            ]);

            // Success notification
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Job successfully rated with AI! Check the AI Ratings page to see the detailed analysis.'
            ]);

            // Optionally refresh the parent component or redirect
            $this->dispatch('jobRated', ['jobId' => data_get($this->jobPosting, 'job_id')]);

        } catch (OpenAiException $e) {
            Log::error('OpenAI Exception', ['error' => $e->getMessage()]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'AI rating failed: ' . $e->getMessage()
            ]);
        } catch (\Exception $e) {
            Log::error('General exception', ['error' => $e->getMessage()]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'An error occurred while rating the job: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Refresh rating after AI analysis.
     * @param array $payload
     */
    public function refreshRating(array $payload): void
    {
        $jobId = data_get($payload, 'jobId');
        if ($jobId) {
            $this->rating = JobRating::where('job_id', $jobId)
                ->where('user_id', Auth::id())
                ->where('rating_type', 'ai_rating')
                ->first();
        }
    }

    public function canNavigatePrevious()
    {
        // This will be handled by the parent component
        return true;
    }

    public function canNavigateNext()
    {
        // This will be handled by the parent component
        return true;
    }

    public function render()
    {
        return view('livewire.jobs.shared-job-content');
    }
}
