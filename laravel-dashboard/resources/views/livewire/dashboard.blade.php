<div>
    <flux:main class="max-w-7xl mx-auto px-4 bg-white dark:bg-zinc-900">
        <flux:heading size="xl" class="text-blue-600 dark:text-blue-400 mb-6">
            <i class="fas fa-chart-line mr-2"></i>Dashboard Overview
        </flux:heading>

        <!-- Statistics Cards -->
        <livewire:components.statistics-cards :cards="[
            ['title' => 'Total Jobs', 'value' => $totalJobs, 'color' => 'blue', 'icon' => 'briefcase'],
            ['title' => 'Companies', 'value' => $totalCompanies, 'color' => 'green', 'icon' => 'building'],
            ['title' => 'AI Ratings', 'value' => $totalRatings, 'color' => 'yellow', 'icon' => 'star'],
            ['title' => 'Avg Match Score', 'value' => $avgScore ? round($avgScore) . '%' : 'N/A', 'color' => 'indigo', 'icon' => 'chart-bar']
        ]" />

        <!-- Advanced Search and Filters -->
        <livewire:components.search-filters
            :companies="$companies"
            :locations="$locations"
            :options="[
                'title' => 'Search & Filters',
                'showPerPage' => true,
                'showDateFilters' => true
            ]"
        />

        <!-- Advanced Jobs Table -->
        <livewire:jobs.job-table
            :tableConfig="$tableConfig"
            :jobId="$jobId"
        />

    </flux:main>
</div>
