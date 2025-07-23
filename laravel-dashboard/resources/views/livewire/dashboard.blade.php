<div>
    <flux:heading size="xl" class="text-blue-600 dark:text-blue-400 mb-6">
        Dashboard Overview
    </flux:heading>

    <!-- Statistics Cards -->
    <livewire:components.statistics-cards :cards="[
        ['title' => 'Total Jobs',     'value' => $totalJobs,      'color' => 'blue',   'icon' => 'briefcase'],
        ['title' => 'Companies',      'value' => $totalCompanies, 'color' => 'green',  'icon' => 'building'],
        ['title' => 'AI Ratings',     'value' => $totalRatings,   'color' => 'yellow', 'icon' => 'star'],
        ['title' => 'Avg Match Score','value' => $avgScore ? round($avgScore) . '%' : 'N/A', 'color' => 'indigo', 'icon' => 'chart-bar']
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
</div>
