<?php

namespace App\Livewire\Components;

use Livewire\Component;

class SearchFilters extends Component
{
    public $search = '';
    public $companyFilter = '';
    public $locationFilter = '';
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

        // Initialize from URL parameters
        $this->search = request()->get('search', '');
        $this->companyFilter = request()->get('companyFilter', '');
        $this->locationFilter = request()->get('locationFilter', '');
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
        // Get all unique skills from open job postings only (exclude closed jobs)
        $jobPostings = \App\Models\JobPosting::whereNotNull('skills')
            ->whereNull('job_post_closed_date') // Only include open jobs
            ->get();

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
            ->filter(fn($count) => $count > 1) // Only include skills that appear more than once
            ->map(fn($count, $skill) => $skill . ' (' . $count . ')')
            ->sort()
            ->values()
            ->toArray();
    }

    public function clearFilters()
    {
        $this->search = '';
        // Don't clear companyFilter if we have a scopedCompanyId (company details page)
        if (!$this->scopedCompanyId) {
            $this->companyFilter = '';
        }
        $this->locationFilter = '';
        $this->skillsFilter = []; // Clear skills filter
        $this->dateFromFilter = '';
        $this->dateToFilter = '';
        $this->datePreset = '';
        $this->viewedStatusFilter = '';
        $this->ratingStatusFilter = '';
        $this->jobStatusFilter = 'open';
        $this->perPage = 10;

        $this->dispatch('filtersCleared');
    }

    public function updated($propertyName, $value = null)
    {
        // Handle array properties like skillsFilter.0, skillsFilter.1, etc.
        $baseProperty = explode('.', $propertyName)[0];

        $this->dispatch('filterUpdated', [
            'property' => $baseProperty,
            'value' => $baseProperty === 'skillsFilter' ? $this->skillsFilter : ($this->$baseProperty ?? $value),
            'filters' => [
                'search' => $this->search,
                'companyFilter' => $this->scopedCompanyId ? null : $this->companyFilter,
                'locationFilter' => $this->locationFilter,
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

        $this->dispatch('filterUpdated', [
            'property' => 'skillsFilter',
            'value' => $this->skillsFilter,
            'filters' => [
                'search' => $this->search,
                'companyFilter' => $this->scopedCompanyId ? null : $this->companyFilter,
                'locationFilter' => $this->locationFilter,
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

    public function render()
    {
        // Load available skills if not already loaded
        if (empty($this->availableSkills)) {
            $this->loadAvailableSkills();
        }

        return view('livewire.components.search-filters');
    }
}
