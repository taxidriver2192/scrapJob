<?php

namespace App\Livewire\Companies;

use Livewire\Component;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class CompanyTable extends Component
{
    public $perPage = 10;
    public $page = 1;
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $title = 'Companies';
    public $linkToDetailsPage = false;

    // Configuration structure
    public $tableConfig = [];
    public $enabledColumns = [];
    public $regularColumns = [];

    // Current filters
    public $search = '';
    public $cityFilter = '';
    public $statusFilter = '';
    public $hasVatFilter = '';
    public $hasJobsFilter = '';
    public $minEmployeesFilter = '';
    public $maxEmployeesFilter = '';

    protected $listeners = [
        'filterUpdated' => 'handleFilterUpdate',
        'filtersCleared' => 'handleFiltersCleared',
    ];

    protected $queryString = [
        'page' => ['except' => 1],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
        'perPage' => ['except' => 10],
        'search' => ['except' => ''],
        'cityFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'hasVatFilter' => ['except' => ''],
        'hasJobsFilter' => ['except' => ''],
        'minEmployeesFilter' => ['except' => ''],
        'maxEmployeesFilter' => ['except' => ''],
    ];

    public function mount($tableConfig = [])
    {
        if (!empty($tableConfig)) {
            $this->tableConfig = $tableConfig;
            $this->title = $tableConfig['title'] ?? 'Companies';
            $this->linkToDetailsPage = $tableConfig['linkToDetailsPage'] ?? false;

            // Process columns configuration
            $this->processColumnConfiguration();
        }

        // Initialize filters from URL parameters
        $this->page = request()->get('page', 1);
        $this->search = request()->get('search', '');
        $this->cityFilter = request()->get('cityFilter', '');
        $this->statusFilter = request()->get('statusFilter', '');
        $this->hasVatFilter = request()->get('hasVatFilter', '');
        $this->hasJobsFilter = request()->get('hasJobsFilter', '');
        $this->minEmployeesFilter = request()->get('minEmployeesFilter', '');
        $this->maxEmployeesFilter = request()->get('maxEmployeesFilter', '');
        $this->perPage = request()->get('perPage', 10);
    }

    private function processColumnConfiguration()
    {
        $this->enabledColumns = [];
        $this->regularColumns = [];

        if (isset($this->tableConfig['columns'])) {
            foreach ($this->tableConfig['columns'] as $field => $config) {
                if ($config['enabled']) {
                    $this->enabledColumns[$field] = $config['label'];
                    if ($config['type'] === 'regular') {
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
            $this->sortDirection = $field === 'name' ? 'asc' : 'desc';
        }

        $this->page = 1;
    }

    public function viewCompany($companyId)
    {
        if ($this->linkToDetailsPage) {
            // Navigate to dedicated company details page
            return redirect()->route('company.details', ['companyId' => $companyId]);
        }
        // Could add modal functionality here if needed later
    }

    public function handleFilterUpdate($data)
    {
        if (isset($data['filters'])) {
            foreach ($data['filters'] as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
        $this->page = 1;
    }

    public function handleFiltersCleared()
    {
        $this->search = '';
        $this->cityFilter = '';
        $this->statusFilter = '';
        $this->hasVatFilter = '';
        $this->hasJobsFilter = '';
        $this->minEmployeesFilter = '';
        $this->maxEmployeesFilter = '';
        $this->perPage = 10;
        $this->page = 1;
    }

    public function render()
    {
        // Build query based on current filters
        $query = Company::withCount([
            'jobPostings',
            'jobPostings as open_jobs_count' => function ($query) {
                $query->whereNull('job_post_closed_date');
            },
            'jobPostings as closed_jobs_count' => function ($query) {
                $query->whereNotNull('job_post_closed_date');
            }
        ]);

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'LIKE', '%' . $this->search . '%')
                  ->orWhere('vat', 'LIKE', '%' . $this->search . '%')
                  ->orWhere('address', 'LIKE', '%' . $this->search . '%');
            });
        }

        // Apply city filter
        if (!empty($this->cityFilter)) {
            $query->where('city', 'LIKE', '%' . $this->cityFilter . '%');
        }

        // Apply status filter
        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        // Apply VAT filter
        if ($this->hasVatFilter === 'with_vat') {
            $query->whereNotNull('vat');
        } elseif ($this->hasVatFilter === 'without_vat') {
            $query->whereNull('vat');
        }

        // Apply job postings filter
        if ($this->hasJobsFilter === 'with_jobs') {
            $query->has('jobPostings');
        } elseif ($this->hasJobsFilter === 'with_open_jobs') {
            $query->whereHas('jobPostings', function ($q) {
                $q->whereNull('job_post_closed_date');
            });
        } elseif ($this->hasJobsFilter === 'without_jobs') {
            $query->doesntHave('jobPostings');
        }

        // Apply minimum employees filter
        if (!empty($this->minEmployeesFilter)) {
            $minEmployees = (int) $this->minEmployeesFilter;
            $query->where('employees', '>=', $minEmployees);
        }

        // Apply maximum employees filter
        if (!empty($this->maxEmployeesFilter)) {
            $maxEmployees = (int) $this->maxEmployeesFilter;
            $query->where('employees', '<=', $maxEmployees);
        }

        // Apply sorting
        if ($this->sortField === 'job_count') {
            $query->orderBy('job_postings_count', $this->sortDirection);
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        // Load companies with pagination
        $companies = $query->paginate($this->perPage, ['*'], 'page', $this->page);

        return view('livewire.companies.company-table', [
            'companies' => $companies,
            'totalResults' => $companies->total(),
        ]);
    }
}
