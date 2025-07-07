<div>
    <flux:main class="max-w-7xl mx-auto px-4">
        <flux:heading size="xl" class="text-blue-600 mb-6">
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

        <!-- Recent Jobs Table -->
        <flux:card>
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-6">
                <flux:heading size="lg">
                    <i class="fas fa-clock mr-2"></i>Recent Jobs
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($recentJobs as $job)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $job->title }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">{{ $job->company->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">{{ $job->location }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">{{ $job->posted_date ? $job->posted_date->format('Y-m-d') : 'N/A' }}</td>
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
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-6 text-center">
                <flux:button href="/jobs" variant="primary">
                    View All Jobs
                </flux:button>
            </div>
        </flux:card>
    </flux:main>
</div>
