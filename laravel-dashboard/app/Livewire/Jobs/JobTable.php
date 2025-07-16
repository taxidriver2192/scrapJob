<?php

namespace App\Livewire\Jobs;

use Livewire\Component;
use App\Models\JobPosting;
use App\Models\JobRating;
use Illuminate\Support\Facades\Log;

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
    public $locationFilter = '';
    public $dateFromFilter = '';
    public $dateToFilter = '';

    // Modal state
    public $selectedJobId = null;
    public $showModal = false;
    public $jobId = null; // URL parameter for modal

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
        'dateFromFilter' => ['except' => ''],
        'dateToFilter' => ['except' => ''],
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
                'location' => 'Location',
                'posted_date' => 'Posted Date'
            ];
        }

        // Initialize filters from URL parameters or tableConfig
        $this->page = request()->get('page', 1);
        $this->search = request()->get('search', '');
        $this->companyFilter = request()->get('companyFilter', '');
        $this->locationFilter = request()->get('locationFilter', '');
        $this->dateFromFilter = request()->get('dateFromFilter', '');
        $this->dateToFilter = request()->get('dateToFilter', '');
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
            $this->dateFromFilter = $data['filters']['dateFromFilter'] ?? '';
            $this->dateToFilter = $data['filters']['dateToFilter'] ?? '';
            $this->perPage = $data['filters']['perPage'] ?? 10;

            // Reset to first page when filters change
            $this->page = 1;
        }
    }

    public function handleFiltersCleared()
    {
        $this->search = '';
        $this->companyFilter = '';
        $this->locationFilter = '';
        $this->dateFromFilter = '';
        $this->dateToFilter = '';
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

        // Apply location filter
        if (!empty($this->locationFilter)) {
            $query->where('location', 'like', "%{$this->locationFilter}%");
        }

        // Apply date filters
        if (!empty($this->dateFromFilter)) {
            $query->whereDate('posted_date', '>=', $this->dateFromFilter);
        }

        if (!empty($this->dateToFilter)) {
            $query->whereDate('posted_date', '<=', $this->dateToFilter);
        }

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
            'locationFilter' => $this->locationFilter,
            'dateFromFilter' => $this->dateFromFilter,
            'dateToFilter' => $this->dateToFilter,
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
}
