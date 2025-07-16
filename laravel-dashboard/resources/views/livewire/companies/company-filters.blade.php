<div>
    <flux:card class="mb-6">
        <div class="p-4">
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="md" class="text-purple-600">
                    <flux:icon.funnel class="mr-2" />
                    {{ $options['title'] ?? 'Company Search & Filters' }}
                </flux:heading>
                @if($search || $cityFilter || $statusFilter || $hasVatFilter || $hasJobsFilter || $minEmployeesFilter)
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
                        <flux:icon.question-mark-circle
                            class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help"
                            tooltip="Find companies by name or description"
                        />
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
                        <flux:icon.question-mark-circle
                            class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help"
                            tooltip="Filter by company location"
                        />
                    </div>
                    <flux:select wire:model.live="cityFilter" icon="map-pin">
                        <option value="">All Cities</option>
                        @foreach($locations as $location)
                        <option value="{{ $location }}">{{ $location }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Status Filter -->
                @if($options['showStatusFilter'] ?? false)
                <div>
                    <div class="flex items-center gap-1 mb-1">
                        <flux:label>Status</flux:label>
                        <flux:icon.question-mark-circle
                            class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help"
                            tooltip="Filter by company status"
                        />
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
                        <flux:icon.question-mark-circle
                            class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help"
                            tooltip="Filter by VAT registration status"
                        />
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
                        <flux:icon.question-mark-circle
                            class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help"
                            tooltip="Filter by companies with job postings"
                        />
                    </div>
                    <flux:select wire:model.live="hasJobsFilter" icon="briefcase">
                        @foreach($jobsOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>
                @endif

                <!-- Minimum Employees Filter -->
                @if($options['showEmployeesFilter'] ?? false)
                <div>
                    <div class="flex items-center gap-1 mb-1">
                        <flux:label>Team Size</flux:label>
                        <flux:icon.question-mark-circle
                            class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help"
                            tooltip="Filter by minimum number of employees"
                        />
                    </div>
                    <flux:select wire:model.live="minEmployeesFilter" icon="user-group">
                        @foreach($employeeRangeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>
                @endif

                <!-- Per Page -->
                @if($options['showPerPage'] ?? false)
                <div class="@if(!($options['showStatusFilter'] ?? false) && !($options['showVatFilter'] ?? false) && !($options['showJobsFilter'] ?? false) && !($options['showEmployeesFilter'] ?? false)) md:col-start-4 @endif">
                    <div class="flex items-center gap-1 mb-1">
                        <flux:label>Results per Page</flux:label>
                        <flux:icon.question-mark-circle
                            class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help"
                            tooltip="Number of companies to display per page"
                        />
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
            @if($search || $cityFilter || $statusFilter || $hasVatFilter || $hasJobsFilter || $minEmployeesFilter)
            <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <div class="flex flex-wrap gap-2">
                    @if($search)
                    <flux:badge variant="outline" size="sm">
                        Search: "{{ $search }}"
                        <flux:button size="xs" variant="ghost" wire:click="$set('search', '')" class="ml-1">
                            <flux:icon.x-mark class="w-3 h-3" />
                        </flux:button>
                    </flux:badge>
                    @endif

                    @if($cityFilter)
                    <flux:badge variant="outline" size="sm">
                        City: {{ $cityFilter }}
                        <flux:button size="xs" variant="ghost" wire:click="$set('cityFilter', '')" class="ml-1">
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

                    @if($minEmployeesFilter)
                    <flux:badge variant="outline" size="sm">
                        Employees: {{ $employeeRangeOptions[$minEmployeesFilter] }}
                        <flux:button size="xs" variant="ghost" wire:click="$set('minEmployeesFilter', '')" class="ml-1">
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
