<flux:card class="bg-white dark:bg-zinc-900">
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-6">
        <div class="flex justify-between items-center">
            <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-100">
                <flux:icon.table-cells class="mr-2 text-zinc-600 dark:text-zinc-400" />{{ $title }} ({{ $totalResults }} results)
            </flux:heading>
            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $jobs->count() }} results displayed
            </div>
        </div>
    </div>

    <flux:table :paginate="$jobs">
        <flux:table.columns>

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

                    @if(isset($ratingColumns) && count($ratingColumns) > 0)
                        @foreach($ratingColumns as $field => $label)
                            <flux:table.cell class="whitespace-nowrap">
                                @php $score = $this->getRatingScore($job, $field); @endphp
                                @if($score !== null)
                                    <flux:tooltip :content="$this->getRatingTooltip($job, $field)">
                                        <button wire:click="viewJobRating({{ $job->job_id }})" class="hover:scale-105 transition-transform">
                                            <flux:badge color="{{ $this->getRatingColor($score) }}" size="sm">
                                                {{ $score }}%
                                            </flux:badge>
                                        </button>
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
                                </flux:table.cell>
                            @elseif($field === 'company')
                                <flux:table.cell class="whitespace-nowrap">
                                    <div class="flex items-center">
                                        <flux:icon.building-office class="mr-2 text-zinc-400 dark:text-zinc-500" />
                                        {{ $job->company->name ?? 'N/A' }}
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
                    <flux:table.cell colspan="{{ count($ratingColumns ?? []) + count($regularColumns ?? []) + ($showActions ? 1 : 0) }}">
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

    <!-- Job Modal Component -->
    <livewire:jobs.job-modal />

</flux:card>


