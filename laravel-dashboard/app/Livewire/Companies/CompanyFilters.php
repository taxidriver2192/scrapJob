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
        $this->perPage = request()->get('perPage', 10);

        $this->loadFilterOptions();
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

    public function updated($propertyName)
    {
        // Emit filter update when any filter changes
        $this->emitFilters();
    }

    public function emitFilters()
    {
        $filters = [
            'search' => $this->search,
            'cityFilter' => $this->cityFilter,
            'statusFilter' => $this->statusFilter,
            'hasVatFilter' => $this->hasVatFilter,
            'hasJobsFilter' => $this->hasJobsFilter,
            'minEmployeesFilter' => $this->minEmployeesFilter,
            'perPage' => $this->perPage,
        ];

        $this->dispatch('companyFilterUpdated', ['filters' => $filters]);
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->cityFilter = '';
        $this->statusFilter = '';
        $this->hasVatFilter = '';
        $this->hasJobsFilter = '';
        $this->minEmployeesFilter = '';
        $this->perPage = 10;

        $this->dispatch('filtersCleared');
        $this->emitFilters();
    }

    public function render()
    {
        return view('livewire.companies.company-filters');
    }
}
