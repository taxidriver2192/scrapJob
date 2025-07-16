<?php

namespace App\Livewire\Jobs;

use Livewire\Component;
use App\Models\JobRating;
use App\Models\JobPosting;
use Flux\Flux;
use Illuminate\Support\Facades\Log;

class JobModal extends Component
{
    public $jobPosting = null;
    public $rating = null;
    public $currentIndex = 0;
    public $total = 0;
    public $jobId = null; // URL parameter for the job ID
    public $showModal = false; // Controls modal visibility

    // Cache navigation state to avoid repeated queries
    public $canGoToPrevious = false;
    public $canGoToNext = false;

    protected $queryString = []; // Remove query string handling from modal

    protected $listeners = [
        'openJobModal' => 'openModal',
        'refreshJobModal' => 'refreshModal',
        'closeJobModal' => 'closeModal',
        'jobIdUpdated' => 'handleJobIdUpdate',
        'previousRating' => 'previousRating',
        'nextRating' => 'nextRating'
    ];

    public function mount($jobId = null)
    {
        // Set jobId from parameter or URL
        $this->jobId = $jobId ?: request()->get('jobId');

        // Check if there's a jobId and load the job
        if ($this->jobId) {
            $this->loadJobFromId($this->jobId);
            $this->showModal = true; // Open the modal when jobId is present
        } else {
            $this->showModal = false; // Ensure modal is closed when no jobId
        }
    }

    public function openModal($data)
    {
        // Handle both old format (rating object) and new format (jobId)
        if (isset($data['jobId'])) {
            // Load the job directly
            $this->loadJobFromId($data['jobId']);
        } else {
            // Old format - handle rating object
            $jobId = data_get($data, 'job_id') ?? data_get($data, 'jobPosting.job_id');
            if ($jobId) {
                $this->loadJobFromId($jobId);
            }
        }
    }

    public function refreshModal($jobId, $currentIndex, $total)
    {
        // Load the job posting
        $this->jobPosting = JobPosting::where('job_id', $jobId)
            ->with('company')
            ->first();

        // Try to load existing job rating (optional)
        $this->rating = JobRating::where('job_id', $jobId)->first();

        $this->currentIndex = $currentIndex;
        $this->total = $total;

        // Update navigation state
        $this->updateNavigationState();
    }    public function closeModal()
    {
        // Hide the modal and clear data
        $this->showModal = false;
        $this->jobId = null;
        $this->jobPosting = null;
        $this->rating = null;

        // Dispatch event to notify parent components that modal is closed
        $this->dispatch('modalClosed');
    }

    public function handleJobIdUpdate($jobId)
    {
        if ($jobId) {
            $this->jobId = $jobId;
            $this->loadJobFromId($jobId);
            $this->showModal = true;
        } else {
            // Close modal without dispatching modalClosed event to prevent loop
            $this->showModal = false;
            $this->jobId = null;
            $this->jobPosting = null;
            $this->rating = null;
            Log::info('JobModal handleJobIdUpdate - modal closed directly (no event dispatch)');
        }
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
            // Dispatch event to update the URL parameter in the parent
            $this->dispatch('updateJobId', $previousJob->job_id);
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
            // Dispatch event to update the URL parameter in the parent
            $this->dispatch('updateJobId', $nextJob->job_id);
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

    public function rateJob()
    {
        if (!$this->jobPosting || !$this->jobPosting->job_id) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Unable to rate job - job ID not found.'
            ]);
            return;
        }

        $jobId = $this->jobPosting->job_id;

        // Add your job rating logic here
        // This could trigger an API call to your AI rating service
        // or queue a job to process the rating

        // For now, just show a notification
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => "Job rating request submitted for job ID: {$jobId}. This may take a few moments to process."
        ]);

        // You could also close the modal and refresh the table
        // $this->closeModal();
        // $this->dispatch('refreshJobTable');
    }

    private function loadJobFromId($jobId)
    {
        // Load the job posting
        $this->jobPosting = JobPosting::where('job_id', $jobId)
            ->with('company')
            ->first();

        if ($this->jobPosting) {
            // Try to load existing job rating
            $this->rating = JobRating::where('job_id', $jobId)->first();
            Log::info('JobModal loadJobFromId - rating found: ' . ($this->rating ? 'yes' : 'no'));

            // Calculate current index and total
            $this->total = JobPosting::count();
            $this->currentIndex = JobPosting::where('job_id', '<=', $jobId)->count() - 1;

            // Cache navigation state
            $this->updateNavigationState();

            // Show the modal by setting showModal to true
            $this->showModal = true;
        } else {
            Log::error('JobModal loadJobFromId - job not found for jobId: ' . $jobId);
        }
    }

    // Delegated method to get job information from child component
    public function getJobInformation()
    {
        return $this->jobPosting;
    }

    // Delegated method to get rating data for child component
    public function getRatingData()
    {
        return $this->rating;
    }

    public function updated($property, $value)
    {
        if ($property === 'jobId') {
            if ($value) {
                $this->loadJobFromId($value);
            }
        }
    }

    public function render()
    {
        return view('livewire.jobs.job-modal');
    }
}
