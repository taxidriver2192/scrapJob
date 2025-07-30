<?php

namespace App\Livewire\SearchFilters;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Flux\DateRange;

class Index extends Component
{
    // Filter state properties - these serve as the single source of truth
    public string $search = '';
    public string $companyFilter = '';
    public string $regionFilter = '';
    public array $skillsFilter = [];
    public ?DateRange $dateRange = null;
    public string $dateFrom = ''; // Keep for backward compatibility
    public string $dateTo = ''; // Keep for backward compatibility
    public string $datePreset = ''; // Keep for backward compatibility
    public string $viewedStatus = '';
    public string $ratingStatus = '';
    public string $favoritesStatus = '';
    public string $jobStatus = 'open';
    public int $perPage = 10;

    // Configuration options
    public bool $showPerPage = true;
    public bool $showDateFilters = true;
    public bool $showCompanyFilter = true;
    public string $title = 'Search & Filters';
    public ?int $scopedCompanyId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'companyFilter' => ['except' => ''],
        'regionFilter' => ['except' => ''],
        'skillsFilter' => ['except' => []],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'datePreset' => ['except' => ''],
        'viewedStatus' => ['except' => ''],
        'ratingStatus' => ['except' => ''],
        'favoritesStatus' => ['except' => ''],
        'jobStatus' => ['except' => 'open'],
        'perPage' => ['except' => 10],
    ];

    public function mount($companies = [], $locations = [], $options = [])
    {
        // Set configuration options
        $this->showPerPage = $options['showPerPage'] ?? true;
        $this->showDateFilters = $options['showDateFilters'] ?? true;
        $this->showCompanyFilter = $options['showCompanyFilter'] ?? true;
        $this->scopedCompanyId = $options['scopedCompanyId'] ?? null;
        $this->title = $options['title'] ?? 'Search & Filters';

        // Initialize from URL parameters
        $this->search = request()->get('search', '');
        $this->companyFilter = request()->get('companyFilter', '');
        $this->regionFilter = request()->get('regionFilter', '');
        $this->skillsFilter = request()->get('skillsFilter', []) ?: [];
        
        // Handle date parameters - convert to DateRange if available
        $dateFrom = request()->get('dateFrom', '');
        $dateTo = request()->get('dateTo', '');
        $datePreset = request()->get('datePreset', '');
        
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->datePreset = $datePreset;
        
        if ($dateFrom && $dateTo) {
            $this->dateRange = new DateRange(
                \Carbon\Carbon::parse($dateFrom),
                \Carbon\Carbon::parse($dateTo)
            );
        }
        
        $this->viewedStatus = request()->get('viewedStatus', '');
        $this->ratingStatus = request()->get('ratingStatus', '');
        $this->favoritesStatus = request()->get('favoritesStatus', '');
        $this->jobStatus = request()->get('jobStatus', 'open');
        $this->perPage = request()->get('perPage', 10);

        // Dispatch initial filter values
        $this->emitFiltersUpdated();
    }

    // Event handlers for child component updates
    public function updateCompanyFilter($company)
    {
        $this->companyFilter = $company;
        $this->emitFiltersUpdated();
    }

    public function updateRegionFilter($region)
    {
        $this->regionFilter = $region;
        $this->emitFiltersUpdated();
    }

    public function updateSkillsFilter($skills)
    {
        $this->skillsFilter = is_array($skills) ? $skills : [];
        $this->emitFiltersUpdated();
    }

    public function updateDateFilter($from, $to, $preset)
    {
        $this->dateFrom = $from;
        $this->dateTo = $to;
        $this->datePreset = $preset;
        
        // Update DateRange object
        if ($from && $to) {
            $this->dateRange = new DateRange(
                \Carbon\Carbon::parse($from),
                \Carbon\Carbon::parse($to)
            );
        } else {
            $this->dateRange = null;
        }
        
        $this->emitFiltersUpdated();
    }

    public function updateViewedStatus($status)
    {
        $this->viewedStatus = $status;
        $this->emitFiltersUpdated();
    }

    public function updateRatingStatus($status)
    {
        $this->ratingStatus = $status;
        $this->emitFiltersUpdated();
    }

    public function updateFavoritesStatus($status)
    {
        $this->favoritesStatus = $status;
        $this->emitFiltersUpdated();
    }

    public function updateJobStatus($status)
    {
        $this->jobStatus = $status;
        $this->emitFiltersUpdated();
    }

    // Handle direct updates (for search input and perPage)
    public function updatedSearch()
    {
        $this->emitFiltersUpdated();
    }

    public function updatedPerPage()
    {
        $this->emitFiltersUpdated();
    }

    public function clearFilters()
    {
        $this->search = '';
        // Don't clear companyFilter if we have a scopedCompanyId
        if (!$this->scopedCompanyId) {
            $this->companyFilter = '';
        }
        $this->regionFilter = '';
        $this->skillsFilter = [];
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->datePreset = '';
        $this->viewedStatus = '';
        $this->ratingStatus = '';
        $this->favoritesStatus = '';
        $this->jobStatus = 'open';
        $this->perPage = 10;

        $this->dispatch('filtersCleared');
        $this->emitFiltersUpdated();
    }

    // Method to remove individual skills
    public function removeSkill($skill)
    {
        $this->skillsFilter = array_values(array_filter($this->skillsFilter, fn($s) => $s !== $skill));
        $this->emitFiltersUpdated();
    }

    // Method to set date presets
    public function setDatePreset($preset)
    {
        $this->datePreset = $preset;

        switch ($preset) {
            case 'last_24_hours':
                $this->dateFrom = now()->subDay()->format('Y-m-d');
                $this->dateTo = now()->format('Y-m-d');
                break;
            case 'last_week':
                $this->dateFrom = now()->subWeek()->format('Y-m-d');
                $this->dateTo = now()->format('Y-m-d');
                break;
            case 'last_month':
                $this->dateFrom = now()->subMonth()->format('Y-m-d');
                $this->dateTo = now()->format('Y-m-d');
                break;
            case 'last_3_months':
                $this->dateFrom = now()->subMonths(3)->format('Y-m-d');
                $this->dateTo = now()->format('Y-m-d');
                break;
            default:
                $this->dateFrom = '';
                $this->dateTo = '';
                break;
        }

        $this->emitFiltersUpdated();
    }

    // Central method to emit unified filter update event
    private function emitFiltersUpdated()
    {
        $this->dispatch('filterUpdated', filters: [
            'search' => $this->search,
            'companyFilter' => $this->scopedCompanyId ? null : $this->companyFilter,
            'locationFilter' => '', // Deprecated but kept for compatibility
            'regionFilter' => $this->regionFilter,
            'skillsFilter' => $this->skillsFilter,
            'dateFromFilter' => $this->dateFrom,
            'dateToFilter' => $this->dateTo,
            'viewedStatusFilter' => $this->viewedStatus,
            'ratingStatusFilter' => $this->ratingStatus,
            'favoritesStatusFilter' => $this->favoritesStatus,
            'jobStatusFilter' => $this->jobStatus,
            'perPage' => $this->perPage,
            'scopedCompanyId' => $this->scopedCompanyId,
        ]);
    }

    public function render()
    {
        return view('livewire.search-filters.index');
    }
}
