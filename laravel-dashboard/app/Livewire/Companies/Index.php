<?php

namespace App\Livewire\Companies;

use Livewire\Component;
use App\Models\Company;
use App\Models\JobPosting;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.app')]
class Index extends Component
{
    // Dashboard stats
    public $totalCompanies;
    public $companiesWithVat;
    public $companiesWithJobs;
    public $avgEmployees;

    // Data for components
    public $locations = [];

    // Column configuration for companies table
    public $tableConfig = [
        'title' => 'Companies Dashboard',
        'showActions' => false,
        'linkToDetailsPage' => true, // Link to dedicated company pages
        'columns' => [
            // Company columns
            'name' => ['enabled' => true, 'label' => 'Company Name', 'type' => 'regular'],
            'vat' => ['enabled' => true, 'label' => 'VAT Number', 'type' => 'regular'],
            'city' => ['enabled' => true, 'label' => 'City', 'type' => 'regular'],
            'employees' => ['enabled' => true, 'label' => 'Employees', 'type' => 'regular'],
            'status' => ['enabled' => true, 'label' => 'Status', 'type' => 'regular'],
            'job_count' => ['enabled' => true, 'label' => 'Job Postings', 'type' => 'regular'],
        ]
    ];

    // Current filters
    public $currentFilters = [
        'search' => '',
        'cityFilter' => '',
        'statusFilter' => '',
        'hasVatFilter' => '',
        'hasJobsFilter' => '',
        'minEmployeesFilter' => '',
        'perPage' => 10,
    ];

    protected $listeners = [
        'refreshCompanyTable' => 'updateFilters',
        'companyFilterUpdated' => 'handleFilterUpdate',
    ];

    public function mount()
    {
        // Initialize filters from URL parameters
        $this->currentFilters = [
            'search' => request()->get('search', ''),
            'cityFilter' => request()->get('cityFilter', ''),
            'statusFilter' => request()->get('statusFilter', ''),
            'hasVatFilter' => request()->get('hasVatFilter', ''),
            'hasJobsFilter' => request()->get('hasJobsFilter', ''),
            'minEmployeesFilter' => request()->get('minEmployeesFilter', ''),
            'perPage' => request()->get('perPage', 10),
        ];

        $this->loadDashboardData();
        $this->loadComponentData();
    }

    public function loadDashboardData()
    {
        $this->totalCompanies = Company::count();
        $this->companiesWithVat = Company::whereNotNull('vat')->count();
        $this->companiesWithJobs = Company::has('jobPostings')->count();
        
        $this->avgEmployees = Company::whereNotNull('employees')
            ->where('employees', '>', 0)
            ->avg('employees');
    }

    public function loadComponentData()
    {
        // Load data for child components
        $this->locations = Company::whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
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
        return view('livewire.companies.index');
    }
}
