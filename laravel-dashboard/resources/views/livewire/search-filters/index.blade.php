<div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold text-zinc-900">{{ $title }}</h2>
        <flux:button wire:click="clearFilters" variant="subtle" size="sm" icon="x-mark">
            Clear All
        </flux:button>
    </div>

    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <!-- Search Input -->
        <div class="col-span-full md:col-span-2">
            <flux:input
                wire:model.live.debounce.500ms="search"
                placeholder="Search jobs, companies, or keywords..."
                icon="magnifying-glass"
                label="Search"
            />
        </div>

        <!-- Company Filter Component -->
        @if($showCompanyFilter && !$scopedCompanyId)
        <div>
            <livewire:search-filters.company-filter
                :selectedCompany="$companyFilter"
                key="company-filter-{{ $companyFilter }}"
            />
        </div>
        @endif

        <!-- Region Filter Component -->
        <div>
            <livewire:search-filters.region-filter
                :selectedRegion="$regionFilter"
                key="region-filter-{{ $regionFilter }}"
            />
        </div>

        <!-- Skills Filter Component -->
        <div class="md:col-span-2">
            <livewire:search-filters.skills-filter-simple
                :skillsFilter="$skillsFilter"
                key="skills-filter-{{ count($skillsFilter) }}"
            />
        </div>

        <!-- Date Filter Component -->
        @if($showDateFilters)
        <div class="md:col-span-2">
            <livewire:search-filters.date-filter
                :dateFrom="$dateFrom"
                :dateTo="$dateTo"
                :datePreset="$datePreset"
                :dateRange="$dateRange"
                key="date-filter-{{ $dateFrom }}-{{ $dateTo }}-{{ $datePreset }}"
            />
        </div>
        @endif

        <!-- Status Filters Components -->
        @auth
        <div>
            <livewire:search-filters.viewed-filter
                :status="$viewedStatus"
                key="viewed-filter-{{ $viewedStatus }}"
            />
        </div>
        @endauth

        <div>
            <livewire:search-filters.rating-filter
                :status="$ratingStatus"
                key="rating-filter-{{ $ratingStatus }}"
            />
        </div>

        @auth
        <div>
            <livewire:search-filters.favorites-filter
                :status="$favoritesStatus"
                key="favorites-filter-{{ $favoritesStatus }}"
            />
        </div>
        @endauth

        <!-- Job Status Filter Component -->
        <div>
            <livewire:search-filters.job-status-filter
                :status="$jobStatus"
                key="job-status-filter-{{ $jobStatus }}"
            />
        </div>

        <!-- Per Page Selector -->
        @if($showPerPage)
        <div>
            <flux:select wire:model.live="perPage" label="Per Page" placeholder="Select...">
                <flux:select.option value="10">10 per page</flux:select.option>
                <flux:select.option value="25">25 per page</flux:select.option>
                <flux:select.option value="50">50 per page</flux:select.option>
                <flux:select.option value="100">100 per page</flux:select.option>
            </flux:select>
        </div>
        @endif
    </div>

    <!-- Selected Filters Display -->
    @if($search || $companyFilter || $regionFilter || count($skillsFilter) > 0 || $viewedStatus || $ratingStatus || $favoritesStatus || $datePreset || ($jobStatus && $jobStatus !== 'open'))
    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
        <div class="flex flex-wrap gap-2">
            @if($search)
            <flux:badge variant="outline" size="sm">
                Search: "{{ $search }}"
                <flux:button size="xs" variant="ghost" wire:click="$set('search', '')" class="ml-1">
                    <flux:icon.x-mark class="w-3 h-3" />
                </flux:button>
            </flux:badge>
            @endif

            @if($companyFilter && $showCompanyFilter)
            <flux:badge variant="outline" size="sm">
                Company: {{ $companyFilter }}
                <flux:button size="xs" variant="ghost" wire:click="$set('companyFilter', '')" class="ml-1">
                    <flux:icon.x-mark class="w-3 h-3" />
                </flux:button>
            </flux:badge>
            @endif

            @if($regionFilter)
            <flux:badge variant="outline" size="sm">
                Region: {{ $regionFilter }}
                <flux:button size="xs" variant="ghost" wire:click="$set('regionFilter', '')" class="ml-1">
                    <flux:icon.x-mark class="w-3 h-3" />
                </flux:button>
            </flux:badge>
            @endif

            @if(count($skillsFilter) > 0)
            @foreach($skillsFilter as $skill)
            <flux:badge variant="outline" size="sm">
                Skill: {{ $skill }}
                <flux:button size="xs" variant="ghost" wire:click="removeSkill('{{ $skill }}')" class="ml-1">
                    <flux:icon.x-mark class="w-3 h-3" />
                </flux:button>
            </flux:badge>
            @endforeach
            @endif

            @auth
            @if($viewedStatus)
            <flux:badge variant="outline" size="sm">
                Status: {{ $viewedStatus === 'viewed' ? 'Viewed Only' : 'Not Viewed' }}
                <flux:button size="xs" variant="ghost" wire:click="$set('viewedStatus', '')" class="ml-1">
                    <flux:icon.x-mark class="w-3 h-3" />
                </flux:button>
            </flux:badge>
            @endif

            @if($ratingStatus)
            <flux:badge variant="outline" size="sm">
                Rating: {{ $ratingStatus === 'rated' ? 'Rated Only' : 'Not Rated' }}
                <flux:button size="xs" variant="ghost" wire:click="$set('ratingStatus', '')" class="ml-1">
                    <flux:icon.x-mark class="w-3 h-3" />
                </flux:button>
            </flux:badge>
            @endif

            @if($favoritesStatus)
            <flux:badge variant="outline" size="sm">
                Favorites: {{ $favoritesStatus === 'favorited' ? 'Saved Only' : 'Not Saved' }}
                <flux:button size="xs" variant="ghost" wire:click="$set('favoritesStatus', '')" class="ml-1">
                    <flux:icon.x-mark class="w-3 h-3" />
                </flux:button>
            </flux:badge>
            @endif

            @if($jobStatus && $jobStatus !== 'open')
            <flux:badge variant="outline" size="sm">
                Status: {{ $jobStatus === 'closed' ? 'Closed Jobs Only' : 'Open & Closed' }}
                <flux:button size="xs" variant="ghost" wire:click="$set('jobStatus', 'open')" class="ml-1">
                    <flux:icon.x-mark class="w-3 h-3" />
                </flux:button>
            </flux:badge>
            @endif
            @endauth

            @if($datePreset)
            <flux:badge variant="outline" size="sm">
                @php
                    $dateLabels = [
                        'last_24_hours' => 'Last 24 Hours',
                        'last_week' => 'Last Week',
                        'last_month' => 'Last Month',
                        'last_3_months' => 'Last 3 Months'
                    ];
                @endphp
                Posted: {{ $dateLabels[$datePreset] ?? $datePreset }}
                <flux:button size="xs" variant="ghost" wire:click="setDatePreset('')" class="ml-1">
                    <flux:icon.x-mark class="w-3 h-3" />
                </flux:button>
            </flux:badge>
            @endif
        </div>
    </div>
    @endif
</div>

@script
<script>
    // Listen for child component events and call parent methods
    Livewire.on('companyFilterUpdated', (event) => {
        $wire.updateCompanyFilter(event.company);
    });

    Livewire.on('regionFilterUpdated', (event) => {
        $wire.updateRegionFilter(event.region);
    });

    Livewire.on('skillsFilterUpdated', (event) => {
        $wire.updateSkillsFilter(event.skills);
    });

    Livewire.on('dateFilterUpdated', (event) => {
        $wire.updateDateFilter(event.from, event.to, event.preset);
    });

    Livewire.on('viewedFilterUpdated', (event) => {
        $wire.updateViewedStatus(event.status);
    });

    Livewire.on('ratingFilterUpdated', (event) => {
        $wire.updateRatingStatus(event.status);
    });

    Livewire.on('favoritesFilterUpdated', (event) => {
        $wire.updateFavoritesStatus(event.status);
    });

    Livewire.on('jobStatusFilterUpdated', (event) => {
        $wire.updateJobStatus(event.status);
    });
</script>
@endscript
