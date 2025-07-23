@push('breadcrumbs')
    <livewire:components.breadcrumbs
        :items="[
            ['label' => 'Jobs', 'icon' => 'briefcase']
        ]"
    />
@endpush

<div>
    <livewire:components.headline
        title="Jobs"
        subtitle="Browse and search through all available job postings in the system."
        icon="briefcase"
    />

    <!-- Search and Filter Row -->
    <div class="flex gap-4 mb-6">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search jobs, companies, or locations..."
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

    <!-- Jobs Table Component -->
    <livewire:jobs.job-table
        :tableConfig="[
            'title' => 'Job Listings',
            'showActions' => true,
            'showRating' => true,
            'showDetailedRatings' => false,
            'linkToDetailsPage' => true,
            'columns' => [
                'title' => ['enabled' => true, 'label' => 'Title', 'type' => 'regular'],
                'company' => ['enabled' => true, 'label' => 'Company', 'type' => 'regular'],
                'location' => ['enabled' => true, 'label' => 'Location', 'type' => 'regular'],
                'posted_date' => ['enabled' => true, 'label' => 'Posted Date', 'type' => 'regular']
            ]
        ]"
    />
</div>
