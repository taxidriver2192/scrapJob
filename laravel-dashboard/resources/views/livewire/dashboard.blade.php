@push('breadcrumbs')
    <livewire:components.breadcrumbs
        :items="[
            ['label' => 'Dashboard', 'icon' => 'squares-2x2']
        ]"
    />
@endpush

<div>
    <livewire:components.headline
        title="Dashboard Overview"
        subtitle="Get an overview of all jobs, companies, and AI ratings in your system."
        icon="squares-2x2"
    />

    <!-- Statistics Cards -->
    <livewire:components.statistics-cards :cards="[
        ['title' => 'Total Jobs',     'value' => $totalJobs,      'color' => 'blue',   'icon' => 'briefcase'],
        ['title' => 'Companies',      'value' => $totalCompanies, 'color' => 'green',  'icon' => 'building'],
        ['title' => 'AI Ratings',     'value' => $totalRatings,   'color' => 'yellow', 'icon' => 'star'],
        ['title' => 'Avg Match Score','value' => $avgScore ? round($avgScore) . '%' : 'N/A', 'color' => 'indigo', 'icon' => 'chart-bar']
    ]" />

    <!-- Advanced Search and Filters -->
    <livewire:search-filters.index
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
