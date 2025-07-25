<?php

namespace App\Livewire\Jobs;

use Livewire\Component;
use App\Models\JobPosting;
use App\Models\JobRating;
use App\Models\UserJobView;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JobTable extends Component
{
    public $perPage = 10;
    public $page = 1;
    public $sortField = 'posted_date';
    public $sortDirection = 'desc';
    public $showActions = true;
    public $showRating = true;
    public $showDetailedRatings = false;
    public $title = 'Jobs';
    public $linkToDetailsPage = false; // New option to link to dedicated page instead of modal

    // New configuration structure
    public $tableConfig = [];
    public $enabledColumns = [];
    public $ratingColumns = [];
    public $regularColumns = [];

    // Current filters
    public $search = '';
    public $companyFilter = '';
    public $locationFilter = ''; // Keep for backward compatibility
    public $regionFilter = '';
    public $skillsFilter = []; // Array for multiple skills
    public $dateFromFilter = '';
    public $dateToFilter = '';
    public $viewedStatusFilter = '';
    public $ratingStatusFilter = '';
    public $jobStatusFilter = 'open'; // Default to showing open jobs only

    // Modal state
    public $selectedJobId = null;
    public $showModal = false;
    public $jobId = null; // URL parameter for modal

    // Bulk selection state
    public $selectedJobs = [];
    public $selectAll = false;
    public $showBulkActions = false;

    protected $listeners = [
        'filterUpdated' => 'handleFilterUpdate',
        'filtersCleared' => 'handleFiltersCleared',
    ];

    protected $queryString = [
        'page' => ['except' => 1],
        'sortField' => ['except' => 'posted_date'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 10],
        'search' => ['except' => ''],
        'companyFilter' => ['except' => ''],
        'locationFilter' => ['except' => ''],
        'regionFilter' => ['except' => ''],
        'skillsFilter' => ['except' => []],
        'dateFromFilter' => ['except' => ''],
        'dateToFilter' => ['except' => ''],
        'viewedStatusFilter' => ['except' => ''],
        'ratingStatusFilter' => ['except' => ''],
        'jobStatusFilter' => ['except' => 'open'],
        'jobId' => ['except' => null],
    ];

    public function mount($options = [], $tableConfig = [], $jobId = null)
    {
        // Set jobId if passed from parent
        if ($jobId) {
            $this->jobId = $jobId;
        } else {
            $urlJobId = request()->get('jobId');
            if ($urlJobId) {
                $this->jobId = $urlJobId;
            }
        }

        // Handle legacy options format or new tableConfig format
        if (!empty($tableConfig)) {
            $this->tableConfig = $tableConfig;
            $this->title = $tableConfig['title'] ?? 'Jobs';
            $this->showActions = $tableConfig['showActions'] ?? true;
            $this->showRating = $tableConfig['showRating'] ?? true;
            $this->showDetailedRatings = $tableConfig['showDetailedRatings'] ?? false;
            $this->linkToDetailsPage = $tableConfig['linkToDetailsPage'] ?? false;

            // Process columns configuration
            $this->processColumnConfiguration();
        } else {
            // Legacy format for backward compatibility
            $this->showActions = $options['showActions'] ?? true;
            $this->showRating = $options['showRating'] ?? true;
            $this->showDetailedRatings = $options['showDetailedRatings'] ?? false;
            $this->title = $options['title'] ?? 'Jobs';
            $this->regularColumns = $options['columns'] ?? [
                'title' => 'Title',
                'company' => 'Company',
                'city' => 'City',
                'zipcode' => 'Zip Code',
                'posted_date' => 'Posted Date'
            ];
        }

        // Initialize filters from URL parameters or tableConfig
        $this->page = request()->get('page', 1);
        $this->search = request()->get('search', '');
        $this->companyFilter = request()->get('companyFilter', '');
        $this->locationFilter = request()->get('locationFilter', '');
        $this->regionFilter = request()->get('regionFilter', '');
        $this->dateFromFilter = request()->get('dateFromFilter', '');
        $this->dateToFilter = request()->get('dateToFilter', '');
        $this->viewedStatusFilter = request()->get('viewedStatusFilter', '');
        $this->ratingStatusFilter = request()->get('ratingStatusFilter', '');
        $this->perPage = request()->get('perPage', 10);

        // Override companyFilter if specified in tableConfig
        if (isset($this->tableConfig['companyFilter'])) {
            $this->companyFilter = $this->tableConfig['companyFilter'];
        }
    }

    private function processColumnConfiguration()
    {
        $this->enabledColumns = [];
        $this->ratingColumns = [];
        $this->regularColumns = [];

        if (isset($this->tableConfig['columns'])) {
            foreach ($this->tableConfig['columns'] as $field => $config) {
                if ($config['enabled']) {
                    $this->enabledColumns[$field] = $config['label'];

                    if ($config['type'] === 'rating') {
                        $this->ratingColumns[$field] = $config['label'];
                    } else {
                        $this->regularColumns[$field] = $config['label'];
                    }
                }
            }
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->dispatch('sortUpdated', [
            'field' => $this->sortField,
            'direction' => $this->sortDirection
        ]);
    }

    public function viewJobRating($jobId)
    {
        if ($this->linkToDetailsPage) {
            // Navigate to dedicated job details page
            return redirect()->route('job.details', ['jobId' => $jobId]);
        } else {
            // Simply dispatch to parent to update URL parameter
            // Let the parent handle all the modal logic
            $this->dispatch('updateJobId', $jobId);
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedJobId = null;
    }

    public function handleFilterUpdate($data)
    {
        if (isset($data['filters'])) {
            $this->search = $data['filters']['search'] ?? '';
            // Use scopedCompanyId if available, otherwise use companyFilter
            if (isset($data['filters']['scopedCompanyId']) && $data['filters']['scopedCompanyId']) {
                $this->companyFilter = $data['filters']['scopedCompanyId'];
            } else {
                $this->companyFilter = $data['filters']['companyFilter'] ?? '';
            }
            $this->locationFilter = $data['filters']['locationFilter'] ?? '';
            $this->regionFilter = $data['filters']['regionFilter'] ?? '';
            $this->skillsFilter = $data['filters']['skillsFilter'] ?? [];
            $this->dateFromFilter = $data['filters']['dateFromFilter'] ?? '';
            $this->dateToFilter = $data['filters']['dateToFilter'] ?? '';
            $this->viewedStatusFilter = $data['filters']['viewedStatusFilter'] ?? '';
            $this->ratingStatusFilter = $data['filters']['ratingStatusFilter'] ?? '';
            $this->jobStatusFilter = $data['filters']['jobStatusFilter'] ?? 'open';
            $this->perPage = $data['filters']['perPage'] ?? 10;

            // Reset to first page when filters change
            $this->page = 1;
        }
    }

    public function handleFiltersCleared()
    {
        $this->search = '';
        // Don't clear companyFilter if it's set via tableConfig (company details page)
        if (!isset($this->tableConfig['companyFilter'])) {
            $this->companyFilter = '';
        }
        $this->locationFilter = '';
        $this->regionFilter = '';
        $this->skillsFilter = []; // Clear skills filter
        $this->dateFromFilter = '';
        $this->dateToFilter = '';
        $this->viewedStatusFilter = '';
        $this->ratingStatusFilter = '';
        $this->jobStatusFilter = 'open';
        $this->perPage = 10;
        $this->page = 1;
    }

    public function render()
    {
        // Build query based on current filters
        $query = JobPosting::with(['company', 'jobRatings']);

        // Apply search filter
        if (!empty($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('company', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply company filter
        if (!empty($this->companyFilter)) {
            // Check if it's a numeric company ID or company name
            if (is_numeric($this->companyFilter)) {
                $query->where('company_id', $this->companyFilter);
            } else {
                $query->whereHas('company', function ($q) {
                    $q->where('name', $this->companyFilter);
                });
            }
        }

        // Apply region filter (new implementation)
        if (!empty($this->regionFilter)) {
            $this->applyRegionFilter($query, $this->regionFilter);
        }

        // Apply skills filter
        if (!empty($this->skillsFilter) && is_array($this->skillsFilter)) {
            $query->where(function($q) {
                foreach ($this->skillsFilter as $skill) {
                    // Extract skill name from format "Skill Name (count)"
                    $skillName = preg_replace('/\s*\(\d+\)$/', '', $skill);
                    $q->orWhereJsonContains('skills', $skillName);
                }
            });
        }

        // Apply date filters
        if (!empty($this->dateFromFilter)) {
            $query->whereDate('posted_date', '>=', $this->dateFromFilter);
        }

        if (!empty($this->dateToFilter)) {
            $query->whereDate('posted_date', '<=', $this->dateToFilter);
        }

        // Apply viewed status filter (only for authenticated users)
        if (!empty($this->viewedStatusFilter) && Auth::check()) {
            $userId = Auth::id();

            if ($this->viewedStatusFilter === 'viewed') {
                // Show only jobs that have been viewed
                $query->whereExists(function ($subQuery) use ($userId) {
                    $subQuery->select(DB::raw(1))
                        ->from('user_job_views')
                        ->whereColumn('user_job_views.job_id', 'job_postings.job_id')
                        ->where('user_job_views.user_id', $userId);
                });
            } elseif ($this->viewedStatusFilter === 'not_viewed') {
                // Show only jobs that have NOT been viewed
                $query->whereNotExists(function ($subQuery) use ($userId) {
                    $subQuery->select(DB::raw(1))
                        ->from('user_job_views')
                        ->whereColumn('user_job_views.job_id', 'job_postings.job_id')
                        ->where('user_job_views.user_id', $userId);
                });
            }
        }

        // Apply rating status filter
        if (!empty($this->ratingStatusFilter)) {
            if ($this->ratingStatusFilter === 'rated') {
                // Show only jobs that have ratings
                $query->whereHas('jobRatings');
            } elseif ($this->ratingStatusFilter === 'not_rated') {
                // Show only jobs that don't have ratings
                $query->whereDoesntHave('jobRatings');
            }
        }

        // Apply job status filter
        if ($this->jobStatusFilter === 'open') {
            $query->whereNull('job_post_closed_date');
        } elseif ($this->jobStatusFilter === 'closed') {
            $query->whereNotNull('job_post_closed_date');
        }
        // If 'both' is selected, don't add any filter (show all jobs)

        // Apply sorting
        if (in_array($this->sortField, ['overall_score', 'location_score', 'tech_score', 'team_size_score', 'leadership_score'])) {
            // Sorting by rating fields - join with job_ratings table
            $query->leftJoin('job_ratings', 'job_postings.job_id', '=', 'job_ratings.job_id')
                  ->select('job_postings.*')
                  ->orderBy("job_ratings.{$this->sortField}", $this->sortDirection)
                  ->orderBy('job_postings.posted_date', 'desc'); // Secondary sort
        } else {
            // Regular sorting
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        // Load jobs with pagination
        $jobs = $query->paginate($this->perPage, ['*'], 'page', $this->page);

        // Append current filters to pagination links
        $jobs->appends([
            'page' => $this->page,
            'search' => $this->search,
            'companyFilter' => $this->companyFilter,
            'regionFilter' => $this->regionFilter,
            'skillsFilter' => $this->skillsFilter,
            'dateFromFilter' => $this->dateFromFilter,
            'dateToFilter' => $this->dateToFilter,
            'viewedStatusFilter' => $this->viewedStatusFilter,
            'ratingStatusFilter' => $this->ratingStatusFilter,
            'jobStatusFilter' => $this->jobStatusFilter,
            'perPage' => $this->perPage,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ]);

        return view('livewire.jobs.job-table', [
            'jobs' => $jobs,
            'totalResults' => $jobs->total(),
        ]);
    }

    public function gotoPage($page, $pageName = 'page')
    {
        $this->setPage($page, $pageName);
    }

    public function nextPage($pageName = 'page')
    {
        $this->setPage($this->getPage($pageName) + 1, $pageName);
    }

    public function previousPage($pageName = 'page')
    {
        $this->setPage($this->getPage($pageName) - 1, $pageName);
    }

    public function setPage($page, $pageName = 'page')
    {
        $this->page = $page;
    }

    public function getPage($pageName = 'page')
    {
        return $this->page ?? 1;
    }

    // Helper methods for ratings
    public function getRatingTooltip($job, $type)
    {
        if (!$job->jobRatings || $job->jobRatings->isEmpty()) {
            return "This job hasn't been rated yet. Click to get an AI-powered analysis.";
        }

        $rating = $job->jobRatings->first();
        $criteria = $rating->criteria;

        if (is_string($criteria)) {
            $criteria = json_decode($criteria, true) ?: [];
        }

        switch ($type) {
            case 'overall_score':
                return "Overall match score based on combined analysis of location, technical skills, team size, and leadership requirements.";
            case 'location_score':
                return data_get($criteria, 'location', 'Location preference analysis based on your settings and job location.');
            case 'tech_score':
                return data_get($criteria, 'tech_match', 'Technical skills match based on job requirements and your expertise.');
            case 'team_size_score':
                return data_get($criteria, 'company_fit', 'Company culture and team size analysis based on your preferences.');
            case 'leadership_score':
                return data_get($criteria, 'seniority_fit', 'Leadership and seniority level analysis based on job requirements.');
            default:
                return "AI-powered job rating analysis.";
        }
    }

    public function getRatingScore($job, $type)
    {
        if (!$job->jobRatings || $job->jobRatings->isEmpty()) {
            return null;
        }

        $rating = $job->jobRatings->first();
        return data_get($rating, $type, 0);
    }

    public function getRatingColor($score)
    {
        if ($score === null) return 'zinc';
        if ($score >= 80) return 'green';
        if ($score >= 60) return 'yellow';
        if ($score >= 40) return 'orange';
        return 'red';
    }

    /**
     * Check if a job has been viewed by the current authenticated user
     */
    public function isJobViewed($jobId): bool
    {
        if (!Auth::check()) {
            return false;
        }

        return UserJobView::hasUserViewed(Auth::id(), $jobId);
    }

    /**
     * Toggle job selection
     */
    public function toggleJobSelection($jobId)
    {
        if (in_array($jobId, $this->selectedJobs)) {
            $this->selectedJobs = array_diff($this->selectedJobs, [$jobId]);
        } else {
            $this->selectedJobs[] = $jobId;
        }

        $this->updateBulkActionsVisibility();
        $this->selectAll = false; // Reset select all when individual items are toggled
    }

    /**
     * Toggle select all jobs on current page
     */
    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            // Select all jobs on current page - get current page jobs
            $currentPageJobIds = $this->getCurrentPageJobIds();
            $this->selectedJobs = array_unique(array_merge($this->selectedJobs, $currentPageJobIds));
        } else {
            // Deselect all jobs on current page
            $currentPageJobIds = $this->getCurrentPageJobIds();
            $this->selectedJobs = array_diff($this->selectedJobs, $currentPageJobIds);
        }

        $this->updateBulkActionsVisibility();
    }

    /**
     * Get job IDs for current page
     */
    private function getCurrentPageJobIds()
    {
        // Build the same query as in render() to get current page job IDs
        $query = JobPosting::with(['company', 'jobRatings']);

        // Apply all the same filters as in render()
        if (!empty($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('company', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($this->companyFilter)) {
            if (is_numeric($this->companyFilter)) {
                $query->where('company_id', $this->companyFilter);
            } else {
                $query->whereHas('company', function ($q) {
                    $q->where('name', $this->companyFilter);
                });
            }
        }

        // Apply region filter (new implementation)
        if (!empty($this->regionFilter)) {
            $this->applyRegionFilter($query, $this->regionFilter);
        }

        // Apply skills filter
        if (!empty($this->skillsFilter) && is_array($this->skillsFilter)) {
            $query->where(function($q) {
                foreach ($this->skillsFilter as $skill) {
                    // Extract skill name from format "Skill Name (count)"
                    $skillName = preg_replace('/\s*\(\d+\)$/', '', $skill);
                    $q->orWhereJsonContains('skills', $skillName);
                }
            });
        }

        if (!empty($this->dateFromFilter)) {
            $query->whereDate('posted_date', '>=', $this->dateFromFilter);
        }

        if (!empty($this->dateToFilter)) {
            $query->whereDate('posted_date', '<=', $this->dateToFilter);
        }

        if (!empty($this->viewedStatusFilter) && Auth::check()) {
            $userId = Auth::id();

            if ($this->viewedStatusFilter === 'viewed') {
                $query->whereExists(function ($subQuery) use ($userId) {
                    $subQuery->select(DB::raw(1))
                        ->from('user_job_views')
                        ->whereColumn('user_job_views.job_id', 'job_postings.job_id')
                        ->where('user_job_views.user_id', $userId);
                });
            } elseif ($this->viewedStatusFilter === 'not_viewed') {
                $query->whereNotExists(function ($subQuery) use ($userId) {
                    $subQuery->select(DB::raw(1))
                        ->from('user_job_views')
                        ->whereColumn('user_job_views.job_id', 'job_postings.job_id')
                        ->where('user_job_views.user_id', $userId);
                });
            }
        }

        // Apply sorting
        if (in_array($this->sortField, ['overall_score', 'location_score', 'tech_score', 'team_size_score', 'leadership_score'])) {
            $query->leftJoin('job_ratings', 'job_postings.job_id', '=', 'job_ratings.job_id')
                  ->select('job_postings.*')
                  ->orderBy("job_ratings.{$this->sortField}", $this->sortDirection)
                  ->orderBy('job_postings.posted_date', 'desc');
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        // Get current page jobs
        $jobs = $query->paginate($this->perPage, ['*'], 'page', $this->page);

        return $jobs->pluck('job_id')->toArray();
    }

    /**
     * Clear all selections
     */
    public function clearSelection()
    {
        $this->selectedJobs = [];
        $this->selectAll = false;
        $this->updateBulkActionsVisibility();
    }

    /**
     * Update bulk actions visibility
     */
    private function updateBulkActionsVisibility()
    {
        $this->showBulkActions = count($this->selectedJobs) > 0;
    }

    /**
     * Queue selected jobs for AI rating
     */
    public function queueSelectedJobsForRating()
    {
        if (empty($this->selectedJobs)) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Please select at least one job to queue for rating.'
            ]);
            return;
        }

        if (!Auth::check()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You must be logged in to queue jobs for rating.'
            ]);
            return;
        }

        $queuedCount = 0;
        $skippedCount = 0;

        foreach ($this->selectedJobs as $jobId) {
            // Check if job already has an AI rating for this user
            $existingRating = JobRating::where('job_id', $jobId)
                ->where('user_id', Auth::id())
                ->first();

            if ($existingRating) {
                $skippedCount++;
                continue;
            }

            // Check if job is already queued
            $existingQueue = \App\Models\JobQueue::where('job_id', $jobId)
                ->whereIn('status_code', [\App\Models\JobQueue::STATUS_PENDING, \App\Models\JobQueue::STATUS_IN_PROGRESS])
                ->first();

            if ($existingQueue) {
                $skippedCount++;
                continue;
            }

            // Add to queue
            $queueItem = \App\Models\JobQueue::create([
                'job_id' => $jobId,
                'user_id' => Auth::id(),
                'status_code' => \App\Models\JobQueue::STATUS_PENDING,
                'queued_at' => now(),
            ]);

            // Dispatch the job for background processing
            \App\Jobs\ProcessAiJobRating::dispatch($queueItem->queue_id);

            $queuedCount++;
        }

        $message = "Queued {$queuedCount} jobs for AI rating.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} jobs were skipped (already rated or queued).";
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message
        ]);

        $this->clearSelection();
    }

    /**
     * Check if a job is selected
     */
    public function isJobSelected($jobId): bool
    {
        return in_array($jobId, $this->selectedJobs);
    }

    /**
     * Check if a job has been rated by the current authenticated user
     */
    public function isJobRated($jobId): bool
    {
        if (!Auth::check()) {
            return false;
        }

        return JobRating::where('job_id', $jobId)
            ->where('user_id', Auth::id())
            ->exists();
    }

    /**
     * Check if a job posting has been closed
     */
    public function isJobClosed($jobId): bool
    {
        $job = JobPosting::find($jobId);
        return $job && !is_null($job->job_post_closed_date);
    }

    public function getUnratedSelectedJobsCount()
    {
        if (!Auth::check() || empty($this->selectedJobs)) {
            return 0;
        }

        $userId = Auth::id();
        $unratedCount = 0;

        foreach ($this->selectedJobs as $jobId) {
            // Check if this job has been rated by the current user
            $hasRating = \App\Models\JobRating::where('job_id', $jobId)
                ->where('user_id', $userId)
                ->exists();

            if (!$hasRating) {
                $unratedCount++;
            }
        }

        return $unratedCount;
    }

    private function applyRegionFilter($query, $regionScope)
    {
        // Define regional data matching SearchFilters.php
        $regionalData = [
            "København & Frederiksberg" => [
                "zip_ranges" => [[1000, 2470]],
                "municipalities" => ["København", "Frederiksberg"]
            ],
            "Vestegnen" => [
                "zip_ranges" => [[2600, 2690]],
                "municipalities" => ["Glostrup", "Brøndby", "Rødovre", "Albertslund", "Vallensbæk", "Taastrup", "Ishøj", "Hedehusene", "Hvidovre", "Greve", "Solrød"]
            ],
            "Nordsjælland" => [
                "zip_ranges" => [[2800, 2990], [3000, 3699]],
                "municipalities" => ["Lyngby-Taarbæk", "Gentofte", "Rudersdal", "Hørsholm", "Fredensborg", "Helsingør", "Gribskov", "Hillerød", "Allerød", "Frederikssund", "Egedal", "Furesø", "Halsnæs"]
            ],
            "Bornholm" => [
                "zip_ranges" => [[3700, 3790]],
                "municipalities" => ["Bornholm"]
            ],
            "Sjælland" => [
                "zip_ranges" => [[4000, 4990]]
            ],
            "Fyn & Øer" => [
                "zip_ranges" => [[5000, 5999]]
            ],
            "Syd- & Sønderjylland" => [
                "zip_ranges" => [[6000, 6999]]
            ],
            "Midtjylland" => [
                "zip_ranges" => [[7000, 8999]]
            ],
            "Nordjylland" => [
                "zip_ranges" => [[9000, 9999]]
            ]
        ];

        if (!isset($regionalData[$regionScope])) {
            return;
        }

        $region = $regionalData[$regionScope];

        $query->where(function($q) use ($region) {
            // Filter by zip code ranges
            if (isset($region['zip_ranges'])) {
                foreach ($region['zip_ranges'] as $range) {
                    $q->orWhereBetween('zipcode', [$range[0], $range[1]]);
                }
            }

            // Filter by municipalities if available
            if (isset($region['municipalities'])) {
                foreach ($region['municipalities'] as $municipality) {
                    $q->orWhere('city', 'like', "%{$municipality}%");
                }
            }
        });
    }
}
