<?php

namespace App\Livewire\Components;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class SearchFilters extends Component
{
    public $search = '';
    public $companyFilter = '';
    public $locationFilter = ''; // Keep for backward compatibility
    public $regionFilter = ''; // New regional filter
    public $skillsFilter = []; // Array for multiple skills
    public $dateFromFilter = '';
    public $dateToFilter = '';
    public $datePreset = ''; // New property for date presets
    public $viewedStatusFilter = ''; // New filter for viewed status
    public $ratingStatusFilter = ''; // New filter for rating status
    public $favoritesStatusFilter = ''; // New filter for favorites status
    public $jobStatusFilter = 'open'; // New filter for job status (open/closed/both)
    public $perPage = 10;

    public $companies = [];
    public $locations = [];
    public $regionOptions = []; // Available regions for filtering
    public $regionDetails = []; // Region details for tooltips (zip ranges, municipalities)
    public $availableSkills = []; // Available skills for listbox
    public $companyOptions = []; // Available companies with counts
    public $viewedStatusOptions = []; // Viewed status options with counts
    public $ratingStatusOptions = []; // Rating status options with counts
    public $favoritesStatusOptions = []; // Favorites status options with counts
    public $jobStatusOptions = []; // Job status options with counts
    public $showPerPage = true;
    public $showDateFilters = true;
    public $showCompanyFilter = true;
    public $title = 'Search & Filters';
    public $scopedCompanyId = null; // For company-specific filtering

    protected $queryString = [
        'search' => ['except' => ''],
        'companyFilter' => ['except' => ''],
        'locationFilter' => ['except' => ''],
        'regionFilter' => ['except' => ''],
        'skillsFilter' => ['except' => []], // Array instead of string
        'dateFromFilter' => ['except' => ''],
        'dateToFilter' => ['except' => ''],
        'datePreset' => ['except' => ''],
        'viewedStatusFilter' => ['except' => ''],
        'ratingStatusFilter' => ['except' => ''],
        'favoritesStatusFilter' => ['except' => ''],
        'jobStatusFilter' => ['except' => 'open'],
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

        // Load available skills for autocomplete
        $this->loadAvailableSkills();

        // Load regional data
        $this->loadRegionalData();

        // Load company options with counts
        $this->loadCompanyOptions();

        // Load other filter options with counts
        $this->loadFilterOptions();

        // Initialize from URL parameters
        $this->search = request()->get('search', '');
        $this->companyFilter = request()->get('companyFilter', '');
        $this->locationFilter = request()->get('locationFilter', '');
        $this->regionFilter = request()->get('regionFilter', '');
        $this->skillsFilter = request()->get('skillsFilter', []) ?: [];
        $this->dateFromFilter = request()->get('dateFromFilter', '');
        $this->dateToFilter = request()->get('dateToFilter', '');
        $this->datePreset = request()->get('datePreset', '');
        $this->viewedStatusFilter = request()->get('viewedStatusFilter', '');
        $this->ratingStatusFilter = request()->get('ratingStatusFilter', '');
        $this->favoritesStatusFilter = request()->get('favoritesStatusFilter', '');
        $this->jobStatusFilter = request()->get('jobStatusFilter', 'open');
        $this->perPage = request()->get('perPage', 10);

        // Dispatch initial values to parent components
        $this->dispatch('filterUpdated', [
            'property' => 'mount',
            'value' => null,
            'filters' => [
                'search' => $this->search,
                'companyFilter' => $this->scopedCompanyId ? null : $this->companyFilter,
                'locationFilter' => $this->locationFilter,
                'regionFilter' => $this->regionFilter,
                'skillsFilter' => $this->skillsFilter, // Include skills filter
                'dateFromFilter' => $this->dateFromFilter,
                'dateToFilter' => $this->dateToFilter,
                'viewedStatusFilter' => $this->viewedStatusFilter,
                'ratingStatusFilter' => $this->ratingStatusFilter,
                'favoritesStatusFilter' => $this->favoritesStatusFilter,
                'jobStatusFilter' => $this->jobStatusFilter,
                'perPage' => $this->perPage,
                'scopedCompanyId' => $this->scopedCompanyId,
            ]
        ]);
    }

