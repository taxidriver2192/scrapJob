<div>
    <flux:main class="max-w-7xl mx-auto px-4">
        <flux:heading size="xl" class="text-blue-600 mb-6">
            <i class="fas fa-star mr-2"></i>Job Ratings
        </flux:heading>

        <!-- Search and Filter Row -->
        <div class="flex gap-4 mb-6">
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search jobs or companies..."
                    icon="magnifying-glass"
                />
            </div>
            <div class="w-64">
                <flux:select wire:model.live="ratingTypeFilter">
                    <option value="">All Rating Types</option>
                    @foreach($ratingTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </flux:select>
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

        <!-- Ratings Table -->
        <flux:card>
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-6">
                <flux:heading size="lg">
                    <i class="fas fa-list mr-2"></i>AI Job Ratings
                </flux:heading>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Job Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Company</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Overall Score</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tech</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Team Size</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Leadership</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Rated At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($ratings as $rating)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $rating->jobPosting->title ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $rating->jobPosting->company->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                @if($rating->overall_score)
                                    <flux:badge color="{{ $rating->overall_score >= 80 ? 'green' : ($rating->overall_score >= 60 ? 'yellow' : 'red') }}">
                                        {{ $rating->overall_score }}%
                                    </flux:badge>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                @if($rating->location_score)
                                    <flux:badge color="gray">{{ $rating->location_score }}%</flux:badge>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                @if($rating->tech_score)
                                    <flux:badge color="gray">{{ $rating->tech_score }}%</flux:badge>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                @if($rating->team_size_score)
                                    <flux:badge color="gray">{{ $rating->team_size_score }}%</flux:badge>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                @if($rating->leadership_score)
                                    <flux:badge color="gray">{{ $rating->leadership_score }}%</flux:badge>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $rating->rated_at->format('Y-m-d H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-star text-4xl mb-4"></i>
                                <p class="text-lg">No ratings found matching your criteria.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-6">
                {{ $ratings->links() }}
            </div>
        </flux:card>
    </flux:main>
</div>
