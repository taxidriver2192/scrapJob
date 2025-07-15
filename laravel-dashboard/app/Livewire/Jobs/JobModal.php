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
        'jobIdUpdated' => 'handleJobIdUpdate'
    ];

    public function mount($jobId = null)
    {
        Log::info('JobModal mount - received jobId parameter: ' . $jobId);

        // Set jobId from parameter or URL
        $this->jobId = $jobId ?: request()->get('jobId');

        Log::info('JobModal mount - final jobId: ' . $this->jobId);

        // Check if there's a jobId and load the job
        if ($this->jobId) {
            Log::info('JobModal mount - loading job with ID: ' . $this->jobId . ' and setting showModal to true');
            $this->loadJobFromId($this->jobId);
            $this->showModal = true; // Open the modal when jobId is present
        } else {
            Log::info('JobModal mount - no jobId, not loading job');
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

        Log::info('JobModal closeModal - modal closed, showModal set to false');

        // Dispatch event to notify parent components that modal is closed
        $this->dispatch('modalClosed');
    }

    public function handleJobIdUpdate($jobId)
    {
        Log::info('JobModal handleJobIdUpdate - received jobId: ' . $jobId);

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
        Log::info('JobModal loadJobFromId - called with jobId: ' . ($jobId ?? 'null'));

        // Load the job posting
        $this->jobPosting = JobPosting::where('job_id', $jobId)
            ->with('company')
            ->first();

        Log::info('JobModal loadJobFromId - job found: ' . ($this->jobPosting ? 'yes (ID: ' . $this->jobPosting->job_id . ')' : 'no'));

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
            Log::info('JobModal loadJobFromId - showing modal, showModal set to: ' . ($this->showModal ? 'true' : 'false'));
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
        Log::info('JobModal updated - property: ' . $property . ', value: ' . ($value ?? 'null'));

        if ($property === 'jobId') {
            Log::info('JobModal updated - jobId changed to: ' . ($value ?? 'null'));
            if ($value) {
                Log::info('JobModal updated - loading job for new jobId: ' . $value);
                $this->loadJobFromId($value);
            }
        }
    }

    public function render()
    {
        Log::info('JobModal render - jobId: ' . ($this->jobId ?? 'null'));
        Log::info('JobModal render - job loaded: ' . ($this->jobPosting ? 'yes (ID: ' . $this->jobPosting->job_id . ')' : 'no'));
        return view('livewire.jobs.job-modal');
    }
}
