@push('breadcrumbs')
    <livewire:components.breadcrumbs
        :items="[
            ['label' => 'Job Rating Queue', 'icon' => 'queue-list']
        ]"
    />
@endpush

<div>
    <livewire:components.headline
        title="Job Rating Queue"
        subtitle="Monitor and manage AI job rating queue across all users."
        icon="queue-list"
    />

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <flux:card class="text-center">
            <flux:badge color="blue" size="lg" class="mb-2">{{ $stats['total'] }}</flux:badge>
            <div class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Total Jobs</div>
        </flux:card>

        <flux:card class="text-center">
            <flux:badge color="yellow" size="lg" class="mb-2">{{ $stats['pending'] }}</flux:badge>
            <div class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Pending</div>
        </flux:card>

        <flux:card class="text-center">
            <flux:badge color="blue" size="lg" class="mb-2">{{ $stats['in_progress'] }}</flux:badge>
            <div class="text-sm font-medium text-zinc-600 dark:text-zinc-400">In Progress</div>
        </flux:card>

        <flux:card class="text-center">
            <flux:badge color="green" size="lg" class="mb-2">{{ $stats['completed'] }}</flux:badge>
            <div class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Completed</div>
        </flux:card>

        <flux:card class="text-center">
            <flux:badge color="red" size="lg" class="mb-2">{{ $stats['errors'] }}</flux:badge>
            <div class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Errors</div>
        </flux:card>
    </div>

    <!-- Filters -->
    <flux:card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <flux:field>
                    <flux:label>Search Jobs/Users</flux:label>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by job title or user..."
                        icon="magnifying-glass"
                    />
                </flux:field>
            </div>

            <!-- Status Filter -->
            <div>
                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model.live="statusFilter" placeholder="All Statuses">
                        <flux:select.option value="{{ \App\Models\JobQueue::STATUS_PENDING }}">Pending</flux:select.option>
                        <flux:select.option value="{{ \App\Models\JobQueue::STATUS_IN_PROGRESS }}">In Progress</flux:select.option>
                        <flux:select.option value="{{ \App\Models\JobQueue::STATUS_DONE }}">Completed</flux:select.option>
                        <flux:select.option value="{{ \App\Models\JobQueue::STATUS_ERROR }}">Error</flux:select.option>
                    </flux:select>
                </flux:field>
            </div>

            <!-- User Filter -->
            <div>
                <flux:field>
                    <flux:label>User</flux:label>
                    <flux:select wire:model.live="userFilter" placeholder="All Users">
                        @foreach($users as $user)
                            <flux:select.option value="{{ $user->id }}">{{ $user->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            <!-- Actions -->
            <div class="flex items-end gap-2">
                <flux:button wire:click="clearFilters" variant="outline" size="sm" icon="x-mark">
                    Clear Filters
                </flux:button>
            </div>
        </div>
    </flux:card>

    <!-- Queue Table -->
    <flux:card>
        <flux:table :paginate="$queueItems">
            <flux:table.columns>
                <flux:table.column
                    sortable
                    :sorted="$sortField === 'queued_at'"
                    :direction="$sortDirection"
                    wire:click="sortBy('queued_at')"
                >
                    Queued At
                </flux:table.column>

                <flux:table.column>Job Title</flux:table.column>
                <flux:table.column>Company</flux:table.column>
                <flux:table.column>User</flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortField === 'status_code'"
                    :direction="$sortDirection"
                    wire:click="sortBy('status_code')"
                >
                    Status
                </flux:table.column>

                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($queueItems as $queueItem)
                    <flux:table.row :key="$queueItem->queue_id">
                        <flux:table.cell class="whitespace-nowrap">
                            <div class="flex items-center">
                                <flux:icon.clock class="mr-2 text-zinc-400 dark:text-zinc-500" />
                                {{ $queueItem->queued_at->format('M j, Y H:i') }}
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if($queueItem->jobPosting)
                                <a href="{{ route('job.details', ['jobId' => $queueItem->job_id]) }}"
                                   class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                    <div class="font-medium">{{ Str::limit($queueItem->jobPosting->title, 40) }}</div>
                                    @if($queueItem->jobPosting->location)
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ $queueItem->jobPosting->location }}
                                        </div>
                                    @endif
                                </a>
                            @else
                                <span class="text-zinc-500 dark:text-zinc-400">Job not found</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            @if($queueItem->jobPosting?->company)
                                <a href="{{ route('company.details', ['companyId' => $queueItem->jobPosting->company->company_id]) }}"
                                   class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors flex items-center">
                                    <flux:icon.building-office class="mr-2 text-zinc-400 dark:text-zinc-500" />
                                    {{ $queueItem->jobPosting->company->name }}
                                </a>
                            @else
                                <span class="text-zinc-500 dark:text-zinc-400 flex items-center">
                                    <flux:icon.building-office class="mr-2" />
                                    N/A
                                </span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            @if($queueItem->user)
                                <div class="flex items-center">
                                    <flux:icon.user class="mr-2 text-zinc-400 dark:text-zinc-500" />
                                    <div>
                                        <div class="font-medium">{{ $queueItem->user->name }}</div>
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $queueItem->user->email }}</div>
                                    </div>
                                </div>
                            @else
                                <span class="text-zinc-500 dark:text-zinc-400">User not found</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="whitespace-nowrap">
                            @switch($queueItem->status_code)
                                @case(\App\Models\JobQueue::STATUS_PENDING)
                                    <flux:badge color="yellow" size="sm">
                                        <flux:icon.clock class="mr-1" />
                                        Pending
                                    </flux:badge>
                                    @break
                                @case(\App\Models\JobQueue::STATUS_IN_PROGRESS)
                                    <flux:badge color="blue" size="sm">
                                        <flux:icon.arrow-path class="mr-1" />
                                        In Progress
                                    </flux:badge>
                                    @break
                                @case(\App\Models\JobQueue::STATUS_DONE)
                                    <flux:badge color="green" size="sm">
                                        <flux:icon.check-circle class="mr-1" />
                                        Completed
                                    </flux:badge>
                                    @break
                                @case(\App\Models\JobQueue::STATUS_ERROR)
                                    <flux:badge color="red" size="sm">
                                        <flux:icon.exclamation-triangle class="mr-1" />
                                        Error
                                    </flux:badge>
                                    @break
                                @default
                                    <flux:badge color="gray" size="sm">Unknown</flux:badge>
                            @endswitch
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex space-x-2">
                                @if($queueItem->status_code === \App\Models\JobQueue::STATUS_ERROR)
                                    <flux:button
                                        size="sm"
                                        variant="outline"
                                        wire:click="retryJob({{ $queueItem->queue_id }})"
                                        icon="arrow-path"
                                        wire:confirm="Are you sure you want to retry this job?"
                                    >
                                        Retry
                                    </flux:button>
                                @endif

                                @if(in_array($queueItem->status_code, [\App\Models\JobQueue::STATUS_PENDING, \App\Models\JobQueue::STATUS_ERROR]))
                                    <flux:button
                                        size="sm"
                                        variant="danger"
                                        wire:click="cancelJob({{ $queueItem->queue_id }})"
                                        icon="trash"
                                        wire:confirm="Are you sure you want to cancel this job?"
                                    >
                                        Cancel
                                    </flux:button>
                                @endif

                                @if($queueItem->status_code === \App\Models\JobQueue::STATUS_DONE && $queueItem->jobPosting)
                                    @php
                                        $aiRating = $queueItem->ai_job_rating;
                                    @endphp
                                    @if($aiRating)
                                        <a href="{{ route('ai-job-ratings.show', $aiRating->id) }}"
                                           class="inline-flex">
                                            <flux:button
                                                size="sm"
                                                variant="outline"
                                                icon="eye"
                                            >
                                                View Rating
                                            </flux:button>
                                        </a>
                                    @else
                                        <flux:tooltip content="AI rating not found for this job">
                                            <flux:button
                                                size="sm"
                                                variant="outline"
                                                icon="exclamation-triangle"
                                                disabled
                                            >
                                                No Rating
                                            </flux:button>
                                        </flux:tooltip>
                                    @endif
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <div class="text-center text-zinc-500 dark:text-zinc-400 py-8">
                                <flux:icon.queue-list class="text-4xl mb-4" />
                                <p class="text-lg">No jobs in queue</p>
                                <p class="text-sm">Queue some jobs for AI rating to see them here.</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
