<?php

namespace App\Livewire\Jobs;

use Livewire\Component;
use App\Models\JobRating;
use App\Models\JobPosting;
use App\Models\UserJobView;
use Flux\Flux;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class JobModal extends Component
{
    public $jobPosting = null;
    public $rating = null;
    public $currentIndex = 0;
    public $total = 0;
    public $jobId = null; // URL parameter for the job ID
    public $showModal = false; // Controls modal visibility

    // Filter parameters to scope navigation
    public $companyId = null;
    public $filterScope = []; // Store filter parameters from parent

    // Cache navigation state to avoid repeated queries
    public $canGoToPrevious = false;
    public $canGoToNext = false;

    protected $queryString = ['jobId']; // Enable URL parameter handling

    protected $listeners = [
        'openJobModal' => 'openModal',
        'refreshJobModal' => 'refreshModal',
        'closeJobModal' => 'closeModal',
        'updateJobId' => 'handleJobIdUpdate',
        'previousRating' => 'previousRating',
        'nextRating' => 'nextRating'
    ];

    public function mount($jobId = null, $companyId = null, $filterScope = [])
    {
        // Set filter parameters
        $this->companyId = $companyId;
        $this->filterScope = $filterScope;

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
        $this->jobId = null; // This will automatically remove the URL parameter
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

    /**
     * Called automatically when jobId property changes (from URL or programmatically)
     */
    public function updatedJobId($value)
    {
        if ($value) {
            // JobId was set - load the job and show modal
            $this->loadJobFromId($value);
            $this->showModal = true;
        } else {
            // JobId was cleared - hide modal
            $this->showModal = false;
            $this->jobPosting = null;
            $this->rating = null;
        }
    }

    /**
     * Build a filtered query based on current filter scope
     */
    private function getFilteredQuery()
    {
        $query = JobPosting::query();

        // Apply company filter - prioritize companyId over filterScope companyFilter
        if ($this->companyId) {
            $query->where('company_id', $this->companyId);
        } elseif (!empty($this->filterScope['companyFilter'])) {
            $companyFilter = $this->filterScope['companyFilter'];
            if (is_numeric($companyFilter)) {
                // If it's numeric, treat as company ID
                $query->where('company_id', $companyFilter);
            } else {
                // If it's text, search by company name
                $query->whereHas('company', function($companyQuery) use ($companyFilter) {
                    $companyQuery->where('name', 'like', '%' . $companyFilter . '%');
                });
            }
        }

        // Apply filters from filterScope
        if (!empty($this->filterScope)) {
            // Search filter
            if (!empty($this->filterScope['search'])) {
                $search = $this->filterScope['search'];
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%')
                      ->orWhereHas('company', function($companyQuery) use ($search) {
                          $companyQuery->where('name', 'like', '%' . $search . '%');
                      });
                });
            }

            // Location filter
            if (!empty($this->filterScope['locationFilter'])) {
                $query->where('location', 'like', '%' . $this->filterScope['locationFilter'] . '%');
            }

            // Date range filters
            if (!empty($this->filterScope['dateFromFilter'])) {
                $query->where('posted_date', '>=', $this->filterScope['dateFromFilter']);
            }

            if (!empty($this->filterScope['dateToFilter'])) {
                $query->where('posted_date', '<=', $this->filterScope['dateToFilter']);
            }
        }

        return $query;
    }

    private function updateNavigationState()
    {
        if (!$this->jobPosting || !$this->jobPosting->job_id) {
            $this->canGoToPrevious = false;
            $this->canGoToNext = false;
            return;
        }

        // Build the base query with filters
        $baseQuery = $this->getFilteredQuery();

        // Single query to check both directions at once with filters applied
        $navigationData = $baseQuery->selectRaw('
            EXISTS(SELECT 1 FROM job_postings jp WHERE jp.job_id < ?' .
            ($this->companyId ? ' AND jp.company_id = ?' : '') . ') as has_previous,
            EXISTS(SELECT 1 FROM job_postings jp WHERE jp.job_id > ?' .
            ($this->companyId ? ' AND jp.company_id = ?' : '') . ') as has_next
        ', array_filter([
            $this->jobPosting->job_id,
            $this->companyId,
            $this->jobPosting->job_id,
            $this->companyId
        ]))
        ->first();

        // Add null check to prevent the error
        if ($navigationData) {
            $this->canGoToPrevious = (bool) $navigationData->has_previous;
            $this->canGoToNext = (bool) $navigationData->has_next;
        } else {
            $this->canGoToPrevious = false;
            $this->canGoToNext = false;
        }
    }

    public function previousRating()
    {
        if (!$this->canGoToPrevious || !$this->jobPosting) {
            return;
        }

        $currentJobId = $this->jobPosting->job_id;

        // Find the previous job posting using filtered query
        $previousJob = $this->getFilteredQuery()
            ->where('job_id', '<', $currentJobId)
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

        // Find the next job posting using filtered query
        $nextJob = $this->getFilteredQuery()
            ->where('job_id', '>', $currentJobId)
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
            // Mark job as viewed if user is authenticated
            if (Auth::check()) {
                UserJobView::markAsViewed(Auth::id(), $jobId);
            }

            // Try to load existing job rating
            $this->rating = JobRating::where('job_id', $jobId)->first();
            Log::info('JobModal loadJobFromId - rating found: ' . ($this->rating ? 'yes' : 'no'));

            // Calculate current index and total using filtered query
            $filteredQuery = $this->getFilteredQuery();
            $this->total = $filteredQuery->count();
            $this->currentIndex = $filteredQuery->where('job_id', '<=', $jobId)->count() - 1;

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
        if ($property === 'jobId' && $value) {
            $this->loadJobFromId($value);
        }
    }

    public function render()
    {
        return view('livewire.jobs.job-modal');
    }
}
