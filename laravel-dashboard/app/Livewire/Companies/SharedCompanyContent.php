<?php

namespace App\Livewire\Companies;

use Livewire\Component;
use App\Models\Company;
use App\Models\JobPosting;
use Illuminate\Support\Facades\Log;

class SharedCompanyContent extends Component
{
    public $company;
    public $companyId;
    public $showBackButton = false;
    public $showNavigation = false;
    public $currentIndex = null;
    public $total = null;
    // Data for job components (reusing dashboard components)
    public $companies = [];
    public $locations = [];
    public $tableConfig = [];
    public $jobId = null;
    public $relatedJobs = []; // Initialize as empty array
    public $jobFilters = [
        'search' => '',
        'locationFilter' => '',
        'perPage' => 10,
    ];

    protected $queryString = ['jobId'];

    protected $listeners = [
        'companyUpdated' => 'refreshCompany',
        'navigateToCompany' => 'loadCompany',
        'modalClosed' => 'handleModalClosed',
    ];

    public function mount($companyId = null, $company = null, $showBackButton = false, $showNavigation = false, $currentIndex = null, $total = null)
    {
        $this->companyId = $companyId;
        $this->company = $company;
        $this->showBackButton = $showBackButton;
        $this->showNavigation = $showNavigation;
        $this->currentIndex = $currentIndex;
        $this->total = $total;

        // Initialize jobId from URL parameter
        $this->jobId = request()->get('jobId');

        if ($this->companyId && !$this->company) {
            $this->loadCompany($this->companyId);
        }

        if ($this->company) {
            $this->setupJobComponents();
        }
    }

    public function loadCompany($companyId)
    {
        try {
            $this->company = Company::find($companyId);
            $this->companyId = $companyId;

            if ($this->company) {
                $this->setupJobComponents();
            }
        } catch (\Exception $e) {
            Log::error("Error loading company: " . $e->getMessage());
            $this->company = null;
        }
    }

    public function setupJobComponents()
    {
        // Setup data for the search-filters component
        $this->companies = collect([$this->company]); // Single company for context
        $this->locations = JobPosting::where('company_id', $this->company->company_id)
            ->whereNotNull('location')
            ->distinct()
            ->orderBy('location')
            ->pluck('location')
            ->toArray();

        // Setup table configuration for job-table component
        $this->tableConfig = [
            'title' => 'Job Postings for ' . $this->company->name,
            'showActions' => false,
            'linkToDetailsPage' => false, // Use modal instead of linking to job detail pages
            'companyFilter' => $this->company->company_id, // Filter jobs by this company
            'columns' => [
                'title' => ['enabled' => true, 'label' => 'Job Title', 'type' => 'regular'],
                'location' => ['enabled' => true, 'label' => 'Location', 'type' => 'regular'],
                'created_at' => ['enabled' => true, 'label' => 'Posted Date', 'type' => 'date'],
                'employment_type' => ['enabled' => true, 'label' => 'Type', 'type' => 'regular'],
                'apply_url' => ['enabled' => true, 'label' => 'Apply', 'type' => 'link'],
            ]
        ];

        // Load related jobs for the custom job management section
        $this->loadRelatedJobs();
    }

    public function loadRelatedJobs()
    {
        if ($this->company) {
            $this->relatedJobs = JobPosting::where('company_id', $this->company->company_id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }
    }

    public function refreshCompany()
    {
        if ($this->companyId) {
            $this->loadCompany($this->companyId);
        }
    }

    public function goBackToDashboard()
    {
        return redirect()->route('companies');
    }

    public function previousCompany()
    {
        $this->dispatch('navigatePrevious');
    }

    public function nextCompany()
    {
        $this->dispatch('navigateNext');
    }

    public function viewJob($jobId)
    {
        return redirect()->route('job.details', ['jobId' => $jobId]);
    }

    public function updatedJobFilters()
    {
        // Reload jobs when filters change
        $this->loadRelatedJobs();
    }

    public function handleModalClosed()
    {
        $this->jobId = null;
        Log::info('SharedCompanyContent modal closed - jobId reset to null');
    }

    public function render()
    {
        return view('livewire.companies.shared-company-content');
    }
}
