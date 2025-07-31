<?php

namespace App\Livewire\SearchFilters;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

    // Configuration options
    public bool $showPerPage = false; // Disabled since moved to job table
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
    ];

    public function mount($companies = [], $locations = [], $options = [])
    {
        Log::info('SearchFilters Index: Component mounting', [
            'companies_count' => count($companies),
            'locations_count' => count($locations),
            'options' => $options,
            'request_params' => request()->all()
        ]);

        // Set configuration options
        $this->showPerPage = false; // Disabled since moved to job table
        $this->showDateFilters = $options['showDateFilters'] ?? true;
        $this->showCompanyFilter = $options['showCompanyFilter'] ?? true;
        $this->scopedCompanyId = $options['scopedCompanyId'] ?? null;
        $this->title = $options['title'] ?? 'Search & Filters';

        Log::info('SearchFilters Index: Configuration set', [
            'showPerPage' => $this->showPerPage,
            'showDateFilters' => $this->showDateFilters,
            'showCompanyFilter' => $this->showCompanyFilter,
            'scopedCompanyId' => $this->scopedCompanyId,
            'title' => $this->title
        ]);

        // Initialize from URL parameters
        $this->search = request()->get('search', '');
        $this->companyFilter = request()->get('companyFilter', '');
        $this->regionFilter = request()->get('regionFilter', '');
        $this->skillsFilter = request()->get('skillsFilter', []) ?: [];

        Log::info('SearchFilters Index: Basic filters initialized', [
            'search' => $this->search,
            'companyFilter' => $this->companyFilter,
            'regionFilter' => $this->regionFilter,
            'skillsFilter' => $this->skillsFilter
        ]);

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

        Log::info('SearchFilters Index: Date filters initialized', [
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'datePreset' => $this->datePreset,
            'dateRange' => $this->dateRange ? 'set' : 'null'
        ]);

        $this->viewedStatus = request()->get('viewedStatus', '');
        $this->ratingStatus = request()->get('ratingStatus', '');
        $this->favoritesStatus = request()->get('favoritesStatus', '');
        $this->jobStatus = request()->get('jobStatus', 'open');

        Log::info('SearchFilters Index: Status filters initialized', [
            'viewedStatus' => $this->viewedStatus,
            'ratingStatus' => $this->ratingStatus,
            'favoritesStatus' => $this->favoritesStatus,
            'jobStatus' => $this->jobStatus,
        ]);

        // Dispatch initial filter values
        Log::info('SearchFilters Index: About to emit initial filters');
        $this->emitFiltersUpdated();
        Log::info('SearchFilters Index: Mount completed');
    }

    // Event handlers for child component updates
    public function updateCompanyFilter($company)
    {
        Log::info('SearchFilters Index: updateCompanyFilter called', [
            'old_value' => $this->companyFilter,
            'new_value' => $company,
            'caller' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);

        $this->companyFilter = $company;
        $this->emitFiltersUpdated();

        Log::info('SearchFilters Index: updateCompanyFilter completed', [
            'current_value' => $this->companyFilter
        ]);
    }

    public function updateRegionFilter($region)
    {
        Log::info('SearchFilters Index: updateRegionFilter called', [
            'old_value' => $this->regionFilter,
            'new_value' => $region,
            'caller' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);

        $this->regionFilter = $region;

        Log::info('SearchFilters Index: About to emit filters updated from updateRegionFilter');
        $this->emitFiltersUpdated();

        Log::info('SearchFilters Index: updateRegionFilter completed', [
            'current_value' => $this->regionFilter
        ]);
    }

    public function updateSkillsFilter($skills)
    {
        Log::info('SearchFilters Index: updateSkillsFilter called', [
            'old_value' => $this->skillsFilter,
            'new_value' => $skills,
            'is_array' => is_array($skills),
            'caller' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);

        $this->skillsFilter = is_array($skills) ? $skills : [];
        $this->emitFiltersUpdated();

        Log::info('SearchFilters Index: updateSkillsFilter completed', [
            'current_value' => $this->skillsFilter
        ]);
    }

    public function updateDateFilter($from, $to, $preset)
    {
        Log::info('SearchFilters Index: updateDateFilter called', [
            'old_from' => $this->dateFrom,
            'old_to' => $this->dateTo,
            'old_preset' => $this->datePreset,
            'new_from' => $from,
            'new_to' => $to,
            'new_preset' => $preset
        ]);

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

        Log::info('SearchFilters Index: updateDateFilter completed', [
            'current_from' => $this->dateFrom,
            'current_to' => $this->dateTo,
            'current_preset' => $this->datePreset
        ]);
    }

    public function updateViewedStatus($status)
    {
        Log::info('SearchFilters Index: updateViewedStatus called', [
            'old_value' => $this->viewedStatus,
            'new_value' => $status
        ]);

        $this->viewedStatus = $status;
        $this->emitFiltersUpdated();

        Log::info('SearchFilters Index: updateViewedStatus completed', [
            'current_value' => $this->viewedStatus
        ]);
    }

    public function updateRatingStatus($status)
    {
        Log::info('SearchFilters Index: updateRatingStatus called', [
            'old_value' => $this->ratingStatus,
            'new_value' => $status
        ]);

        $this->ratingStatus = $status;
        $this->emitFiltersUpdated();

        Log::info('SearchFilters Index: updateRatingStatus completed', [
            'current_value' => $this->ratingStatus
        ]);
    }

    public function updateFavoritesStatus($status)
    {
        Log::info('SearchFilters Index: updateFavoritesStatus called', [
            'old_value' => $this->favoritesStatus,
            'new_value' => $status
        ]);

        $this->favoritesStatus = $status;
        $this->emitFiltersUpdated();

        Log::info('SearchFilters Index: updateFavoritesStatus completed', [
            'current_value' => $this->favoritesStatus
        ]);
    }

    public function updateJobStatus($status)
    {
        Log::info('SearchFilters Index: updateJobStatus called', [
            'old_value' => $this->jobStatus,
            'new_value' => $status
        ]);

        $this->jobStatus = $status;
        $this->emitFiltersUpdated();

        Log::info('SearchFilters Index: updateJobStatus completed', [
            'current_value' => $this->jobStatus
        ]);
    }

    // Handle direct updates (for search input)
    public function updatedSearch()
    {
        Log::info('SearchFilters Index: updatedSearch called', [
            'new_value' => $this->search,
            'caller' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);

        $this->emitFiltersUpdated();

        Log::info('SearchFilters Index: updatedSearch completed');
    }

    public function updatedRegionFilter()
    {
        Log::info('SearchFilters Index: updatedRegionFilter called (property updated directly)', [
            'new_value' => $this->regionFilter,
            'caller' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);

        $this->emitFiltersUpdated();

        Log::info('SearchFilters Index: updatedRegionFilter completed');
    }

    public function updatedCompanyFilter()
    {
        Log::info('SearchFilters Index: updatedCompanyFilter called (property updated directly)', [
            'new_value' => $this->companyFilter,
            'caller' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);

        $this->emitFiltersUpdated();

        Log::info('SearchFilters Index: updatedCompanyFilter completed');
    }

    public function clearFilters()
    {
        Log::info('SearchFilters Index: clearFilters called');

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

        Log::info('SearchFilters Index: Filters cleared, dispatching events');
        $this->dispatch('filtersCleared');
        $this->emitFiltersUpdated();

        Log::info('SearchFilters Index: clearFilters completed');
    }

    // Method to remove individual skills
    public function removeSkill($skill)
    {
        Log::info('SearchFilters Index: removeSkill called', [
            'skill_to_remove' => $skill,
            'current_skills' => $this->skillsFilter
        ]);

        $this->skillsFilter = array_values(array_filter($this->skillsFilter, fn($s) => $s !== $skill));
        $this->emitFiltersUpdated();

        Log::info('SearchFilters Index: removeSkill completed', [
            'remaining_skills' => $this->skillsFilter
        ]);
    }

    // Method to set date presets
    public function setDatePreset($preset)
    {
        Log::info('SearchFilters Index: setDatePreset called', [
            'old_preset' => $this->datePreset,
            'new_preset' => $preset
        ]);

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

        Log::info('SearchFilters Index: Date preset applied', [
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo
        ]);

        $this->emitFiltersUpdated();

        Log::info('SearchFilters Index: setDatePreset completed');
    }

    // Central method to emit unified filter update event
    private function emitFiltersUpdated()
    {
        $filters = [
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
            'scopedCompanyId' => $this->scopedCompanyId,
        ];

        Log::info('SearchFilters Index: emitFiltersUpdated called', [
            'filters' => $filters,
            'caller' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)
        ]);

        $this->dispatch('filterUpdated', filters: $filters);

        Log::info('SearchFilters Index: filterUpdated event dispatched');
    }

    public function render()
    {
        Log::info('SearchFilters Index: render called');
        return view('livewire.search-filters.index');
    }
}
