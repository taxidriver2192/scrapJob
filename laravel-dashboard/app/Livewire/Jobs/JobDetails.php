<?php

namespace App\Livewire\Jobs;

use Livewire\Component;
use App\Models\JobRating;
use App\Models\JobPosting;
use App\Models\UserJobView;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.app')]
class JobDetails extends Component
{
    public $jobPosting = null;
    public $rating = null;
    public $currentIndex = 0;
    public $total = 0;
    public $jobId = null;

    // Cache navigation state to avoid repeated queries
    public $canGoToPrevious = false;
    public $canGoToNext = false;

    protected $listeners = [
        'previousRating' => 'previousRating',
        'nextRating' => 'nextRating',
        'goBackToDashboard' => 'goBackToDashboard'
    ];

    public function mount($jobId)
    {
        $this->jobId = $jobId;

        if ($this->jobId) {
            $this->loadJobFromId($this->jobId);
        }
        // Don't redirect here, let the view handle showing an error
    }

    private function loadJobFromId($jobId)
    {
        // Load the job posting
        $this->jobPosting = JobPosting::where('job_id', $jobId)
            ->with('company')
            ->first();

        if (!$this->jobPosting) {
            // Job not found, log the error but let the view handle it
            Log::warning('Job not found: ' . $jobId);
            return;
        }

        // Mark job as viewed if user is authenticated
        if (Auth::check()) {
            UserJobView::markAsViewed(Auth::id(), $jobId);
        }

        // Load regular manual rating
        $this->rating = JobRating::where('job_id', $jobId)
            ->where('rating_type', 'manual')
            ->first();

        // If no manual rating found, check for AI rating
        if (!$this->rating && Auth::check()) {
            $aiRating = JobRating::where('job_id', $jobId)
                ->where('user_id', Auth::id())
                ->where('rating_type', 'ai_rating')
                ->first();

            if ($aiRating) {
                $this->rating = $this->convertAiRatingToStandardFormat($aiRating);
            }
        }

        // Calculate current position and total for navigation
        $this->calculatePosition();

        // Update navigation state
        $this->updateNavigationState();
    }

    private function calculatePosition()
    {
        if (!$this->jobPosting) {
            return;
        }

        // Get total count of all jobs
        $this->total = JobPosting::count();

        // Get current position (1-based index)
        $this->currentIndex = JobPosting::where('job_id', '<', $this->jobPosting->job_id)->count();
    }

    private function updateNavigationState()
    {
        if (!$this->jobPosting || !$this->jobPosting->job_id) {
            $this->canGoToPrevious = false;
            $this->canGoToNext = false;
            return;
        }

        // Single query to check both directions at once
        $navigationData = JobPosting::selectRaw('
            EXISTS(SELECT 1 FROM job_postings WHERE job_id < ?) as has_previous,
            EXISTS(SELECT 1 FROM job_postings WHERE job_id > ?) as has_next
        ', [$this->jobPosting->job_id, $this->jobPosting->job_id])
        ->first();

        $this->canGoToPrevious = (bool) $navigationData->has_previous;
        $this->canGoToNext = (bool) $navigationData->has_next;
    }

    public function previousRating()
    {
        if (!$this->canGoToPrevious || !$this->jobPosting) {
            return;
        }

        $currentJobId = $this->jobPosting->job_id;

        // Find the previous job posting
        $previousJob = JobPosting::where('job_id', '<', $currentJobId)
            ->with('company')
            ->orderBy('job_id', 'desc')
            ->first();

        if ($previousJob) {
            // Navigate to the previous job details page
            return redirect()->route('job.details', ['jobId' => $previousJob->job_id]);
        }
    }

    public function nextRating()
    {
        if (!$this->canGoToNext || !$this->jobPosting) {
            return;
        }

        $currentJobId = $this->jobPosting->job_id;

        // Find the next job posting
        $nextJob = JobPosting::where('job_id', '>', $currentJobId)
            ->with('company')
            ->orderBy('job_id', 'asc')
            ->first();

        if ($nextJob) {
            // Navigate to the next job details page
            return redirect()->route('job.details', ['jobId' => $nextJob->job_id]);
        }
    }

    public function canNavigatePrevious()
    {
        return $this->canGoToPrevious;
    }

    public function canNavigateNext()
    {
        return $this->canGoToNext;
    }

    public function goBackToDashboard()
    {
        return redirect()->route('dashboard');
    }

    public function render()
    {
        Log::info('JobDetails render - called for jobId: ' . ($this->jobId ?? 'null'));
        Log::info('JobDetails render - jobPosting exists: ' . ($this->jobPosting ? 'yes' : 'no'));

        return view('livewire.jobs.job-details');
    }

    /**
     * Convert AI rating to standard format for display
     */
    private function convertAiRatingToStandardFormat(JobRating $aiRating)
    {
        // Parse the AI response to get scores
        $response = json_decode($aiRating->response, true);

        if (!$response) {
            return null;
        }

        // Create a fake JobRating-like structure for display
        return (object) [
            'job_id' => $aiRating->job_id,
            'overall_score' => $response['overall_score'] ?? 0,
            'location_score' => $response['scores']['location'] ?? 0,
            'tech_score' => $response['scores']['tech_match'] ?? 0,
            'team_size_score' => $response['scores']['company_fit'] ?? 0,
            'leadership_score' => $response['scores']['seniority_match'] ?? 0,
            'criteria' => json_encode($response['reasoning'] ?? []),
            'rated_at' => $aiRating->rated_at,
            'rating_type' => 'ai_rating', // Mark as AI rating
            'ai_confidence' => $response['confidence'] ?? 0,
        ];
    }
}
