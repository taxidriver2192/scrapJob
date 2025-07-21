<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <div class="mb-8">
            <flux:heading size="xl" class="mb-2">
                <flux:icon.sparkles class="mr-2 text-purple-500" />
                AI Job Rating History
            </flux:heading>
            <p class="text-zinc-600 dark:text-zinc-400">
                View your AI-powered job ratings, prompts, and responses with detailed analytics.
            </p>
        </div>

        <!-- Statistics Summary -->
        <div class="mb-6">
            <flux:card class="bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 border-purple-200 dark:border-purple-700">
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <!-- Total Jobs -->
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                {{ number_format($statistics['total_jobs']) }}
                            </div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                <flux:icon.document-text class="inline mr-1 size-4" />
                                Jobs Analyzed
                            </div>
                        </div>

                        <!-- Total Cost DKK -->
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ number_format($statistics['total_cost_dkk'], 2) }} DKK
                            </div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                <flux:icon.currency-dollar class="inline mr-1 size-4" />
                                Total Cost
                            </div>
                        </div>

                        <!-- Average Cost DKK -->
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ number_format($statistics['avg_cost_dkk'], 3) }} DKK
                            </div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                <flux:icon.calculator class="inline mr-1 size-4" />
                                Avg. per Job
                            </div>
                        </div>

                        <!-- Total Tokens -->
                        <div class="text-center">
                            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                {{ number_format($statistics['total_tokens']) }}
                            </div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                <flux:icon.cpu-chip class="inline mr-1 size-4" />
                                Total Tokens
                            </div>
                        </div>
                    </div>

                    <!-- Exchange Rate Notice -->
                    <div class="mt-4 pt-4 border-t border-purple-200 dark:border-purple-700">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 text-center">
                            <flux:icon.information-circle class="inline mr-1 size-3" />
                            Prices converted from USD to DKK at approximate rate (1 USD = 7.00 DKK)
                        </p>
                    </div>
                </div>
            </flux:card>
        </div>

        <!-- Job Filter Notification -->
        <div class="mb-6">
            @if($jobIdFilter)
            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                <div class="flex items-center">
                    <flux:icon.information-circle class="mr-2 text-blue-600 size-5" />
                    <span class="text-blue-800 dark:text-blue-200 font-medium">
                        Showing AI ratings for Job ID: {{ $jobIdFilter }}
                    </span>
                    <div class="ml-auto flex items-center space-x-2">
                        <flux:button
                            size="xs"
                            variant="outline"
                            href="{{ route('job', $jobIdFilter) }}"
                            class="text-blue-600 border-blue-300 hover:bg-blue-50"
                            icon="arrow-left"
                        >
                            View Job
                        </flux:button>
                        <flux:button
                            size="xs"
                            variant="ghost"
                            wire:click="clearJobFilter"
                            class="text-blue-600 hover:text-blue-800"
                            icon="x-mark"
                        >
                            Clear Filter
                        </flux:button>
                    </div>
                </div>
            </div>
            @endif
        </div>
        </div>

        <!-- Filters -->
        <flux:card class="mb-6">
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search prompts and responses..."
                            icon="magnifying-glass"
                        />
                    </div>
                    <div>
                        <flux:input
                            wire:model.live="jobIdFilter"
                            placeholder="Filter by Job ID..."
                            icon="identification"
                        />
                    </div>
                    <div>
                        <flux:select wire:model.live="modelFilter" placeholder="All Models">
                            @foreach($availableModels as $model)
                            <option value="{{ $model }}">{{ $model }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                @if($search || $jobIdFilter || $modelFilter)
                <div class="mt-4 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-zinc-500">Active filters:</span>
                        @if($search)
                        <flux:badge color="blue" size="sm">Search: {{ Str::limit($search, 20) }}</flux:badge>
                        @endif
                        @if($jobIdFilter)
                        <flux:badge color="green" size="sm">Job ID: {{ $jobIdFilter }}</flux:badge>
                        @endif
                        @if($modelFilter)
                        <flux:badge color="purple" size="sm">Model: {{ $modelFilter }}</flux:badge>
                        @endif
                    </div>
                    <flux:button size="sm" variant="subtle" wire:click="clearFilters" icon="x-mark">
                        Clear Filters
                    </flux:button>
                </div>
                @endif
            </div>
        </flux:card>

        @if($ratings->count() > 0)
        <div class="grid gap-6">
            @foreach($ratings as $rating)
            <flux:card class="hover:shadow-lg transition-shadow">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-2">
                                <flux:badge color="purple" size="sm">
                                    Job ID: {{ $rating->job_id }}
                                </flux:badge>
                                <flux:badge color="blue" size="sm">
                                    {{ $rating->model }}
                                </flux:badge>
                                @if($rating->total_tokens)
                                <flux:badge color="green" size="sm">
                                    {{ number_format($rating->total_tokens) }} tokens
                                </flux:badge>
                                @endif
                                @if($rating->cost)
                                <flux:badge color="yellow" size="sm">
                                    {{ number_format($this->convertToDkk($rating->cost), 3) }} DKK
                                </flux:badge>
                                @endif
                            </div>

                            <div class="text-sm text-zinc-500 dark:text-zinc-400 mb-3">
                                <flux:icon.calendar class="inline mr-1" />
                                Rated on {{ $rating->rated_at->format('M j, Y \a\t g:i A') }}
                            </div>

                            <!-- Response Preview (first 200 characters) -->
                            <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 mb-4">
                                <div class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                    <flux:icon.chat-bubble-left class="inline mr-1" />
                                    AI Response Preview:
                                </div>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400 font-mono">
                                    {{ Str::limit($rating->response, 200) }}
                                </div>
                            </div>

                            @if($rating->metadata)
                            <div class="grid grid-cols-3 gap-4 text-xs">
                                @if(data_get($rating->metadata, 'profile_completeness'))
                                <div class="text-center">
                                    <div class="text-zinc-500 dark:text-zinc-400">Profile Completeness</div>
                                    <div class="font-semibold text-blue-600">
                                        {{ data_get($rating->metadata, 'profile_completeness') }}%
                                    </div>
                                </div>
                                @endif

                                @if($rating->prompt_tokens)
                                <div class="text-center">
                                    <div class="text-zinc-500 dark:text-zinc-400">Prompt Tokens</div>
                                    <div class="font-semibold text-purple-600">
                                        {{ number_format($rating->prompt_tokens) }}
                                    </div>
                                </div>
                                @endif

                                @if($rating->completion_tokens)
                                <div class="text-center">
                                    <div class="text-zinc-500 dark:text-zinc-400">Response Tokens</div>
                                    <div class="font-semibold text-green-600">
                                        {{ number_format($rating->completion_tokens) }}
                                    </div>
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>

                        <div class="ml-4">
                            <flux:button
                                size="sm"
                                variant="outline"
                                href="{{ route('job-ratings.show', $rating) }}"
                                icon="eye"
                            >
                                View Details
                            </flux:button>
                        </div>
                    </div>
                </div>
            </flux:card>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $ratings->links() }}
        </div>

        @else
        <flux:card class="p-8 text-center">
            <flux:icon.sparkles class="mx-auto text-zinc-400 dark:text-zinc-500 size-16 mb-4" />
            <flux:heading size="md" class="text-zinc-600 dark:text-zinc-300 mb-2">
                @if($search || $jobIdFilter || $modelFilter)
                No Matching AI Ratings Found
                @else
                No AI Ratings Yet
                @endif
            </flux:heading>
            <p class="text-zinc-500 dark:text-zinc-400 text-sm mb-6">
                @if($search || $jobIdFilter || $modelFilter)
                Try adjusting your filters or clearing them to see more results.
                @else
                You haven't rated any jobs with AI yet. Start by visiting a job details page and clicking the "Rate This Job" button.
                @endif
            </p>
            @if($search || $jobIdFilter || $modelFilter)
            <flux:button wire:click="clearFilters" variant="outline" icon="arrow-path">
                Clear Filters
            </flux:button>
            @else
            <flux:button href="{{ route('jobs') }}" icon="arrow-right">
                Browse Jobs
            </flux:button>
            @endif
        </flux:card>
        @endif
    </div>
</div>
