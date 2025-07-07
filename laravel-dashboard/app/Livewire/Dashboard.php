<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\JobPosting;
use App\Models\Company;
use App\Models\JobRating;
use App\Models\JobQueue;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    use WithPagination;

    // Dashboard stats
    public $totalJobs;
    public $totalCompanies;
    public $totalRatings;
    public $queuedJobs;
    public $avgScore;

    // Search and filter properties
    public $search = '';
    public $companyFilter = '';
    public $locationFilter = '';
    public $dateFromFilter = '';
    public $dateToFilter = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    // Autocomplete data
    public $companies = [];
    public $locations = [];

    protected $queryString = [
        'search', 'companyFilter', 'locationFilter',
        'dateFromFilter', 'dateToFilter', 'sortField', 'sortDirection'
    ];

    public function mount()
    {
        $this->loadDashboardData();
        $this->loadFilterOptions();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCompanyFilter()
    {
        $this->resetPage();
    }

    public function updatingLocationFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFromFilter()
    {
        $this->resetPage();
    }

    public function updatingDateToFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->companyFilter = '';
        $this->locationFilter = '';
        $this->dateFromFilter = '';
        $this->dateToFilter = '';
        $this->resetPage();
    }

    public function loadDashboardData()
    {
        $this->totalJobs = JobPosting::count();
        $this->totalCompanies = Company::count();
        $this->totalRatings = JobRating::count();
        $this->queuedJobs = JobQueue::where('status_code', JobQueue::STATUS_PENDING)->count();

        $this->avgScore = JobRating::whereNotNull('overall_score')
            ->avg('overall_score');
    }

    public function loadFilterOptions()
    {
        $this->companies = Company::orderBy('name')->pluck('name', 'company_id')->toArray();
        $this->locations = JobPosting::whereNotNull('location')
            ->distinct()
            ->orderBy('location')
            ->pluck('location')
            ->toArray();
    }

    public function getFilteredJobs()
    {
        return JobPosting::with(['company', 'jobRatings'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%')
                      ->orWhereHas('company', function ($companyQuery) {
                          $companyQuery->where('name', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->when($this->companyFilter, function ($query) {
                $query->whereHas('company', function ($companyQuery) {
                    $companyQuery->where('name', 'like', '%' . $this->companyFilter . '%');
                });
            })
            ->when($this->locationFilter, function ($query) {
                $query->where('location', 'like', '%' . $this->locationFilter . '%');
            })
            ->when($this->dateFromFilter, function ($query) {
                $query->whereDate('posted_date', '>=', $this->dateFromFilter);
            })
            ->when($this->dateToFilter, function ($query) {
                $query->whereDate('posted_date', '<=', $this->dateToFilter);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        $jobs = $this->getFilteredJobs();

        return view('livewire.dashboard', [
            'jobs' => $jobs
        ]);
    }
}
