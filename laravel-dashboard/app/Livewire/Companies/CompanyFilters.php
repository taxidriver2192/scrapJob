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
    public $regionFilter = ''; // New regional filter
    public $statusFilter = '';
    public $hasVatFilter = '';
    public $hasJobsFilter = '';
    public $minEmployeesFilter = '';
    public $maxEmployeesFilter = '';
    public $perPage = 10;

    // Available filter options
    public $statusOptions = [];
    public $regionOptions = []; // New regional options
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
        'regionFilter' => ['except' => ''], // New regional filter in query string
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
        $this->regionFilter = request()->get('regionFilter', ''); // Initialize regional filter
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
                'regionFilter' => $this->regionFilter, // Dispatch regional filter
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

        // Load regional options
        $this->loadRegionalData();
    }

    public function loadRegionalData()
    {
        $regionalData = [
            [
                "macro_region" => "Region Hovedstaden",
                "scope" => "København & Frederiksberg",
                "zip_ranges" => [[1000, 2470]],
                "municipalities" => ["København", "Frederiksberg"]
            ],
            [
                "macro_region" => "Region Hovedstaden",
                "scope" => "Vestegnen",
                "zip_ranges" => [[2600, 2690]],
                "municipalities" => ["Glostrup", "Brøndby", "Rødovre", "Albertslund", "Vallensbæk", "Taastrup", "Ishøj", "Hedehusene", "Hvidovre", "Greve", "Solrød"]
            ],
            [
                "macro_region" => "Region Hovedstaden",
                "scope" => "Nordsjælland",
                "zip_ranges" => [[2800, 2990], [3000, 3699]],
                "municipalities" => ["Lyngby-Taarbæk", "Gentofte", "Rudersdal", "Hørsholm", "Fredensborg", "Helsingør", "Gribskov", "Hillerød", "Allerød", "Frederikssund", "Egedal", "Furesø", "Halsnæs"]
            ],
            [
                "macro_region" => "Region Hovedstaden",
                "scope" => "Bornholm",
                "zip_ranges" => [[3700, 3790]],
                "municipalities" => ["Bornholm"]
            ],
            [
                "macro_region" => "Region Sjælland",
                "scope" => "Sjælland",
                "zip_ranges" => [[4000, 4990]]
            ],
            [
                "macro_region" => "Region Syddanmark",
                "scope" => "Fyn & Øer",
                "zip_ranges" => [[5000, 5999]]
            ],
            [
                "macro_region" => "Region Syddanmark",
                "scope" => "Syd- & Sønderjylland",
                "zip_ranges" => [[6000, 6999]]
            ],
            [
                "macro_region" => "Region Midtjylland",
                "scope" => "Midtjylland",
                "zip_ranges" => [[7000, 8999]]
            ],
            [
                "macro_region" => "Region Nordjylland",
                "scope" => "Nordjylland",
                "zip_ranges" => [[9000, 9999]]
            ]
        ];

        $this->regionOptions = ['' => 'All Regions'];

        // Group by macro region for better organization
        $groupedRegions = [];
        foreach ($regionalData as $region) {
            $macroRegion = $region['macro_region'];
            if (!isset($groupedRegions[$macroRegion])) {
                $groupedRegions[$macroRegion] = [];
            }
            $groupedRegions[$macroRegion][] = $region;
        }

        // Create options with macro region as optgroup
        foreach ($groupedRegions as $macroRegion => $regions) {
            foreach ($regions as $region) {
                $key = $region['scope'];
                $this->regionOptions[$key] = $region['scope'] . ' (' . $macroRegion . ')';
            }
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
                'regionFilter' => $this->regionFilter, // Include regional filter
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
        $this->regionFilter = ''; // Clear regional filter
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
                'regionFilter' => $this->regionFilter, // Dispatch cleared regional filter
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
