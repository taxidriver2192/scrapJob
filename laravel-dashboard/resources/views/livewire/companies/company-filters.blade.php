<div>
    <flux:card class="mb-6">
        <div class="p-4">
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="md" class="text-purple-600">
                    <flux:icon.funnel class="mr-2" />
                    {{ $options['title'] ?? 'Company Search & Filters' }}
                </flux:heading>
                @if($search || $cityFilter || $regionFilter || $statusFilter || $hasVatFilter || $hasJobsFilter || $minEmployeesFilter || $maxEmployeesFilter)
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

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <!-- Search Input -->
                <div class="md:col-span-2">
                    <div class="flex items-center gap-1 mb-1">
                        <flux:label>Search Companies</flux:label>
                        <flux:tooltip content="Find companies by name or description" position="top">
                            <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
                        </flux:tooltip>
                    </div>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search companies..."
                        icon="magnifying-glass"
                    />
                </div>

                <!-- City Filter -->
                <div>
                    <div class="flex items-center gap-1 mb-1">
                        <flux:label>City</flux:label>
                        <flux:tooltip content="Filter by company location" position="top">
                            <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
                        </flux:tooltip>
                    </div>
                    <flux:select wire:model.live="cityFilter" icon="map-pin">
                        @foreach($cityOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Regional Filter -->
                <div>
                    <div class="flex items-center gap-1 mb-1">
                        <flux:label>Region</flux:label>
                        <flux:tooltip content="Filter by larger geographical regions" position="top">
                            <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
                        </flux:tooltip>
                    </div>
                    <flux:select wire:model.live="regionFilter" icon="globe-alt">
                        @foreach($regionOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Status Filter -->
                @if($options['showStatusFilter'] ?? false)
                <div>
                    <div class="flex items-center gap-1 mb-1">
                        <flux:label>Status</flux:label>
                        <flux:tooltip content="Filter by company status" position="top">
                            <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
                        </flux:tooltip>
                    </div>
                    <flux:select wire:model.live="statusFilter" icon="check-circle">
                        @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>
                @endif

                <!-- VAT Filter -->
                @if($options['showVatFilter'] ?? false)
                <div>
                    <div class="flex items-center gap-1 mb-1">
                        <flux:label>VAT</flux:label>
                        <flux:tooltip content="Filter by VAT registration status" position="top">
                            <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
                        </flux:tooltip>
                    </div>
                    <flux:select wire:model.live="hasVatFilter" icon="receipt-tax">
                        @foreach($vatOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>
                @endif

                <!-- Job Postings Filter -->
                @if($options['showJobsFilter'] ?? false)
                <div>
                    <div class="flex items-center gap-1 mb-1">
                        <flux:label>Job Postings</flux:label>
                        <flux:tooltip content="Filter by companies with job postings" position="top">
                            <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
                        </flux:tooltip>
                    </div>
                    <flux:select wire:model.live="hasJobsFilter" icon="briefcase">
                        @foreach($jobsOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>
                @endif

                <!-- Employee Range Filter -->
                @if($options['showEmployeesFilter'] ?? false)
                <div class="md:col-span-2">
                    <div class="flex items-center gap-1 mb-1">
                        <flux:label>Employee Range</flux:label>
                        <flux:tooltip content="Filter by employee count range (leave empty for no limit)" position="top">
                            <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
                        </flux:tooltip>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <flux:input
                            wire:model.live.debounce.300ms="minEmployeesFilter"
                            placeholder="Min employees"
                            type="number"
                            min="0"
                            icon="user-group"
                        />
                        <flux:input
                            wire:model.live.debounce.300ms="maxEmployeesFilter"
                            placeholder="Max employees"
                            type="number"
                            min="0"
                            icon="user-group"
                        />
                    </div>
                </div>
                @endif

                <!-- Per Page -->
                @if($options['showPerPage'] ?? false)
                <div class="@if(!($options['showStatusFilter'] ?? false) && !($options['showVatFilter'] ?? false) && !($options['showJobsFilter'] ?? false) && !($options['showEmployeesFilter'] ?? false)) md:col-start-4 @endif">
                    <div class="flex items-center gap-1 mb-1">
                        <flux:label>Results per Page</flux:label>
                        <flux:tooltip content="Number of companies to display per page" position="top">
                            <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
                        </flux:tooltip>
                    </div>
                    <flux:select wire:model.live="perPage" icon="queue-list">
                        <option value="5">5 per page</option>
                        <option value="10">10 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                    </flux:select>
                </div>
                @endif
            </div>

            <!-- Active Filters Display -->
            @if($search || $cityFilter || $regionFilter || $statusFilter || $hasVatFilter || $hasJobsFilter || $minEmployeesFilter || $maxEmployeesFilter)
            <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <div class="flex flex-wrap gap-2">
                    @if($search)
                    <flux:badge variant="outline" size="sm">
                        Search: "{{ $search }}"
                        <flux:button size="xs" variant="ghost" wire:click="$set('search', '')" class="ml-1">
                            <flux:icon.x-mark class="w-3 h-3" />
                        </flux:button>
                    </flux:badge>
                    @endif                @if($cityFilter)
                <flux:badge variant="outline" size="sm">
                    City: {{ $cityFilter }}
                    <flux:button size="xs" variant="ghost" wire:click="$set('cityFilter', '')" class="ml-1">
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

                    @if($statusFilter)
                    <flux:badge variant="outline" size="sm">
                        Status: {{ ucfirst($statusFilter) }}
                        <flux:button size="xs" variant="ghost" wire:click="$set('statusFilter', '')" class="ml-1">
                            <flux:icon.x-mark class="w-3 h-3" />
                        </flux:button>
                    </flux:badge>
                    @endif

                    @if($hasVatFilter)
                    <flux:badge variant="outline" size="sm">
                        VAT: {{ $vatOptions[$hasVatFilter] }}
                        <flux:button size="xs" variant="ghost" wire:click="$set('hasVatFilter', '')" class="ml-1">
                            <flux:icon.x-mark class="w-3 h-3" />
                        </flux:button>
                    </flux:badge>
                    @endif

                    @if($hasJobsFilter)
                    <flux:badge variant="outline" size="sm">
                        Jobs: {{ $jobsOptions[$hasJobsFilter] }}
                        <flux:button size="xs" variant="ghost" wire:click="$set('hasJobsFilter', '')" class="ml-1">
                            <flux:icon.x-mark class="w-3 h-3" />
                        </flux:button>
                    </flux:badge>
                    @endif

                    @if($minEmployeesFilter || $maxEmployeesFilter)
                    <flux:badge variant="outline" size="sm">
                        Employees:
                        @if($minEmployeesFilter && $maxEmployeesFilter)
                            {{ number_format($minEmployeesFilter) }} - {{ number_format($maxEmployeesFilter) }}
                        @elseif($minEmployeesFilter)
                            {{ number_format($minEmployeesFilter) }}+
                        @elseif($maxEmployeesFilter)
                            ≤ {{ number_format($maxEmployeesFilter) }}
                        @endif
                        <flux:button size="xs" variant="ghost" wire:click="$set('minEmployeesFilter', ''); $set('maxEmployeesFilter', '')" class="ml-1">
                            <flux:icon.x-mark class="w-3 h-3" />
                        </flux:button>
                    </flux:badge>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </flux:card>
</div>
