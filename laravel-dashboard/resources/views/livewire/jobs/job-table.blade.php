<flux:card>
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-6">
        <div class="flex justify-between items-center">
            <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-100">
                {{ $title }} ({{ $totalResults }} results)
            </flux:heading>
            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $jobs->count() }} results displayed
            </div>
        </div>
    </div>

    <flux:table :paginate="$jobs">
        <flux:table.columns>
            <!-- Bulk Selection Column -->
            <flux:table.column class="w-12">
                <flux:checkbox
                    wire:click="toggleSelectAll"
                    :checked="$selectAll"
                />
            </flux:table.column>

            @if(isset($ratingColumns) && count($ratingColumns) > 0)
                @foreach($ratingColumns as $field => $label)
                    <flux:table.column
                        sortable
                        :sorted="$sortField === $field"
                        :direction="$sortDirection"
                        wire:click="sortBy('{{ $field }}')"
                    >
                        {{ $label }}
                    </flux:table.column>
                @endforeach
            @endif

            @if(isset($regularColumns) && count($regularColumns) > 0)
                @foreach($regularColumns as $field => $label)
                    <flux:table.column
                        sortable
                        :sorted="$sortField === $field"
                        :direction="$sortDirection"
                        wire:click="sortBy('{{ $field }}')"
                    >
                        {{ $label }}
                    </flux:table.column>
                @endforeach
            @endif

            @if($showActions)
                <flux:table.column>Actions</flux:table.column>
            @endif
        </flux:table.columns>

        <flux:table.rows>
            @forelse($jobs as $job)
                <flux:table.row :key="$job->job_id">
                    <!-- Bulk Selection Column -->
                    <flux:table.cell class="w-12">
                        <flux:checkbox
                            wire:click="toggleJobSelection({{ $job->job_id }})"
                            :checked="$this->isJobSelected($job->job_id)"
                        />
                    </flux:table.cell>

                    @if(isset($ratingColumns) && count($ratingColumns) > 0)
                        @foreach($ratingColumns as $field => $label)
                            <flux:table.cell class="whitespace-nowrap">
                                @php $score = $this->getRatingScore($job, $field); @endphp
                                @if($score !== null)
                                    <flux:tooltip :content="$this->getRatingTooltip($job, $field)">
                                        <flux:badge color="{{ $this->getRatingColor($score) }}" size="sm">
                                            {{ $score }}%
                                        </flux:badge>
                                    </flux:tooltip>
                                @else
                                    <flux:tooltip content="This job hasn't been rated yet.">
                                        <span class="text-zinc-400 dark:text-zinc-500">-</span>
                                    </flux:tooltip>
                                @endif
                            </flux:table.cell>
                        @endforeach
                    @endif

                    @if(isset($regularColumns) && count($regularColumns) > 0)
                        @foreach($regularColumns as $field => $label)
                            @if($field === 'title')
                                <flux:table.cell>
                                    <div>
                                        @if($linkToDetailsPage)
                                            <a href="{{ route('job.details', ['jobId' => $job->job_id]) }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors group text-left block">
                                                <div class="flex items-center gap-2">
                                                    <div class="flex gap-1">
                                                        @if($this->isJobClosed($job->job_id))
                                                            <flux:badge color="red" size="sm" class="shrink-0">
                                                                <flux:icon.x-circle class="w-3 h-3 mr-1" />
                                                                Closed
                                                            </flux:badge>
                                                        @endif
                                                        @if(auth()->check() && $this->isJobViewed($job->job_id))
                                                            <flux:badge color="zinc" size="sm" class="shrink-0">
                                                                Seen
                                                            </flux:badge>
                                                        @endif
                                                        @if(auth()->check() && $this->isJobRated($job->job_id))
                                                            <flux:badge color="green" size="sm" class="shrink-0">
                                                                <flux:icon.sparkles class="w-3 h-3 mr-1" />
                                                                Rated
                                                            </flux:badge>
                                                        @endif
                                                        @if(auth()->check() && $this->isJobFavorited($job->job_id))
                                                            <flux:badge color="yellow" size="sm" class="shrink-0">
                                                                <flux:icon.bookmark class="w-3 h-3 mr-1" />
                                                                Saved
                                                            </flux:badge>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        @if(strlen($job->title) > 50)
                                                            <flux:tooltip :content="$job->title">
                                                                <div class="font-medium cursor-help">{{ Str::limit($job->title, 50) }}</div>
                                                            </flux:tooltip>
                                                        @else
                                                            <div class="font-medium">{{ $job->title }}</div>
                                                        @endif
                                                        @if($job->description)
                                                            <div class="text-sm text-zinc-500 dark:text-zinc-400 truncate max-w-[300px]">{{ Str::limit(strip_tags($job->description), 100) }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </a>
                                        @else
                                            <button wire:click="viewJobRating({{ $job->job_id }})" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors group text-left">
                                                <div class="flex items-center gap-2">
                                                    <div class="flex gap-1">
                                                        @if($this->isJobClosed($job->job_id))
                                                            <flux:badge color="red" size="sm" class="shrink-0">
                                                                <flux:icon.x-circle class="w-3 h-3 mr-1" />
                                                                Closed
                                                            </flux:badge>
                                                        @endif
                                                        @if(auth()->check() && $this->isJobViewed($job->job_id))
                                                            <flux:badge color="zinc" size="sm" class="shrink-0">
                                                                Seen
                                                            </flux:badge>
                                                        @endif
                                                        @if(auth()->check() && $this->isJobRated($job->job_id))
                                                            <flux:badge color="green" size="sm" class="shrink-0">
                                                                <flux:icon.sparkles class="w-3 h-3 mr-1" />
                                                                Rated
                                                            </flux:badge>
                                                        @endif
                                                        @if(auth()->check() && $this->isJobFavorited($job->job_id))
                                                            <flux:badge color="yellow" size="sm" class="shrink-0">
                                                                <flux:icon.bookmark class="w-3 h-3 mr-1" />
                                                                Saved
                                                            </flux:badge>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        @if(strlen($job->title) > 50)
                                                            <flux:tooltip :content="$job->title">
                                                                <div class="font-medium cursor-help">{{ Str::limit($job->title, 50) }}</div>
                                                            </flux:tooltip>
                                                        @else
                                                            <div class="font-medium">{{ $job->title }}</div>
                                                        @endif
                                                        @if($job->description)
                                                            <div class="text-sm text-zinc-500 dark:text-zinc-400 truncate max-w-[300px]">{{ Str::limit(strip_tags($job->description), 100) }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </button>
                                        @endif
                                    </div>
                                </flux:table.cell>
                            @elseif($field === 'company')
                                <flux:table.cell class="whitespace-nowrap">
                                    @if($job->company)
                                        <a href="{{ route('company.details', ['companyId' => $job->company->company_id]) }}" class="flex items-center hover:text-blue-600 dark:hover:text-blue-400 transition-colors group">
                                            <flux:icon.building-office class="mr-2 text-zinc-400 dark:text-zinc-500 group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors" />
                                            {{ $job->company->name }}
                                        </a>
                                    @else
                                        <div class="flex items-center text-zinc-500 dark:text-zinc-400">
                                            <flux:icon.building-office class="mr-2" />
                                            N/A
                                        </div>
                                    @endif
                                </flux:table.cell>
                            @elseif($field === 'city')
                                <flux:table.cell class="whitespace-nowrap">
                                    <div class="flex items-center">
                                        <flux:icon.map-pin class="mr-2 text-zinc-400 dark:text-zinc-500" />
                                        {{ $job->city ?? 'Not specified' }}
                                    </div>
                                </flux:table.cell>
                            @elseif($field === 'zipcode')
                                <flux:table.cell class="whitespace-nowrap">
                                    <div class="flex items-center">
                                        <flux:icon.map class="mr-2 text-zinc-400 dark:text-zinc-500" />
                                        {{ $job->zipcode ?? 'Not specified' }}
                                    </div>
                                </flux:table.cell>
                            @elseif($field === 'location')
                                <flux:table.cell class="whitespace-nowrap">
                                    <div class="flex items-center">
                                        <flux:icon.map-pin class="mr-2 text-zinc-400 dark:text-zinc-500" />
                                        {{ $job->location ?? 'Not specified' }}
                                    </div>
                                </flux:table.cell>
                            @elseif($field === 'posted_date')
                                <flux:table.cell class="whitespace-nowrap">
                                    @if($job->posted_date)
                                        <div class="flex items-center">
                                            <flux:icon.calendar class="mr-2 text-zinc-400 dark:text-zinc-500" />
                                            {{ \Carbon\Carbon::parse($job->posted_date)->format('M j, Y') }}
                                        </div>
                                    @else
                                        <span class="text-zinc-500 dark:text-zinc-400">N/A</span>
                                    @endif
                                </flux:table.cell>
                            @endif
                        @endforeach
                    @endif

                    @if($showActions)
                        <flux:table.cell>
                            <div class="flex space-x-2">
                                @if($job->apply_url)
                                    <flux:button
                                        href="{{ $job->apply_url }}"
                                        target="_blank"
                                        size="sm"
                                        variant="primary"
                                        icon="arrow-top-right-on-square"
                                    >
                                        Apply
                                    </flux:button>
                                @endif

                                @if($job->job_url)
                                    <flux:button
                                        href="{{ $job->job_url }}"
                                        target="_blank"
                                        size="sm"
                                        variant="outline"
                                        icon="eye"
                                    >
                                        View
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    @endif
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="{{ 1 + count($ratingColumns ?? []) + count($regularColumns ?? []) + ($showActions ? 1 : 0) }}">
                        <div class="text-center text-zinc-500 dark:text-zinc-400 py-8">
                            <flux:icon.magnifying-glass class="text-4xl mb-4" />
                            <p class="text-lg">No jobs found matching your criteria.</p>
                            <p class="text-sm">Try adjusting your search or filters.</p>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <!-- Bulk Actions Section -->
    @if($showBulkActions)
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <span class="text-blue-800 dark:text-blue-200 font-medium">
                    {{ count($selectedJobs) }} job(s) selected
                    @if(auth()->check() && $this->getUnratedSelectedJobsCount() < count($selectedJobs))
                        <span class="text-sm text-blue-600 dark:text-blue-300">
                            ({{ $this->getUnratedSelectedJobsCount() }} unrated)
                        </span>
                    @endif
                </span>
                <flux:button
                    size="sm"
                    variant="outline"
                    wire:click="clearSelection"
                    icon="x-mark"
                >
                    Clear Selection
                </flux:button>
            </div>
            <div class="flex items-center space-x-2">
                <flux:button
                    size="sm"
                    variant="primary"
                    wire:click="queueSelectedJobsForRating"
                    icon="sparkles"
                    :disabled="auth()->check() && $this->getUnratedSelectedJobsCount() === 0"
                >
                    Queue for AI Rating
                    @if(auth()->check() && $this->getUnratedSelectedJobsCount() > 0)
                        ({{ $this->getUnratedSelectedJobsCount() }})
                    @endif
                </flux:button>
            </div>
        </div>
    </div>
    @endif

    <!-- Job Modal Component -->
    <livewire:jobs.job-modal
        :jobId="$jobId"
        :companyId="$companyFilter"
        :filterScope="[
            'search' => $search,
            'companyFilter' => $companyFilter,
            'regionFilter' => $regionFilter,
            'dateFromFilter' => $dateFromFilter,
            'dateToFilter' => $dateToFilter,
            'viewedStatusFilter' => $viewedStatusFilter
        ]"
        :key="'job-modal-' . ($jobId ?? 'default') . '-' . ($companyFilter ?? 'all') . '-' . md5(json_encode([$search, $companyFilter, $regionFilter, $dateFromFilter, $dateToFilter, $viewedStatusFilter]))"
    />

</flux:card>