    public function loadAvailableSkills()
    {
        // Build base query with current filters (excluding skills to avoid circular filtering)
        $query = $this->buildFilteredJobQuery(excludeSkills: true);

        $jobPostings = $query->whereNotNull('skills')->get();

        // Count occurrences of each skill
        $skillCounts = [];
        foreach ($jobPostings as $job) {
            if (is_array($job->skills)) {
                foreach ($job->skills as $skill) {
                    if (!empty($skill)) {
                        $skillCounts[$skill] = ($skillCounts[$skill] ?? 0) + 1;
                    }
                }
            }
        }

        // Create array with skill names as keys and display labels as values
        $this->availableSkills = [];
        foreach (collect($skillCounts)->filter(fn($count) => $count > 0)->sortKeys() as $skill => $count) {
            $this->availableSkills[$skill] = $skill . ' (' . $count . ')';
        }
    }

    public function loadCompanyOptions()
    {
        // Build base query with current filters (excluding company to avoid circular filtering)
        $query = $this->buildFilteredJobQuery(excludeCompany: true);

        // Get companies with job counts using Eloquent relationships
        $companyCounts = $query->with('company')
            ->get()
            ->groupBy('company.name')
            ->map(function($jobs) {
                return $jobs->count();
            })
            ->filter(fn($count) => $count > 0)
            ->sortKeys()
            ->toArray();

        // Create options array with counts
        $this->companyOptions = ['' => 'All Companies'];
        foreach ($companyCounts as $companyName => $count) {
            // Store the company name as value and display name with count as label
            $this->companyOptions[$companyName] = $companyName . ' (' . $count . ' jobs)';
        }
    }

    public function loadFilterOptions()
    {
        // Load viewed status options with counts (only for authenticated users)
        if (Auth::check()) {
            $this->loadViewedStatusOptions();
        } else {
            $this->viewedStatusOptions = [
                '' => 'All Jobs',
                'viewed' => 'Viewed (0)',
                'not_viewed' => 'Not Viewed (0)'
            ];
        }

        // Load rating status options with counts
        $this->loadRatingStatusOptions();

        // Load favorites status options with counts (only for authenticated users)
        if (Auth::check()) {
            $this->loadFavoritesStatusOptions();
        } else {
            $this->favoritesStatusOptions = [
                '' => 'All Jobs',
                'favorited' => 'Favorited (0)',
                'not_favorited' => 'Not Favorited (0)'
            ];
        }

        // Load job status options with counts
        $this->loadJobStatusOptions();
    }

    private function loadViewedStatusOptions()
    {
        $totalQuery = $this->buildFilteredJobQuery(excludeViewed: true);
        $totalJobs = $totalQuery->count();

        $viewedQuery = $this->buildFilteredJobQuery(excludeViewed: true);
        $userId = Auth::id();

        // Use Eloquent whereHas instead of raw SQL
        $viewedCount = $viewedQuery->whereHas('userJobViews', function($query) use ($userId) {
            $query->where('user_id', $userId);
        })->count();

        $notViewedCount = $totalJobs - $viewedCount;

        $this->viewedStatusOptions = [
            '' => 'All Jobs (' . $totalJobs . ')',
            'viewed' => 'Viewed (' . $viewedCount . ')',
            'not_viewed' => 'Not Viewed (' . $notViewedCount . ')'
        ];
    }

    private function loadRatingStatusOptions()
    {
        $totalQuery = $this->buildFilteredJobQuery(excludeRating: true);
        $totalJobs = $totalQuery->count();

        $ratedQuery = $this->buildFilteredJobQuery(excludeRating: true);
        $ratedCount = $ratedQuery->whereHas('jobRatings')->count();

        $notRatedCount = $totalJobs - $ratedCount;

        $this->ratingStatusOptions = [
            '' => 'All Jobs (' . $totalJobs . ')',
            'rated' => 'Rated (' . $ratedCount . ')',
            'not_rated' => 'Not Rated (' . $notRatedCount . ')'
        ];
    }

    private function loadFavoritesStatusOptions()
    {
        $totalQuery = $this->buildFilteredJobQuery(excludeFavorites: true);
        $totalJobs = $totalQuery->count();

        $favoritedQuery = $this->buildFilteredJobQuery(excludeFavorites: true);
        $userId = Auth::id();

        // Use Eloquent whereHas instead of raw SQL
        $favoritedCount = $favoritedQuery->whereHas('userJobFavorites', function($query) use ($userId) {
            $query->where('user_id', $userId);
        })->count();

        $notFavoritedCount = $totalJobs - $favoritedCount;

        $this->favoritesStatusOptions = [
            '' => 'All Jobs (' . $totalJobs . ')',
            'favorited' => 'Favorited (' . $favoritedCount . ')',
            'not_favorited' => 'Not Favorited (' . $notFavoritedCount . ')'
        ];
    }

