<?php

namespace App\Livewire\Jobs;

use Livewire\Component;
use App\Models\JobRating;
use App\Models\JobPosting;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Log;

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
        Log::info('JobDetails mount - received jobId: ' . $jobId);

        if ($this->jobId) {
            $this->loadJobFromId($this->jobId);
        } else {
            Log::warning('JobDetails mount - no jobId provided');
            // Don't redirect here, let the view handle showing an error
        }
    }

    private function loadJobFromId($jobId)
    {
        Log::info('JobDetails loadJobFromId - called with jobId: ' . ($jobId ?? 'null'));

        // Load the job posting
        $this->jobPosting = JobPosting::where('job_id', $jobId)
            ->with('company')
            ->first();

        Log::info('JobDetails loadJobFromId - job found: ' . ($this->jobPosting ? 'yes (ID: ' . $this->jobPosting->job_id . ')' : 'no'));

        if (!$this->jobPosting) {
            // Job not found, log the error but let the view handle it
            Log::warning('JobDetails loadJobFromId - job not found for ID: ' . $jobId);
            return;
        }

        // Try to load existing job rating (optional)
        $this->rating = JobRating::where('job_id', $jobId)->first();

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
}
