<div>
    <flux:main class="max-w-7xl mx-auto px-4">
        <flux:heading size="xl" class="text-blue-600 mb-6">
            <i class="fas fa-list mr-2"></i>Job Queue
        </flux:heading>

        <!-- Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <flux:card class="bg-yellow-600 text-white hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between p-6">
                    <div>
                        <flux:heading size="2xl" class="text-white">{{ $statusCounts['pending'] }}</flux:heading>
                        <p class="text-yellow-100">Pending</p>
                    </div>
                    <i class="fas fa-clock text-4xl text-yellow-200"></i>
                </div>
            </flux:card>

            <flux:card class="bg-blue-600 text-white hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between p-6">
                    <div>
                        <flux:heading size="2xl" class="text-white">{{ $statusCounts['in_progress'] }}</flux:heading>
                        <p class="text-blue-100">In Progress</p>
                    </div>
                    <i class="fas fa-spinner text-4xl text-blue-200"></i>
                </div>
            </flux:card>

            <flux:card class="bg-green-600 text-white hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between p-6">
                    <div>
                        <flux:heading size="2xl" class="text-white">{{ $statusCounts['done'] }}</flux:heading>
                        <p class="text-green-100">Completed</p>
                    </div>
                    <i class="fas fa-check text-4xl text-green-200"></i>
                </div>
            </flux:card>

            <flux:card class="bg-red-600 text-white hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between p-6">
                    <div>
                        <flux:heading size="2xl" class="text-white">{{ $statusCounts['error'] }}</flux:heading>
                        <p class="text-red-100">Errors</p>
                    </div>
                    <i class="fas fa-exclamation-triangle text-4xl text-red-200"></i>
                </div>
            </flux:card>
        </div>

        <!-- Filter Row -->
        <div class="flex gap-4 mb-6">
            <div class="flex-1">
                <flux:select wire:model.live="statusFilter">
                    <option value="">All Status</option>
                    <option value="1">Pending</option>
                    <option value="2">In Progress</option>
                    <option value="3">Done</option>
                    <option value="4">Error</option>
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

        <!-- Queue Table -->
        <flux:card>
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-6">
                <flux:heading size="lg">
                    <i class="fas fa-list mr-2"></i>Queue Items
                </flux:heading>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Job Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Company</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Queued At</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($queueItems as $item)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $item->jobPosting->title ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">{{ $item->jobPosting->company->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">{{ $item->queued_at->format('Y-m-d H:i:s') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                <flux:badge color="{{ $item->status_code == 1 ? 'yellow' : ($item->status_code == 2 ? 'blue' : ($item->status_code == 3 ? 'green' : 'red')) }}">
                                    {{ $item->status_text }}
                                </flux:badge>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center">
                                <div class="text-gray-500">
                                    <i class="fas fa-list text-4xl mb-4"></i>
                                    <p class="text-lg">No queue items found.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-6">
                {{ $queueItems->links() }}
            </div>
        </flux:card>
    </flux:main>
</div>