    private function loadJobStatusOptions()
    {
        $totalQuery = $this->buildFilteredJobQuery(excludeJobStatus: true);
        $totalJobs = $totalQuery->count();

        $openQuery = $this->buildFilteredJobQuery(excludeJobStatus: true);
        $openCount = $openQuery->whereNull('job_post_closed_date')->count();

        $closedQuery = $this->buildFilteredJobQuery(excludeJobStatus: true);
        $closedCount = $closedQuery->whereNotNull('job_post_closed_date')->count();

        $this->jobStatusOptions = [
            'open' => 'Open Jobs (' . $openCount . ')',
            'closed' => 'Closed Jobs (' . $closedCount . ')',
            'both' => 'All Jobs (' . $totalJobs . ')'
        ];
    }

    private function buildFilteredJobQuery($excludeSkills = false, $excludeRegion = false, $excludeCompany = false, $excludeViewed = false, $excludeRating = false, $excludeFavorites = false, $excludeJobStatus = false)
    {
        $query = \App\Models\JobPosting::query();

        // Apply job status filter first (unless excluded)
        if (!$excludeJobStatus) {
            if ($this->jobStatusFilter === 'closed') {
                $query->whereNotNull('job_post_closed_date');
            } elseif ($this->jobStatusFilter === 'both') {
                // No additional filter needed for both
            } else { // 'open' or default
                $query->whereNull('job_post_closed_date');
            }
        }

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhereHas('company', function($companyQuery) {
                      $companyQuery->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        // Apply company filter (unless excluded)
        if (!$excludeCompany && !empty($this->companyFilter) && !$this->scopedCompanyId) {
            $query->whereHas('company', function($companyQuery) {
                $companyQuery->where('name', $this->companyFilter);
            });
        }

        // Apply scoped company filter (unless excluded)
        if (!$excludeCompany && $this->scopedCompanyId) {
            $query->where('company_id', $this->scopedCompanyId);
        }

        // Apply region filter (unless excluded)
        if (!$excludeRegion && !empty($this->regionFilter)) {
            $this->applyRegionFilterToQuery($query, $this->regionFilter);
        }

        // Apply skills filter (unless excluded)
        if (!$excludeSkills && !empty($this->skillsFilter)) {
            foreach ($this->skillsFilter as $skill) {
                // Skill names are already clean (no counts), use directly
                $query->whereJsonContains('skills', $skill);
            }
        }

        // Apply date filters
        if (!empty($this->dateFromFilter)) {
            $query->whereDate('created_at', '>=', $this->dateFromFilter);
        }
        if (!empty($this->dateToFilter)) {
            $query->whereDate('created_at', '<=', $this->dateToFilter);
        }

        // Apply viewed status filter (unless excluded)
        if (!$excludeViewed && !empty($this->viewedStatusFilter) && Auth::check()) {
            $userId = Auth::id();
            if ($this->viewedStatusFilter === 'viewed') {
                // Use Eloquent whereHas instead of raw SQL
                $query->whereHas('userJobViews', function($subQuery) use ($userId) {
                    $subQuery->where('user_id', $userId);
                });
            } elseif ($this->viewedStatusFilter === 'not_viewed') {
                // Use Eloquent whereDoesntHave instead of raw SQL
                $query->whereDoesntHave('userJobViews', function($subQuery) use ($userId) {
                    $subQuery->where('user_id', $userId);
                });
            }
        }

        // Apply rating status filter (unless excluded)
        if (!$excludeRating && !empty($this->ratingStatusFilter)) {
            if ($this->ratingStatusFilter === 'rated') {
                $query->whereHas('jobRatings');
            } elseif ($this->ratingStatusFilter === 'not_rated') {
                $query->whereDoesntHave('jobRatings');
            }
        }

        // Apply favorites status filter (unless excluded)
        if (!$excludeFavorites && !empty($this->favoritesStatusFilter) && Auth::check()) {
            $userId = Auth::id();
            if ($this->favoritesStatusFilter === 'favorited') {
                // Use Eloquent whereHas instead of raw SQL
                $query->whereHas('userJobFavorites', function($subQuery) use ($userId) {
                    $subQuery->where('user_id', $userId);
                });
            } elseif ($this->favoritesStatusFilter === 'not_favorited') {
                // Use Eloquent whereDoesntHave instead of raw SQL
                $query->whereDoesntHave('userJobFavorites', function($subQuery) use ($userId) {
                    $subQuery->where('user_id', $userId);
                });
            }
        }

        return $query;
    }

    private function applyRegionFilterToQuery($query, $regionScope)
    {
        if (!isset($this->regionDetails[$regionScope])) {
            return $query;
        }

        $region = $this->regionDetails[$regionScope];
        $zipRanges = $region['zip_ranges'] ?? [];
        $municipalities = $region['municipalities'] ?? [];

        $query->where(function($q) use ($zipRanges, $municipalities) {
            // Filter by zip ranges - qualify column name to avoid ambiguity
            if (!empty($zipRanges)) {
                $q->where(function($zipQuery) use ($zipRanges) {
                    foreach ($zipRanges as $range) {
                        $zipQuery->orWhereBetween('job_postings.zipcode', [$range[0], $range[1]]);
                    }
                });
            }

            // Also filter by municipalities if available - qualify column name
            if (!empty($municipalities)) {
                $q->orWhereIn('job_postings.city', $municipalities);
            }
        });

        return $query;
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

        // Store regional data for tooltips (keyed by scope)
        $this->regionDetails = [];

        // Count jobs for each region
        foreach ($regionalData as $region) {
            $scope = $region['scope'];

            // Store region details for tooltips
            $this->regionDetails[$scope] = [
                'zip_ranges' => $region['zip_ranges'],
                'municipalities' => $region['municipalities'] ?? [],
                'macro_region' => $region['macro_region']
            ];

            // Count jobs in this region
            $jobCount = $this->countJobsInRegion($region);

            // Create option with job count
            $label = $scope . ' (' . $jobCount . ' jobs)';
            $this->regionOptions[$scope] = $label;
        }
    }

    private function countJobsInRegion($region)
    {
        // Build base query with current filters (excluding region to avoid circular filtering)
        $query = $this->buildFilteredJobQuery(excludeRegion: true);

        $zipRanges = $region['zip_ranges'];
        $municipalities = $region['municipalities'] ?? [];

        $query->where(function($q) use ($zipRanges, $municipalities) {
            // Filter by zip ranges - qualify column name to avoid ambiguity
            if (!empty($zipRanges)) {
                $q->where(function($zipQuery) use ($zipRanges) {
                    foreach ($zipRanges as $range) {
                        $zipQuery->orWhereBetween('job_postings.zipcode', [$range[0], $range[1]]);
                    }
                });
            }

            // Also filter by municipalities if available - qualify column name
            if (!empty($municipalities)) {
                $q->orWhereIn('job_postings.city', $municipalities);
            }
        });

        return $query->count();
    }

    public function clearFilters()
    {
        $this->search = '';
        // Don't clear companyFilter if we have a scopedCompanyId (company details page)
        if (!$this->scopedCompanyId) {
            $this->companyFilter = '';
        }
        $this->locationFilter = '';
        $this->regionFilter = '';
        $this->skillsFilter = []; // Clear skills filter
        $this->dateFromFilter = '';
        $this->dateToFilter = '';
        $this->datePreset = '';
        $this->viewedStatusFilter = '';
        $this->ratingStatusFilter = '';
        $this->favoritesStatusFilter = '';
        $this->jobStatusFilter = 'open';
        $this->perPage = 10;

        // Refresh filter options after clearing
        $this->loadAvailableSkills();
        $this->loadRegionalData();
        $this->loadCompanyOptions();
        $this->loadFilterOptions();

        $this->dispatch('filtersCleared');
    }

    public function updated($propertyName, $value = null)
    {
        // Handle array properties like skillsFilter.0, skillsFilter.1, etc.
        $baseProperty = explode('.', $propertyName)[0];

        // For skills filter, clean the values to store only skill names without counts
        if ($baseProperty === 'skillsFilter' && is_array($this->skillsFilter)) {
            $this->skillsFilter = array_map(function($skill) {
                // Extract skill name without count for URL storage
                return preg_replace('/\s*\(\d+\)$/', '', $skill);
            }, $this->skillsFilter);
        }

        // Refresh filter options when relevant filters change
        $refreshTriggers = ['search', 'companyFilter', 'regionFilter', 'skillsFilter', 'dateFromFilter', 'dateToFilter', 'viewedStatusFilter', 'ratingStatusFilter', 'favoritesStatusFilter', 'jobStatusFilter'];
        if (in_array($baseProperty, $refreshTriggers)) {
            $this->loadAvailableSkills();
            $this->loadRegionalData();
            $this->loadCompanyOptions();
            $this->loadFilterOptions();
        }

        $this->dispatch('filterUpdated', [
            'property' => $baseProperty,
            'value' => $baseProperty === 'skillsFilter' ? $this->skillsFilter : ($this->$baseProperty ?? $value),
            'filters' => [
                'search' => $this->search,
                'companyFilter' => $this->scopedCompanyId ? null : $this->companyFilter,
                'locationFilter' => $this->locationFilter,
                'regionFilter' => $this->regionFilter,
                'skillsFilter' => $this->skillsFilter,
                'dateFromFilter' => $this->dateFromFilter,
                'dateToFilter' => $this->dateToFilter,
                'viewedStatusFilter' => $this->viewedStatusFilter,
                'ratingStatusFilter' => $this->ratingStatusFilter,
                'favoritesStatusFilter' => $this->favoritesStatusFilter,
                'jobStatusFilter' => $this->jobStatusFilter,
                'perPage' => $this->perPage,
                'scopedCompanyId' => $this->scopedCompanyId,
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
                'regionFilter' => $this->regionFilter,
                'skillsFilter' => $this->skillsFilter, // Include skills filter
                'dateFromFilter' => $this->dateFromFilter,
                'dateToFilter' => $this->dateToFilter,
                'viewedStatusFilter' => $this->viewedStatusFilter,
                'ratingStatusFilter' => $this->ratingStatusFilter,
                'favoritesStatusFilter' => $this->favoritesStatusFilter,
                'jobStatusFilter' => $this->jobStatusFilter,
                'perPage' => $this->perPage,
                'scopedCompanyId' => $this->scopedCompanyId,
            ]
        ]);
    }

    public function removeSkill($skill)
    {
        // Extract skill name from format "Skill Name (count)" for comparison
        $skillToRemove = preg_replace('/\s*\(\d+\)$/', '', $skill);

        $this->skillsFilter = array_values(array_filter($this->skillsFilter, function($s) use ($skillToRemove) {
            // Since skillsFilter now stores clean names, compare directly
            return $s !== $skillToRemove;
        }));

        // Refresh filter options after removing skill
        $this->loadAvailableSkills();
        $this->loadRegionalData();
        $this->loadCompanyOptions();
        $this->loadFilterOptions();

        $this->dispatch('filterUpdated', [
            'property' => 'skillsFilter',
            'value' => $this->skillsFilter,
            'filters' => [
                'search' => $this->search,
                'companyFilter' => $this->scopedCompanyId ? null : $this->companyFilter,
                'locationFilter' => $this->locationFilter,
                'regionFilter' => $this->regionFilter,
                'skillsFilter' => $this->skillsFilter,
                'dateFromFilter' => $this->dateFromFilter,
                'dateToFilter' => $this->dateToFilter,
                'viewedStatusFilter' => $this->viewedStatusFilter,
                'ratingStatusFilter' => $this->ratingStatusFilter,
                'favoritesStatusFilter' => $this->favoritesStatusFilter,
                'jobStatusFilter' => $this->jobStatusFilter,
                'perPage' => $this->perPage,
                'scopedCompanyId' => $this->scopedCompanyId,
            ]
        ]);
    }

    public function getRegionTooltip($regionScope)
    {
        if (!isset($this->regionDetails[$regionScope])) {
            return '';
        }

        $details = $this->regionDetails[$regionScope];
        $tooltip = $details['macro_region'] . "\n\n";

        // Add zip ranges
        if (!empty($details['zip_ranges'])) {
            $tooltip .= "Zip Ranges:\n";
            foreach ($details['zip_ranges'] as $range) {
                $tooltip .= "• " . $range[0] . " - " . $range[1] . "\n";
            }
            $tooltip .= "\n";
        }

        // Add municipalities
        if (!empty($details['municipalities'])) {
            $tooltip .= "Municipalities:\n";
            $tooltip .= "• " . implode(", ", $details['municipalities']);
        }

        return trim($tooltip);
    }

    public function render()
    {
        // Load available skills if not already loaded
        if (empty($this->availableSkills)) {
            $this->loadAvailableSkills();
        }

        // Load company options if not already loaded
        if (empty($this->companyOptions)) {
            $this->loadCompanyOptions();
        }

        // Load filter options if not already loaded
        if (empty($this->viewedStatusOptions) || empty($this->ratingStatusOptions) || empty($this->jobStatusOptions)) {
            $this->loadFilterOptions();
        }

        return view('livewire.components.search-filters');
    }
}
