<flux:card class="mb-8">
    <div class="p-4">
        <div class="flex items-center justify-between mb-4">
            <flux:heading size="lg">
                {{ $title }}
            </flux:heading>
            @if($search || $companyFilter || $regionFilter || count($skillsFilter) > 0 || $viewedStatusFilter || $ratingStatusFilter || $datePreset || ($jobStatusFilter && $jobStatusFilter !== 'open'))
            <flux:button
                size="sm"
                variant="ghost"
                wire:click="clearFilters"
                icon="x-circle"
            >
                Clear Filters
            </flux:button>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 mb-4">
            <!-- Global Search -->
            <div class="md:col-span-2">
                <div class="flex items-center gap-1 mb-1">
                    <flux:label>Search Jobs</flux:label>
                    <flux:tooltip content="Find jobs by title, description, or company name" position="top">
                        <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
                    </flux:tooltip>
                </div>
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search jobs, companies, descriptions..."
                    icon="magnifying-glass"
                />
            </div>

            <!-- Company Filter with Autocomplete -->
            @if($showCompanyFilter)
            <div>
                <div class="flex items-center gap-1 mb-1">
                    <flux:label>Company</flux:label>
                    <flux:tooltip content="Filter by specific company" position="top">
                        <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
                    </flux:tooltip>
                </div>
                <flux:autocomplete wire:model.live="companyFilter" placeholder="Select or type company..." icon="building-office">
                    @foreach($companies as $companyId => $companyName)
                        <flux:autocomplete.item value="{{ $companyName }}">
                            {{ $companyName }}
                        </flux:autocomplete.item>
                    @endforeach
                </flux:autocomplete>
            </div>
            @endif

            <!-- Region Filter -->
            <div>
                <div class="flex items-center gap-1 mb-1">
                    <flux:label>Region</flux:label>
                    <flux:tooltip content="Filter by Danish region or area" position="top">
                        <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
                    </flux:tooltip>
                </div>
                <flux:select wire:model.live="regionFilter" placeholder="All Regions" icon="map-pin">
                    @foreach($regionOptions as $key => $label)
                        <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Skills Filter -->
            <div>
                <div class="flex items-center gap-1 mb-1">
                    <flux:label>Skills</flux:label>
                    <flux:tooltip content="Select multiple skills to filter jobs" position="top">
                        <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
                    </flux:tooltip>
                </div>

                <flux:select variant="listbox" multiple searchable wire:model.live="skillsFilter" placeholder="Choose skills..." icon="code-bracket">
                    <x-slot name="search">
                        <flux:select.search class="px-4" placeholder="Search skills..." />
                    </x-slot>
                    @foreach($availableSkills as $skill)
                        <flux:select.option value="{{ $skill }}">{{ $skill }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Viewed Status Filter -->
            @auth
            <div>
                <div class="flex items-center gap-1 mb-1">
                    <flux:label>Viewed Status</flux:label>
                    <flux:tooltip content="Show jobs you've seen or not seen" position="top">
                        <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
                    </flux:tooltip>
                </div>
                <flux:select wire:model.live="viewedStatusFilter" icon="eye">
                    <option value="">All Jobs</option>
                    <option value="viewed">Viewed Only</option>
                    <option value="not_viewed">Not Viewed</option>
                </flux:select>
            </div>

            <!-- Rating Status Filter -->
            <div>
                <div class="flex items-center gap-1 mb-1">
                    <flux:label>Rating Status</flux:label>
                    <flux:tooltip content="Show jobs with or without AI ratings" position="top">
                        <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
                    </flux:tooltip>
                </div>
                <flux:select wire:model.live="ratingStatusFilter" icon="sparkles">
                    <option value="">All Jobs</option>
                    <option value="rated">Rated Only</option>
                    <option value="not_rated">Not Rated</option>
                </flux:select>
            </div>

            <!-- Job Status Filter -->
            <div>
                <div class="flex items-center gap-1 mb-1">
                    <flux:label>Job Status</flux:label>
                    <flux:tooltip content="Show open jobs, closed jobs, or both" position="top">
                        <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
                    </flux:tooltip>
                </div>
                <flux:select wire:model.live="jobStatusFilter" icon="briefcase">
                    <option value="open">Open Jobs Only</option>
                    <option value="closed">Closed Jobs Only</option>
                    <option value="both">Open & Closed</option>
                </flux:select>
            </div>
            @endauth

            <!-- Items per page -->
            @if($showPerPage)
            <div>
                <div class="flex items-center gap-1 mb-1">
                    <flux:label>Results per Page</flux:label>
                    <flux:tooltip content="Number of jobs to display per page" position="top">
                        <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
                    </flux:tooltip>
                </div>
                <flux:select wire:model.live="perPage" icon="queue-list">
                    <option value="5">5 per page</option>
                    <option value="10">10 per page</option>
                    <option value="20">20 per page</option>
                    <option value="50">50 per page</option>
                </flux:select>
            </div>
            @endif

            @if($showDateFilters)
            <!-- Date Presets -->
            <div>
                <div class="flex items-center gap-1 mb-1">
                    <flux:label>Posted Date Range</flux:label>
                    <flux:tooltip content="Filter jobs by when they were posted" position="top">
                        <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
                    </flux:tooltip>
                </div>
                <flux:select wire:model.live="datePreset" icon="calendar">
                    <option value="">Posted: All Time</option>
                    <option value="last_24_hours">Posted: Last 24 Hours</option>
                    <option value="last_week">Posted: Last Week</option>
                    <option value="last_month">Posted: Last Month</option>
                    <option value="last_3_months">Posted: Last 3 Months</option>
                </flux:select>
            </div>
            @endif
        </div>

        <!-- Active Filters Display -->
        @if($search || $companyFilter || $regionFilter || count($skillsFilter) > 0 || $viewedStatusFilter || $datePreset || ($jobStatusFilter && $jobStatusFilter !== 'open'))
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
                <flux:tooltip content="{{ $this->getRegionTooltip($regionFilter) }}" position="top">
                    <flux:badge variant="outline" size="sm">
                        Region: {{ $regionOptions[$regionFilter] ?? $regionFilter }}
                        <flux:button size="xs" variant="ghost" wire:click="$set('regionFilter', '')" class="ml-1">
                            <flux:icon.x-mark class="w-3 h-3" />
                        </flux:button>
                    </flux:badge>
                </flux:tooltip>
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
                @if($viewedStatusFilter)
                <flux:badge variant="outline" size="sm">
                    Status: {{ $viewedStatusFilter === 'viewed' ? 'Viewed Only' : 'Not Viewed' }}
                    <flux:button size="xs" variant="ghost" wire:click="$set('viewedStatusFilter', '')" class="ml-1">
                        <flux:icon.x-mark class="w-3 h-3" />
                    </flux:button>
                </flux:badge>
                @endif

                @if($ratingStatusFilter)
                <flux:badge variant="outline" size="sm">
                    Rating: {{ $ratingStatusFilter === 'rated' ? 'Rated Only' : 'Not Rated' }}
                    <flux:button size="xs" variant="ghost" wire:click="$set('ratingStatusFilter', '')" class="ml-1">
                        <flux:icon.x-mark class="w-3 h-3" />
                    </flux:button>
                </flux:badge>
                @endif

                @if($jobStatusFilter && $jobStatusFilter !== 'open')
                <flux:badge variant="outline" size="sm">
                    Status: {{ $jobStatusFilter === 'closed' ? 'Closed Jobs Only' : 'Open & Closed' }}
                    <flux:button size="xs" variant="ghost" wire:click="$set('jobStatusFilter', 'open')" class="ml-1">
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
</flux:card>
