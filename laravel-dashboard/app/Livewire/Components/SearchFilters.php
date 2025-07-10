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
    public $perPage = 10;

    public $companies = [];
    public $locations = [];
    public $showPerPage = true;
    public $showDateFilters = true;
    public $title = 'Search & Filters';

    protected $queryString = [
        'search' => ['except' => ''],
        'companyFilter' => ['except' => ''],
        'locationFilter' => ['except' => ''],
        'dateFromFilter' => ['except' => ''],
        'dateToFilter' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function mount($companies = [], $locations = [], $options = [])
    {
        $this->companies = $companies;
        $this->locations = $locations;
        $this->showPerPage = $options['showPerPage'] ?? true;
        $this->showDateFilters = $options['showDateFilters'] ?? true;
        $this->title = $options['title'] ?? 'Search & Filters';

        // Initialize from URL parameters
        $this->search = request()->get('search', '');
        $this->companyFilter = request()->get('companyFilter', '');
        $this->locationFilter = request()->get('locationFilter', '');
        $this->dateFromFilter = request()->get('dateFromFilter', '');
        $this->dateToFilter = request()->get('dateToFilter', '');
        $this->perPage = request()->get('perPage', 10);

        // Dispatch initial values to parent components
        $this->dispatch('filterUpdated', [
            'property' => 'mount',
            'value' => null,
            'filters' => [
                'search' => $this->search,
                'companyFilter' => $this->companyFilter,
                'locationFilter' => $this->locationFilter,
                'dateFromFilter' => $this->dateFromFilter,
                'dateToFilter' => $this->dateToFilter,
                'perPage' => $this->perPage,
            ]
        ]);
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->companyFilter = '';
        $this->locationFilter = '';
        $this->dateFromFilter = '';
        $this->dateToFilter = '';
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
                'companyFilter' => $this->companyFilter,
                'locationFilter' => $this->locationFilter,
                'dateFromFilter' => $this->dateFromFilter,
                'dateToFilter' => $this->dateToFilter,
                'perPage' => $this->perPage,
            ]
        ]);
    }

    public function render()
    {
        return view('livewire.components.search-filters');
    }
}
