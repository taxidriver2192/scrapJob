<flux:card class="bg-white dark:bg-zinc-900">
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-6">
        <div class="flex justify-between items-center">
            <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-100">
                <i class="fas fa-table mr-2 text-zinc-600 dark:text-zinc-400"></i>{{ $title }} ({{ $totalResults }} results)
            </flux:heading>
            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $jobs->count() }} results displayed
            </div>
        </div>
    </div>

    <flux:table :paginate="$jobs">
        <flux:table.columns>

            @if($showRating)
                <flux:table.column>AI Rating</flux:table.column>
            @endif

            @foreach($columns as $field => $label)
                <flux:table.column
                    sortable
                    :sorted="$sortField === $field"
                    :direction="$sortDirection"
                    wire:click="sortBy('{{ $field }}')"
                >
                    {{ $label }}
                </flux:table.column>
            @endforeach

            @if($showActions)
                <flux:table.column>Actions</flux:table.column>
            @endif
        </flux:table.columns>

        <flux:table.rows>
            @forelse($jobs as $job)
                <flux:table.row :key="$job->job_id">

                    @if($showRating)
                        <flux:table.cell class="whitespace-nowrap">
                            @if($job->jobRatings && $job->jobRatings->isNotEmpty())
                                @php $latestRating = $job->jobRatings->first(); @endphp
                                <button wire:click="viewJobRating({{ $job->job_id }})" class="hover:scale-105 transition-transform">
                                    <flux:badge color="{{ $latestRating->overall_score >= 80 ? 'green' : ($latestRating->overall_score >= 60 ? 'yellow' : 'red') }}">
                                        {{ $latestRating->overall_score }}%
                                    </flux:badge>
                                </button>
                            @else
                                <button wire:click="viewJobRating({{ $job->job_id }})" class="text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                                    Not rated
                                </button>
                            @endif
                        </flux:table.cell>
                    @endif

                    @if(isset($columns['title']))
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
                    @endif

                    @if(isset($columns['company']))
                        <flux:table.cell class="whitespace-nowrap">
                            <div class="flex items-center">
                                <i class="fas fa-building mr-2 text-zinc-400 dark:text-zinc-500"></i>
                                {{ $job->company->name ?? 'N/A' }}
                            </div>
                        </flux:table.cell>
                    @endif

                    @if(isset($columns['location']))
                        <flux:table.cell class="whitespace-nowrap">
                            <div class="flex items-center">
                                <i class="fas fa-map-marker-alt mr-2 text-zinc-400 dark:text-zinc-500"></i>
                                {{ $job->location ?? 'Not specified' }}
                            </div>
                        </flux:table.cell>
                    @endif

                    @if(isset($columns['posted_date']))
                        <flux:table.cell class="whitespace-nowrap">
                            @if($job->posted_date)
                                <div class="flex items-center">
                                    <i class="fas fa-calendar mr-2 text-zinc-400 dark:text-zinc-500"></i>
                                    {{ $job->posted_date->format('M j, Y') }}
                                </div>
                            @else
                                <span class="text-zinc-500 dark:text-zinc-400">N/A</span>
                            @endif
                        </flux:table.cell>
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
                                        icon="external-link"
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
                    <flux:table.cell colspan="{{ count($columns) + ($showRating ? 1 : 0) + ($showActions ? 1 : 0) }}">
                        <div class="text-center text-zinc-500 dark:text-zinc-400 py-8">
                            <i class="fas fa-search text-4xl mb-4"></i>
                            <p class="text-lg">No jobs found matching your criteria.</p>
                            <p class="text-sm">Try adjusting your search or filters.</p>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <!-- Job Modal Component (embedded in job table) -->
    <livewire:job-modal
        wire:key="job-table-modal"
        :rating="null"
        :currentIndex="0"
        :total="0"
    />

</flux:card>
