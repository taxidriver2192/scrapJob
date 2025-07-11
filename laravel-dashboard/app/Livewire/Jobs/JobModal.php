<?php

namespace App\Livewire\Jobs;

use Livewire\Component;
use App\Models\JobRating;
use App\Models\JobPosting;
use Flux\Flux;

class JobModal extends Component
{
    public $jobPosting = null;
    public $rating = null;
    public $currentIndex = 0;
    public $total = 0;

    // Cache navigation state to avoid repeated queries
    public $canGoToPrevious = false;
    public $canGoToNext = false;

    protected $listeners = [
        'openJobModal' => 'openModal',
        'refreshJobModal' => 'refreshModal',
        'closeJobModal' => 'closeModal'
    ];

    public function openModal($data)
    {
        // Handle both old format (rating object) and new format (jobId)
        if (isset($data['jobId'])) {
            // Load the job posting
            $this->jobPosting = JobPosting::where('job_id', $data['jobId'])
                ->with('company')
                ->first();

            // Try to load existing job rating (optional)
            $this->rating = JobRating::where('job_id', $data['jobId'])->first();

            if ($this->jobPosting) {
                // Calculate current index and total
                $this->total = JobPosting::count();
                $this->currentIndex = JobPosting::where('job_id', '<=', $data['jobId'])->count() - 1;

                // Cache navigation state
                $this->updateNavigationState();
            }
        } else {
            // Old format - handle rating object
            $this->rating = $data;
            $this->jobPosting = $this->rating->jobPosting ?? null;
            $this->currentIndex = $data['currentIndex'] ?? 0;
            $this->total = $data['total'] ?? 0;

            // Cache navigation state
            $this->updateNavigationState();
        }

        if ($this->jobPosting) {
            Flux::modal('job-details-modal')->show();
            // Trigger map initialization if coordinates are available
            if ($this->jobPosting->lat && $this->jobPosting->lon) {
                $this->dispatch('refreshJobModal', $this->jobPosting->job_id, $this->currentIndex, $this->total);
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
    }

    public function closeModal()
    {
        Flux::modal('job-details-modal')->close();
        $this->jobPosting = null;
        $this->rating = null;
        $this->currentIndex = 0;
        $this->total = 0;
        $this->canGoToPrevious = false;
        $this->canGoToNext = false;
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
            $this->jobPosting = $previousJob;

            // Try to load rating for this job (optional)
            $this->rating = JobRating::where('job_id', $previousJob->job_id)->first();

            // Update index and navigation state
            $this->currentIndex = max(0, $this->currentIndex - 1);
            $this->updateNavigationState();

            // Trigger map update if coordinates are available
            if ($this->jobPosting->lat && $this->jobPosting->lon) {
                $this->dispatch('refreshJobModal', $this->jobPosting->job_id, $this->currentIndex, $this->total);
            }
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
            $this->jobPosting = $nextJob;

            // Try to load rating for this job (optional)
            $this->rating = JobRating::where('job_id', $nextJob->job_id)->first();

            // Update index and navigation state
            $this->currentIndex = min($this->total - 1, $this->currentIndex + 1);
            $this->updateNavigationState();

            // Trigger map update if coordinates are available
            if ($this->jobPosting->lat && $this->jobPosting->lon) {
                $this->dispatch('refreshJobModal', $this->jobPosting->job_id, $this->currentIndex, $this->total);
            }
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

    public function render()
    {
        return view('livewire.jobs.job-modal');
    }
}
