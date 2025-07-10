<?php

namespace App\Livewire\Components;

use Livewire\Component;
use App\Models\JobPosting;

class JobTable extends Component
{
    public $perPage = 10;
    public $page = 1;
    public $sortField = 'posted_date';
    public $sortDirection = 'desc';
    public $showActions = true;
    public $showRating = true;
    public $columns = [];
    public $title = 'Jobs';

    // Current filters
    public $search = '';
    public $companyFilter = '';
    public $locationFilter = '';
    public $dateFromFilter = '';
    public $dateToFilter = '';

    // Modal state
    public $selectedJobId = null;
    public $showModal = false;

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
    ];

    public function mount($options = [])
    {
        $this->showActions = $options['showActions'] ?? true;
        $this->showRating = $options['showRating'] ?? true;
        $this->title = $options['title'] ?? 'Jobs';
        $this->columns = $options['columns'] ?? [
            'title' => 'Title',
            'company' => 'Company',
            'location' => 'Location',
            'posted_date' => 'Posted Date'
        ];

        // Initialize filters from URL parameters
        $this->page = request()->get('page', 1);
        $this->search = request()->get('search', '');
        $this->companyFilter = request()->get('companyFilter', '');
        $this->locationFilter = request()->get('locationFilter', '');
        $this->dateFromFilter = request()->get('dateFromFilter', '');
        $this->dateToFilter = request()->get('dateToFilter', '');
        $this->perPage = request()->get('perPage', 10);
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
        $this->selectedJobId = $jobId;
        $this->showModal = true;

        // Dispatch event to open the modal
        $this->dispatch('openJobModal', ['jobId' => $jobId]);
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
            $this->companyFilter = $data['filters']['companyFilter'] ?? '';
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
            $query->whereHas('company', function ($q) {
                $q->where('name', $this->companyFilter);
            });
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
        $query->orderBy($this->sortField, $this->sortDirection);

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

        return view('livewire.components.job-table', [
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
}
