<div class="bg-white dark:bg-white/10 border border-zinc-200 dark:border-white/10 shadow-sm p-6 mb-6 rounded-lg">
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-100">{{ $title }}</flux:heading>
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
                key="region-filter"
            />
        </div>

        <!-- Skills Filter Component -->
        <div class="md:col-span-2">
            <livewire:search-filters.skills-filter
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
    </div>

    <!-- Selected Filters Display -->
    @if($search || $companyFilter || $regionFilter || count($skillsFilter) > 0 || $viewedStatus || $ratingStatus || $favoritesStatus || $datePreset || ($jobStatus && $jobStatus !== 'open'))
    <div class="pt-4 mt-4 border-t border-zinc-200 dark:border-zinc-700">
        <div class="flex flex-wrap gap-2">
            @if($search)
            <flux:badge variant="outline" size="sm">
                Search: "{{ $search }}"
                <flux:badge.close wire:click="$set('search', '')" />
            </flux:badge>
            @endif

            @if($companyFilter && $showCompanyFilter)
            <flux:badge variant="outline" size="sm">
                Company: {{ $companyFilter }}
                <flux:badge.close wire:click="$set('companyFilter', '')" />
            </flux:badge>
            @endif

            @if($regionFilter)
            <flux:badge variant="outline" size="sm">
                Region: {{ $regionFilter }}
                <flux:badge.close wire:click="$set('regionFilter', '')" />
            </flux:badge>
            @endif

            @if(count($skillsFilter) > 0)
            @foreach($skillsFilter as $skill)
            <flux:badge variant="outline" size="sm">
                Skill: {{ $skill }}
                <flux:badge.close wire:click="removeSkill('{{ $skill }}')" />
            </flux:badge>
            @endforeach
            @endif

            @auth
            @if($viewedStatus)
            <flux:badge variant="outline" size="sm">
                Status: {{ $viewedStatus === 'viewed' ? 'Viewed Only' : 'Not Viewed' }}
                <flux:badge.close wire:click="$set('viewedStatus', '')" />
            </flux:badge>
            @endif

            @if($ratingStatus)
            <flux:badge variant="outline" size="sm">
                Rating: {{ $ratingStatus === 'rated' ? 'Rated Only' : 'Not Rated' }}
                <flux:badge.close wire:click="$set('ratingStatus', '')" />
            </flux:badge>
            @endif

            @if($favoritesStatus)
            <flux:badge variant="outline" size="sm">
                Favorites: {{ $favoritesStatus === 'favorited' ? 'Saved Only' : 'Not Saved' }}
                <flux:badge.close wire:click="$set('favoritesStatus', '')" />
            </flux:badge>
            @endif

            @if($jobStatus && $jobStatus !== 'open')
            <flux:badge variant="outline" size="sm">
                Status: {{ $jobStatus === 'closed' ? 'Closed Jobs Only' : 'Open & Closed' }}
                <flux:badge.close wire:click="$set('jobStatus', 'open')" />
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
                <flux:badge.close wire:click="setDatePreset('')" />
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
        console.log('JavaScript: companyFilterUpdated event received', event);
        $wire.updateCompanyFilter(event.company);
    });

    Livewire.on('regionFilterUpdated', (event) => {
        console.log('JavaScript: regionFilterUpdated event received', event);
        $wire.updateRegionFilter(event.region);
    });

    Livewire.on('skillsFilterUpdated', (event) => {
        console.log('JavaScript: skillsFilterUpdated event received', event);
        $wire.updateSkillsFilter(event.skills);
    });

    Livewire.on('dateFilterUpdated', (event) => {
        console.log('JavaScript: dateFilterUpdated event received', event);
        $wire.updateDateFilter(event.from, event.to, event.preset);
    });

    Livewire.on('viewedFilterUpdated', (event) => {
        console.log('JavaScript: viewedFilterUpdated event received', event);
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
