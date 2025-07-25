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
    public $jobStatusFilter = 'open'; // New filter for job status (open/closed/both)
    public $perPage = 10;

    public $companies = [];
    public $locations = [];
    public $regionOptions = []; // Available regions for filtering
    public $regionDetails = []; // Region details for tooltips (zip ranges, municipalities)
    public $availableSkills = []; // Available skills for listbox
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

        // Create array with skill names including counts, filtered to exclude skills with count <= 1
        $this->availableSkills = collect($skillCounts)
            ->filter(fn($count) => $count > 0) // Only include skills that have results
            ->map(fn($count, $skill) => $skill . ' (' . $count . ')')
            ->sort()
            ->values()
            ->toArray();
    }

    private function buildFilteredJobQuery($excludeSkills = false, $excludeRegion = false)
    {
        $query = \App\Models\JobPosting::query();

        // Apply job status filter first
        if ($this->jobStatusFilter === 'closed') {
            $query->whereNotNull('job_post_closed_date');
        } elseif ($this->jobStatusFilter === 'both') {
            // No additional filter needed for both
        } else { // 'open' or default
            $query->whereNull('job_post_closed_date');
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

        // Apply company filter
        if (!empty($this->companyFilter) && !$this->scopedCompanyId) {
            $query->whereHas('company', function($companyQuery) {
                $companyQuery->where('name', $this->companyFilter);
            });
        }

        // Apply scoped company filter
        if ($this->scopedCompanyId) {
            $query->where('company_id', $this->scopedCompanyId);
        }

        // Apply region filter (unless excluded)
        if (!$excludeRegion && !empty($this->regionFilter)) {
            $this->applyRegionFilterToQuery($query, $this->regionFilter);
        }

        // Apply skills filter (unless excluded)
        if (!$excludeSkills && !empty($this->skillsFilter)) {
            foreach ($this->skillsFilter as $skill) {
                // Extract skill name without count for filtering
                $skillName = preg_replace('/\s*\(\d+\)$/', '', $skill);
                $query->whereJsonContains('skills', $skillName);
            }
        }

        // Apply date filters
        if (!empty($this->dateFromFilter)) {
            $query->whereDate('created_at', '>=', $this->dateFromFilter);
        }
        if (!empty($this->dateToFilter)) {
            $query->whereDate('created_at', '<=', $this->dateToFilter);
        }

        // Apply viewed status filter
        if (!empty($this->viewedStatusFilter) && Auth::check()) {
            if ($this->viewedStatusFilter === 'viewed') {
                $query->whereHas('userViews', function($q) {
                    $q->where('user_id', Auth::id());
                });
            } elseif ($this->viewedStatusFilter === 'not_viewed') {
                $query->whereDoesntHave('userViews', function($q) {
                    $q->where('user_id', Auth::id());
                });
            }
        }

        // Apply rating status filter
        if (!empty($this->ratingStatusFilter)) {
            if ($this->ratingStatusFilter === 'rated') {
                $query->whereHas('jobRating');
            } elseif ($this->ratingStatusFilter === 'not_rated') {
                $query->whereDoesntHave('jobRating');
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
        $this->jobStatusFilter = 'open';
        $this->perPage = 10;

        // Refresh filter options after clearing
        $this->loadAvailableSkills();
        $this->loadRegionalData();

        $this->dispatch('filtersCleared');
    }

    public function updated($propertyName, $value = null)
    {
        // Handle array properties like skillsFilter.0, skillsFilter.1, etc.
        $baseProperty = explode('.', $propertyName)[0];

        // Refresh filter options when relevant filters change
        $refreshTriggers = ['search', 'companyFilter', 'regionFilter', 'skillsFilter', 'dateFromFilter', 'dateToFilter', 'viewedStatusFilter', 'ratingStatusFilter', 'jobStatusFilter'];
        if (in_array($baseProperty, $refreshTriggers)) {
            $this->loadAvailableSkills();
            $this->loadRegionalData();
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
            $currentSkill = preg_replace('/\s*\(\d+\)$/', '', $s);
            return $currentSkill !== $skillToRemove;
        }));

        // Refresh filter options after removing skill
        $this->loadAvailableSkills();
        $this->loadRegionalData();

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

        return view('livewire.components.search-filters');
    }
}
