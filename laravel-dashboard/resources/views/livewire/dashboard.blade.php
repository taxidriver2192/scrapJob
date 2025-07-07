<div>
    <flux:main class="max-w-7xl mx-auto px-4 bg-white dark:bg-zinc-900">
        <flux:heading size="xl" class="text-blue-600 dark:text-blue-400 mb-6">
            <i class="fas fa-chart-line mr-2"></i>Dashboard Overview
        </flux:heading>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <flux:card class="bg-blue-600 text-white hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between p-6">
                    <div>
                        <flux:heading size="2xl" class="text-white">{{ $totalJobs }}</flux:heading>
                        <p class="text-blue-100">Total Jobs</p>
                    </div>
                    <i class="fas fa-briefcase text-4xl text-blue-200"></i>
                </div>
            </flux:card>

            <flux:card class="bg-green-600 text-white hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between p-6">
                    <div>
                        <flux:heading size="2xl" class="text-white">{{ $totalCompanies }}</flux:heading>
                        <p class="text-green-100">Companies</p>
                    </div>
                    <i class="fas fa-building text-4xl text-green-200"></i>
                </div>
            </flux:card>

            <flux:card class="bg-yellow-600 text-white hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between p-6">
                    <div>
                        <flux:heading size="2xl" class="text-white">{{ $totalRatings }}</flux:heading>
                        <p class="text-yellow-100">AI Ratings</p>
                    </div>
                    <i class="fas fa-star text-4xl text-yellow-200"></i>
                </div>
            </flux:card>

            <flux:card class="bg-indigo-600 text-white hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between p-6">
                    <div>
                        <flux:heading size="2xl" class="text-white">{{ $avgScore ? round($avgScore) : 'N/A' }}</flux:heading>
                        <p class="text-indigo-100">Avg Match Score</p>
                    </div>
                    <i class="fas fa-chart-bar text-4xl text-indigo-200"></i>
                </div>
            </flux:card>
        </div>

        <!-- Advanced Search and Filters -->
        <flux:card class="mb-8 bg-white dark:bg-zinc-900">
            <div class="p-6">
                <flux:heading size="lg" class="mb-4 text-zinc-900 dark:text-zinc-100">
                    <i class="fas fa-search mr-2 text-zinc-600 dark:text-zinc-400"></i>Search & Filters
                </flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <!-- Global Search -->
                    <div>
                        <flux:label>Search Jobs</flux:label>
                        <flux:input
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search jobs, companies, descriptions..."
                            icon="search"
                        />
                    </div>

                    <!-- Company Filter with Autocomplete -->
                    <div>
                        <flux:label>Company</flux:label>
                        <flux:autocomplete wire:model.live="companyFilter" placeholder="Select or type company...">
                            @foreach($companies as $companyId => $companyName)
                                <flux:autocomplete.item value="{{ $companyName }}">
                                    {{ $companyName }}
                                </flux:autocomplete.item>
                            @endforeach
                        </flux:autocomplete>
                    </div>

                    <!-- Location Filter with Autocomplete -->
                    <div>
                        <flux:label>Location</flux:label>
                        <flux:autocomplete wire:model.live="locationFilter" placeholder="Select or type location...">
                            @foreach($locations as $location)
                                <flux:autocomplete.item value="{{ $location }}">
                                    {{ $location }}
                                </flux:autocomplete.item>
                            @endforeach
                        </flux:autocomplete>
                    </div>

                    <!-- Items per page -->
                    <div>
                        <flux:label>Items per page</flux:label>
                        <flux:select wire:model.live="perPage">
                            <option value="5">5 per page</option>
                            <option value="10">10 per page</option>
                            <option value="20">20 per page</option>
                            <option value="50">50 per page</option>
                        </flux:select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Date From -->
                    <div>
                        <flux:label>Posted From</flux:label>
                        <flux:input
                            type="date"
                            wire:model.live="dateFromFilter"
                        />
                    </div>

                    <!-- Date To -->
                    <div>
                        <flux:label>Posted To</flux:label>
                        <flux:input
                            type="date"
                            wire:model.live="dateToFilter"
                        />
                    </div>

                    <!-- Clear Filters -->
                    <div class="flex items-end">
                        <flux:button wire:click="clearFilters" variant="outline" icon="x">
                            Clear Filters
                        </flux:button>
                    </div>
                </div>
            </div>
        </flux:card>

        <!-- Advanced Jobs Table -->
        <flux:card class="bg-white dark:bg-zinc-900">
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-6">
                <div class="flex justify-between items-center">
                    <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-100">
                        <i class="fas fa-table mr-2 text-zinc-600 dark:text-zinc-400"></i>Jobs ({{ $jobs->total() }} results)
                    </flux:heading>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">
                        Showing {{ $jobs->firstItem() ?? 0 }} to {{ $jobs->lastItem() ?? 0 }} of {{ $jobs->total() }} results
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th wire:click="sortBy('title')" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-700">
                                <div class="flex items-center">
                                    Title
                                    @if($sortField === 'title')
                                        <i class="fas fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1 text-xs"></i>
                                    @endif
                                </div>
                            </th>

                            <th wire:click="sortBy('company_id')" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-700">
                                <div class="flex items-center">
                                    Company
                                    @if($sortField === 'company_id')
                                        <i class="fas fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1 text-xs"></i>
                                    @endif
                                </div>
                            </th>

                            <th wire:click="sortBy('location')" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-700">
                                <div class="flex items-center">
                                    Location
                                    @if($sortField === 'location')
                                        <i class="fas fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1 text-xs"></i>
                                    @endif
                                </div>
                            </th>

                            <th wire:click="sortBy('posted_date')" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-700">
                                <div class="flex items-center">
                                    Posted Date
                                    @if($sortField === 'posted_date')
                                        <i class="fas fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1 text-xs"></i>
                                    @endif
                                </div>
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">AI Rating</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($jobs as $job)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="px-6 py-4 text-sm text-zinc-900 dark:text-zinc-100">
                                <div>
                                    <div class="font-medium">{{ $job->title }}</div>
                                    @if($job->description)
                                        <div class="text-sm text-zinc-500 truncate max-w-xs">{{ Str::limit($job->description, 100) }}</div>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                <div class="flex items-center">
                                    <i class="fas fa-building mr-2 text-zinc-400 dark:text-zinc-500"></i>
                                    {{ $job->company->name ?? 'N/A' }}
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                <div class="flex items-center">
                                    <i class="fas fa-map-marker-alt mr-2 text-zinc-400 dark:text-zinc-500"></i>
                                    {{ $job->location ?? 'Not specified' }}
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                @if($job->posted_date)
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar mr-2 text-zinc-400 dark:text-zinc-500"></i>
                                        {{ $job->posted_date->format('M j, Y') }}
                                    </div>
                                @else
                                    <span class="text-zinc-500 dark:text-zinc-400">N/A</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                @if($job->jobRatings->isNotEmpty())
                                    @php $latestRating = $job->jobRatings->first(); @endphp
                                    <flux:badge color="{{ $latestRating->overall_score >= 80 ? 'green' : ($latestRating->overall_score >= 60 ? 'yellow' : 'red') }}">
                                        {{ $latestRating->overall_score }}%
                                    </flux:badge>
                                @else
                                    <span class="text-zinc-500 dark:text-zinc-400">Not rated</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
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
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                <i class="fas fa-search text-4xl mb-4"></i>
                                <p class="text-lg">No jobs found matching your criteria.</p>
                                <p class="text-sm">Try adjusting your search or filters.</p>
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
