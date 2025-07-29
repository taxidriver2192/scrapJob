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
    public $cityOptions = []; // City options with counts
    public $vatOptions = [];
    public $jobsOptions = [];
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
        // Load city options with counts
        $this->loadCityOptions();

        // Load status options with counts
        $this->loadStatusOptions();

        // Load VAT options with counts
        $this->loadVatOptions();

        // Load jobs options with counts
        $this->loadJobsOptions();

        // Load regional options with counts
        $this->loadRegionalData();

        // Load city options with counts
        $this->loadCityOptions();
    }

    private function loadStatusOptions()
    {
        $totalQuery = $this->buildFilteredCompanyQuery(excludeStatus: true);
        $totalCompanies = $totalQuery->count();

        $this->statusOptions = ['' => 'All Statuses (' . $totalCompanies . ')'];

        // Get status counts
        $statusCounts = $this->buildFilteredCompanyQuery(excludeStatus: true)
            ->whereNotNull('status')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        foreach ($statusCounts as $status => $count) {
            $this->statusOptions[$status] = ucfirst($status) . ' (' . $count . ')';
        }
    }

    private function loadVatOptions()
    {
        $totalQuery = $this->buildFilteredCompanyQuery(excludeVat: true);
        $totalCompanies = $totalQuery->count();

        $withVatQuery = $this->buildFilteredCompanyQuery(excludeVat: true);
        $withVatCount = $withVatQuery->whereNotNull('vat')->where('vat', '!=', '')->count();

        $withoutVatCount = $totalCompanies - $withVatCount;

        $this->vatOptions = [
            '' => 'All Companies (' . $totalCompanies . ')',
            'with_vat' => 'With VAT Number (' . $withVatCount . ')',
            'without_vat' => 'Without VAT Number (' . $withoutVatCount . ')'
        ];
    }

    private function loadJobsOptions()
    {
        $totalQuery = $this->buildFilteredCompanyQuery(excludeJobs: true);
        $totalCompanies = $totalQuery->count();

        $withJobsQuery = $this->buildFilteredCompanyQuery(excludeJobs: true);
        $withJobsCount = $withJobsQuery->whereHas('jobPostings')->count();

        $withOpenJobsQuery = $this->buildFilteredCompanyQuery(excludeJobs: true);
        $withOpenJobsCount = $withOpenJobsQuery->whereHas('jobPostings', function($query) {
            $query->whereNull('job_post_closed_date');
        })->count();

        $withoutJobsCount = $totalCompanies - $withJobsCount;

        $this->jobsOptions = [
            '' => 'All Companies (' . $totalCompanies . ')',
            'with_jobs' => 'With Job Postings (' . $withJobsCount . ')',
            'with_open_jobs' => 'With Open Job Postings (' . $withOpenJobsCount . ')',
            'without_jobs' => 'Without Job Postings (' . $withoutJobsCount . ')'
        ];
    }

    private function getRegionalData()
    {
        return [
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
    }

    public function loadRegionalData()
    {
        $regionalData = $this->getRegionalData();

        $this->regionOptions = ['' => 'All Regions'];

        // Count companies for each region
        foreach ($regionalData as $region) {
            $scope = $region['scope'];

            // Count companies in this region
            $companyCount = $this->countCompaniesInRegion($region);

            // Create option with company count
            $label = $scope . ' (' . $companyCount . ' companies)';
            $this->regionOptions[$scope] = $label;
        }
    }

    private function countCompaniesInRegion($region)
    {
        // Build base query with current filters (excluding region to avoid circular filtering)
        $query = $this->buildFilteredCompanyQuery(excludeRegion: true);

        $zipRanges = $region['zip_ranges'] ?? [];
        $municipalities = $region['municipalities'] ?? [];

        $query->where(function($q) use ($zipRanges, $municipalities) {
            // Filter by zip ranges
            if (!empty($zipRanges)) {
                $q->where(function($zipQuery) use ($zipRanges) {
                    foreach ($zipRanges as $range) {
                        $zipQuery->orWhereBetween('zipcode', [$range[0], $range[1]]);
                    }
                });
            }

            // Also filter by municipalities if available
            if (!empty($municipalities)) {
                $q->orWhereIn('city', $municipalities);
            }
        });

        return $query->count();
    }

    private function buildFilteredCompanyQuery($excludeStatus = false, $excludeRegion = false, $excludeVat = false, $excludeJobs = false, $excludeCity = false)
    {
        $query = Company::query();

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhere('website', 'like', '%' . $this->search . '%');
            });
        }

        // Apply city filter (unless excluded)
        if (!$excludeCity && !empty($this->cityFilter)) {
            $query->where('city', 'like', '%' . $this->cityFilter . '%');
        }

        // Apply region filter (unless excluded)
        if (!$excludeRegion && !empty($this->regionFilter)) {
            $this->applyRegionFilterToCompanyQuery($query, $this->regionFilter);
        }

        // Apply status filter (unless excluded)
        if (!$excludeStatus && !empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        // Apply VAT filter (unless excluded)
        if (!$excludeVat && !empty($this->hasVatFilter)) {
            if ($this->hasVatFilter === 'with_vat') {
                $query->whereNotNull('vat')->where('vat', '!=', '');
            } elseif ($this->hasVatFilter === 'without_vat') {
                $query->where(function($q) {
                    $q->whereNull('vat')->orWhere('vat', '');
                });
            }
        }

        // Apply jobs filter (unless excluded)
        if (!$excludeJobs && !empty($this->hasJobsFilter)) {
            if ($this->hasJobsFilter === 'with_jobs') {
                $query->whereHas('jobPostings');
            } elseif ($this->hasJobsFilter === 'with_open_jobs') {
                $query->whereHas('jobPostings', function($jobQuery) {
                    $jobQuery->whereNull('job_post_closed_date');
                });
            } elseif ($this->hasJobsFilter === 'without_jobs') {
                $query->whereDoesntHave('jobPostings');
            }
        }

        // Apply employee count filters
        if (!empty($this->minEmployeesFilter)) {
            $query->where('employees', '>=', $this->minEmployeesFilter);
        }
        if (!empty($this->maxEmployeesFilter)) {
            $query->where('employees', '<=', $this->maxEmployeesFilter);
        }

        return $query;
    }

    private function applyRegionFilterToCompanyQuery($query, $regionScope)
    {
        $regionalData = $this->getRegionalData();
        $region = collect($regionalData)->firstWhere('scope', $regionScope);

        if (!$region) {
            return $query;
        }

        $zipRanges = $region['zip_ranges'] ?? [];
        $municipalities = $region['municipalities'] ?? [];

        $query->where(function($q) use ($zipRanges, $municipalities) {
            // Filter by zip ranges
            if (!empty($zipRanges)) {
                $q->where(function($zipQuery) use ($zipRanges) {
                    foreach ($zipRanges as $range) {
                        $zipQuery->orWhereBetween('zipcode', [$range[0], $range[1]]);
                    }
                });
            }

            // Also filter by municipalities if available
            if (!empty($municipalities)) {
                $q->orWhereIn('city', $municipalities);
            }
        });

        return $query;
    }

    private function loadCityOptions()
    {
        $totalQuery = $this->buildFilteredCompanyQuery(excludeCity: true);
        $totalCompanies = $totalQuery->count();

        $this->cityOptions = ['' => 'All Cities (' . $totalCompanies . ')'];

        // Get city counts
        $cityCounts = $this->buildFilteredCompanyQuery(excludeCity: true)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->selectRaw('city, COUNT(*) as count')
            ->groupBy('city')
            ->orderBy('city')
            ->pluck('count', 'city')
            ->toArray();

        foreach ($cityCounts as $city => $count) {
            $this->cityOptions[$city] = $city . ' (' . $count . ')';
        }
    }

    public function updated($propertyName, $value = null)
    {
        // Refresh filter options when relevant filters change
        $refreshTriggers = ['search', 'cityFilter', 'regionFilter', 'statusFilter', 'hasVatFilter', 'hasJobsFilter', 'minEmployeesFilter', 'maxEmployeesFilter'];
        if (in_array($propertyName, $refreshTriggers)) {
            $this->loadFilterOptions();
        }

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

        // Refresh filter options after clearing
        $this->loadFilterOptions();

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
        // Load filter options if not already loaded
        if (empty($this->statusOptions) || empty($this->vatOptions) || empty($this->jobsOptions) || empty($this->regionOptions) || empty($this->cityOptions)) {
            $this->loadFilterOptions();
        }

        return view('livewire.companies.company-filters');
    }
}
