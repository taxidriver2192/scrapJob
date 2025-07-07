<div>
    <flux:main class="max-w-7xl mx-auto px-4">
        <flux:heading size="xl" class="text-blue-600 mb-6">
            <i class="fas fa-briefcase mr-2"></i>Jobs
        </flux:heading>

        <!-- Search and Filter Row -->
        <div class="flex gap-4 mb-6">
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search jobs, companies, or locations..."
                    icon="magnifying-glass"
                />
            </div>
            <div class="w-48">
                <flux:select wire:model.live="perPage">
                    <option value="10">10 per page</option>
                    <option value="20">20 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                </flux:select>
            </div>
        </div>

        <!-- Jobs Table -->
        <flux:card>
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-6">
                <flux:heading size="lg">
                    <i class="fas fa-list mr-2"></i>Job Listings
                </flux:heading>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Company</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Posted</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Work Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Match Score</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($jobs as $job)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                <div>
                                    <flux:heading size="sm">{{ $job->title }}</flux:heading>
                                    @if($job->applicants)
                                        <p class="text-sm text-gray-600">{{ $job->applicants }} applicants</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">{{ $job->company->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">{{ $job->location }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">{{ $job->posted_date ? $job->posted_date->format('Y-m-d') : 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                @if($job->work_type)
                                    <flux:badge color="gray">{{ $job->work_type }}</flux:badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                @if($job->jobRatings->isNotEmpty())
                                    @php $rating = $job->jobRatings->first(); @endphp
                                    <flux:badge color="{{ $rating->overall_score >= 80 ? 'green' : ($rating->overall_score >= 60 ? 'yellow' : 'red') }}">
                                        {{ $rating->overall_score }}%
                                    </flux:badge>
                                @else
                                    <span class="text-gray-500">No rating</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
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
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center">
                                <div class="text-gray-500">
                                    <i class="fas fa-search text-4xl mb-4"></i>
                                    <p class="text-lg">No jobs found matching your search criteria.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-6">
                {{ $jobs->links() }}
            </div>
        </flux:card>
    </flux:main>
</div>
