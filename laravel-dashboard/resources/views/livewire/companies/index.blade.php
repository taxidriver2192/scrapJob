<div>
    @push('breadcrumbs')
        <livewire:components.breadcrumbs
            :items="[
                ['label' => 'Companies', 'icon' => 'building-office']
            ]"
        />
    @endpush


    <livewire:components.headline
        title="Companies Overview"
        subtitle="Comprehensive overview of all companies with statistics and detailed insights."
        icon="building-office"
    />

    <!-- Statistics Cards -->
    <livewire:components.statistics-cards :cards="[
        ['title' => 'Total Companies', 'value' => $totalCompanies, 'color' => 'blue', 'icon' => 'building-office'],
        ['title' => 'With VAT Data', 'value' => $companiesWithVat, 'color' => 'green', 'icon' => 'document-check'],
        ['title' => 'With Job Postings', 'value' => $companiesWithJobs, 'color' => 'yellow', 'icon' => 'briefcase'],
        ['title' => 'Avg Employees', 'value' => $avgEmployees ? round($avgEmployees) : 'N/A', 'color' => 'indigo', 'icon' => 'users']
    ]" />

    <!-- Advanced Search and Filters -->
    <livewire:companies.company-filters
        :locations="$locations"
        :options="[
            'title' => 'Company Search & Filters',
            'showPerPage' => true,
            'showStatusFilter' => true,
            'showVatFilter' => true,
            'showJobsFilter' => true,
            'showEmployeesFilter' => true
        ]"
    />

    <!-- Companies Table -->
    <livewire:companies.company-table
        :tableConfig="$tableConfig"
    />

</div>
