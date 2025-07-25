<?php

namespace App\Livewire\Companies;

use Livewire\Component;
use App\Models\Company;

class CompanyFilters extends Component
{
    public $locations = [];
    public $options = [];

    // Filter values
    public $search = '';
    public $cityFilter = '';
    public $statusFilter = '';
    public $hasVatFilter = '';
    public $hasJobsFilter = '';
    public $minEmployeesFilter = '';
    public $maxEmployeesFilter = '';
    public $perPage = 10;

    // Available filter options
    public $statusOptions = [];
    public $vatOptions = [
        '' => 'All Companies',
        'with_vat' => 'With VAT Number',
        'without_vat' => 'Without VAT Number'
    ];
    public $jobsOptions = [
        '' => 'All Companies',
        'with_jobs' => 'With Job Postings',
        'with_open_jobs' => 'With Open Job Postings',
        'without_jobs' => 'Without Job Postings'
    ];
    public $employeeRangeOptions = [
        '' => 'Any Size',
        '1' => '1+ Employees',
        '10' => '10+ Employees',
        '50' => '50+ Employees',
        '100' => '100+ Employees',
        '500' => '500+ Employees',
        '1000' => '1000+ Employees'
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'cityFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'hasVatFilter' => ['except' => ''],
        'hasJobsFilter' => ['except' => ''],
        'minEmployeesFilter' => ['except' => ''],
        'maxEmployeesFilter' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function mount($locations = [], $options = [])
    {
        $this->locations = $locations;
        $this->options = $options;

        // Initialize filters from URL parameters
        $this->search = request()->get('search', '');
        $this->cityFilter = request()->get('cityFilter', '');
        $this->statusFilter = request()->get('statusFilter', '');
        $this->hasVatFilter = request()->get('hasVatFilter', '');
        $this->hasJobsFilter = request()->get('hasJobsFilter', '');
        $this->minEmployeesFilter = request()->get('minEmployeesFilter', '');
        $this->maxEmployeesFilter = request()->get('maxEmployeesFilter', '');
        $this->perPage = request()->get('perPage', 10);

        $this->loadFilterOptions();

        // Dispatch initial values to parent components
        $this->dispatch('filterUpdated', [
            'property' => 'mount',
            'value' => null,
            'filters' => [
                'search' => $this->search,
                'cityFilter' => $this->cityFilter,
                'statusFilter' => $this->statusFilter,
                'hasVatFilter' => $this->hasVatFilter,
                'hasJobsFilter' => $this->hasJobsFilter,
                'minEmployeesFilter' => $this->minEmployeesFilter,
                'maxEmployeesFilter' => $this->maxEmployeesFilter,
                'perPage' => $this->perPage,
            ]
        ]);
    }

    public function loadFilterOptions()
    {
        // Load status options from database
        $statuses = Company::whereNotNull('status')
            ->distinct()
            ->pluck('status')
            ->toArray();

        $this->statusOptions = ['' => 'All Statuses'];
        foreach ($statuses as $status) {
            $this->statusOptions[$status] = ucfirst($status);
        }
    }

    public function updated($propertyName, $value = null)
    {
        // Handle the property update similar to SearchFilters
        $this->dispatch('filterUpdated', [
            'property' => $propertyName,
            'value' => $this->$propertyName ?? $value,
            'filters' => [
                'search' => $this->search,
                'cityFilter' => $this->cityFilter,
                'statusFilter' => $this->statusFilter,
                'hasVatFilter' => $this->hasVatFilter,
                'hasJobsFilter' => $this->hasJobsFilter,
                'minEmployeesFilter' => $this->minEmployeesFilter,
                'maxEmployeesFilter' => $this->maxEmployeesFilter,
                'perPage' => $this->perPage,
            ]
        ]);
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->cityFilter = '';
        $this->statusFilter = '';
        $this->hasVatFilter = '';
        $this->hasJobsFilter = '';
        $this->minEmployeesFilter = '';
        $this->maxEmployeesFilter = '';
        $this->perPage = 10;

        $this->dispatch('filtersCleared');
        
        // Dispatch the updated filters
        $this->dispatch('filterUpdated', [
            'property' => 'clearFilters',
            'value' => null,
            'filters' => [
                'search' => $this->search,
                'cityFilter' => $this->cityFilter,
                'statusFilter' => $this->statusFilter,
                'hasVatFilter' => $this->hasVatFilter,
                'hasJobsFilter' => $this->hasJobsFilter,
                'minEmployeesFilter' => $this->minEmployeesFilter,
                'maxEmployeesFilter' => $this->maxEmployeesFilter,
                'perPage' => $this->perPage,
            ]
        ]);
    }

    public function render()
    {
        return view('livewire.companies.company-filters');
    }
}
