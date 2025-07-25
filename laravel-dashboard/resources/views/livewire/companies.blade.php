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

    <!-- Company Filters Component -->
    <livewire:companies.company-filters
        :locations="$locations"
        :options="$filterOptions"
    />

    <!-- Company Table Component -->
    <livewire:companies.company-table
        :tableConfig="$tableConfig"
    />
</div>
