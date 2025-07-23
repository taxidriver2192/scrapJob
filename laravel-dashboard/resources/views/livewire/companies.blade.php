@push('breadcrumbs')
    <livewire:components.breadcrumbs
        :items="[
            ['label' => 'Companies', 'icon' => 'building-office']
        ]"
    />
@endpush

<div>
    <livewire:components.headline
        title="Companies"
        subtitle="Explore companies and their job postings to find your perfect match."
        icon="building-office"
    />

    <!-- Search and Filter Row -->
    <div class="flex gap-4 mb-6">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search companies..."
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

    <!-- Companies Table -->
    <flux:card>
        <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-6">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">
                    <i class="fas fa-list mr-2"></i>Company Directory ({{ $companies->total() }} companies)
                </flux:heading>
                <div class="flex items-center space-x-2 text-sm text-zinc-500 dark:text-zinc-400">
                    <i class="fas fa-sort mr-1"></i>
                    <span>Sorted by:</span>
                    <flux:badge color="blue">
                        {{ ucfirst(str_replace('_', ' ', $sortField)) }}
                        @if($sortDirection === 'asc')
                            ↑
                        @else
                            ↓
                        @endif
                    </flux:badge>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            <button wire:click="sortBy('name')" class="flex items-center space-x-1 hover:text-zinc-700 dark:hover:text-zinc-200">
                                <span>Company Name</span>
                                @if($sortField === 'name')
                                    @if($sortDirection === 'asc')
                                        <i class="fas fa-sort-up text-blue-500"></i>
                                    @else
                                        <i class="fas fa-sort-down text-blue-500"></i>
                                    @endif
                                @else
                                    <i class="fas fa-sort text-zinc-400"></i>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            <button wire:click="sortBy('job_postings_count')" class="flex items-center space-x-1 hover:text-zinc-700 dark:hover:text-zinc-200">
                                <span>Job Count</span>
                                @if($sortField === 'job_postings_count')
                                    @if($sortDirection === 'asc')
                                        <i class="fas fa-sort-up text-blue-500"></i>
                                    @else
                                        <i class="fas fa-sort-down text-blue-500"></i>
                                    @endif
                                @else
                                    <i class="fas fa-sort text-zinc-400"></i>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($companies as $company)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-zinc-100">
                            <flux:heading size="sm">{{ $company->name }}</flux:heading>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                            <flux:badge color="blue">{{ $company->job_postings_count }} jobs</flux:badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                            <flux:button
                                href="/jobs?search={{ urlencode($company->name) }}"
                                size="sm"
                                variant="outline"
                                icon="eye"
                            >
                                View Jobs
                            </flux:button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-search text-4xl mb-4"></i>
                                <p class="text-lg">No companies found matching your search criteria.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-6">
            {{ $companies->links() }}
        </div>
    </flux:card>
</div>
