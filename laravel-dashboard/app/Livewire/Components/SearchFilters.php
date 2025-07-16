<?php

namespace App\Livewire\Components;

use Livewire\Component;

class SearchFilters extends Component
{
    public $search = '';
    public $companyFilter = '';
    public $locationFilter = '';
    public $dateFromFilter = '';
    public $dateToFilter = '';
    public $datePreset = ''; // New property for date presets
    public $viewedStatusFilter = ''; // New filter for viewed status
    public $perPage = 10;

    public $companies = [];
    public $locations = [];
    public $showPerPage = true;
    public $showDateFilters = true;
    public $showCompanyFilter = true;
    public $title = 'Search & Filters';
    public $scopedCompanyId = null; // For company-specific filtering

    protected $queryString = [
        'search' => ['except' => ''],
        'companyFilter' => ['except' => ''],
        'locationFilter' => ['except' => ''],
        'dateFromFilter' => ['except' => ''],
        'dateToFilter' => ['except' => ''],
        'datePreset' => ['except' => ''],
        'viewedStatusFilter' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function mount($companies = [], $locations = [], $options = [])
    {
        $this->companies = $companies;
        $this->locations = $locations;
        $this->showPerPage = $options['showPerPage'] ?? true;
        $this->showDateFilters = $options['showDateFilters'] ?? true;
        $this->showCompanyFilter = $options['showCompanyFilter'] ?? true;
        $this->scopedCompanyId = $options['scopedCompanyId'] ?? null;
        $this->title = $options['title'] ?? 'Search & Filters';

        // Initialize from URL parameters
        $this->search = request()->get('search', '');
        $this->companyFilter = request()->get('companyFilter', '');
        $this->locationFilter = request()->get('locationFilter', '');
        $this->dateFromFilter = request()->get('dateFromFilter', '');
        $this->dateToFilter = request()->get('dateToFilter', '');
        $this->datePreset = request()->get('datePreset', '');
        $this->viewedStatusFilter = request()->get('viewedStatusFilter', '');
        $this->perPage = request()->get('perPage', 10);

        // Dispatch initial values to parent components
        $this->dispatch('filterUpdated', [
            'property' => 'mount',
            'value' => null,
            'filters' => [
                'search' => $this->search,
                'companyFilter' => $this->scopedCompanyId ? null : $this->companyFilter,
                'locationFilter' => $this->locationFilter,
                'dateFromFilter' => $this->dateFromFilter,
                'dateToFilter' => $this->dateToFilter,
                'viewedStatusFilter' => $this->viewedStatusFilter,
                'perPage' => $this->perPage,
                'scopedCompanyId' => $this->scopedCompanyId,
            ]
        ]);
    }

    public function clearFilters()
    {
        $this->search = '';
        // Don't clear companyFilter if we have a scopedCompanyId (company details page)
        if (!$this->scopedCompanyId) {
            $this->companyFilter = '';
        }
        $this->locationFilter = '';
        $this->dateFromFilter = '';
        $this->dateToFilter = '';
        $this->datePreset = '';
        $this->viewedStatusFilter = '';
        $this->perPage = 10;

        $this->dispatch('filtersCleared');
    }

    public function updated($propertyName)
    {
        $this->dispatch('filterUpdated', [
            'property' => $propertyName,
            'value' => $this->$propertyName,
            'filters' => [
                'search' => $this->search,
                'companyFilter' => $this->scopedCompanyId ? null : $this->companyFilter, // Use scoped company if available
                'locationFilter' => $this->locationFilter,
                'dateFromFilter' => $this->dateFromFilter,
                'dateToFilter' => $this->dateToFilter,
                'viewedStatusFilter' => $this->viewedStatusFilter,
                'perPage' => $this->perPage,
                'scopedCompanyId' => $this->scopedCompanyId, // Add scoped company ID
            ]
        ]);
    }

    public function setDatePreset($preset)
    {
        $this->datePreset = $preset;

        switch ($preset) {
            case 'last_24_hours':
                $this->dateFromFilter = now()->subDay()->format('Y-m-d');
                $this->dateToFilter = now()->format('Y-m-d');
                break;
            case 'last_week':
                $this->dateFromFilter = now()->subWeek()->format('Y-m-d');
                $this->dateToFilter = now()->format('Y-m-d');
                break;
            case 'last_month':
                $this->dateFromFilter = now()->subMonth()->format('Y-m-d');
                $this->dateToFilter = now()->format('Y-m-d');
                break;
            case 'last_3_months':
                $this->dateFromFilter = now()->subMonths(3)->format('Y-m-d');
                $this->dateToFilter = now()->format('Y-m-d');
                break;
            default:
                $this->dateFromFilter = '';
                $this->dateToFilter = '';
                break;
        }

        // Trigger filter update
        $this->dispatch('filterUpdated', [
            'property' => 'datePreset',
            'value' => $this->datePreset,
            'filters' => [
                'search' => $this->search,
                'companyFilter' => $this->scopedCompanyId ? null : $this->companyFilter,
                'locationFilter' => $this->locationFilter,
                'dateFromFilter' => $this->dateFromFilter,
                'dateToFilter' => $this->dateToFilter,
                'viewedStatusFilter' => $this->viewedStatusFilter,
                'perPage' => $this->perPage,
                'scopedCompanyId' => $this->scopedCompanyId,
            ]
        ]);
    }

    public function render()
    {
        return view('livewire.components.search-filters');
    }
}
