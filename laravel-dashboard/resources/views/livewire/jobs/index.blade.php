<div>
    <flux:main class="max-w-7xl mx-auto px-4">
        <flux:heading size="xl" class="text-blue-600 mb-6">
            <flux:icon.briefcase class="mr-2" />Jobs
        </flux:heading>

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
            :options="[
                'showActions' => true,
                'showRating' => true,
                'title' => 'Job Listings',
                'columns' => [
                    'title' => 'Title',
                    'company' => 'Company',
                    'location' => 'Location',
                    'posted_date' => 'Posted Date'
                ]
            ]"
        />

        <!-- Job Modal Component -->
        <livewire:jobs.job-modal />
    </flux:main>
</div>
