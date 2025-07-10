<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\JobPosting;
use App\Models\Company;
use App\Models\JobRating;
use App\Models\JobQueue;

class Dashboard extends Component
{
    // Dashboard stats
    public $totalJobs;
    public $totalCompanies;
    public $totalRatings;
    public $queuedJobs;
    public $avgScore;

    // Data for components
    public $companies = [];
    public $locations = [];

    // Current filters
    public $currentFilters = [
        'search' => '',
        'companyFilter' => '',
        'locationFilter' => '',
        'dateFromFilter' => '',
        'dateToFilter' => '',
        'perPage' => 10,
    ];

    protected $listeners = [
        'refreshJobTable' => 'updateFilters',
        'filterUpdated' => 'handleFilterUpdate',
    ];

    public function mount()
    {
        // Initialize filters from URL parameters
        $this->currentFilters = [
            'search' => request()->get('search', ''),
            'companyFilter' => request()->get('companyFilter', ''),
            'locationFilter' => request()->get('locationFilter', ''),
            'dateFromFilter' => request()->get('dateFromFilter', ''),
            'dateToFilter' => request()->get('dateToFilter', ''),
            'perPage' => request()->get('perPage', 10),
        ];

        $this->loadDashboardData();
        $this->loadComponentData();
    }

    public function loadDashboardData()
    {
        $this->totalJobs = JobPosting::count();
        $this->totalCompanies = Company::count();
        $this->totalRatings = JobRating::count();
        $this->queuedJobs = JobQueue::where('status_code', JobQueue::STATUS_PENDING)->count();

        $this->avgScore = JobRating::whereNotNull('overall_score')
            ->avg('overall_score');
    }    public function loadComponentData()
    {
        // Load data for child components
        $this->companies = Company::orderBy('name')->pluck('name', 'company_id')->toArray();
        $this->locations = JobPosting::whereNotNull('location')
            ->distinct()
            ->orderBy('location')
            ->pluck('location')
            ->toArray();
    }

    public function updateFilters($filters)
    {
        $this->currentFilters = array_merge($this->currentFilters, $filters);
    }

    public function handleFilterUpdate($data)
    {
        if (isset($data['filters'])) {
            $this->currentFilters = array_merge($this->currentFilters, $data['filters']);
        }
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
