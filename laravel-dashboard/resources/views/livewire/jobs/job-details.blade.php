<div>
    <flux:main class="max-w-7xl mx-auto px-4 bg-white dark:bg-zinc-900">
        <!-- Use the shared job content component -->
        <livewire:jobs.shared-job-content
            :jobPosting="$jobPosting"
            :rating="$rating"
            :currentIndex="$currentIndex"
            :total="$total"
            :showNavigation="true"
            :showBackButton="true"
            :key="'page-job-content-'.($jobPosting?->job_id ?? 'none')"
            @previousRating="previousRating"
            @nextRating="nextRating"
            @goBackToDashboard="goBackToDashboard"
        />
    </flux:main>
</div>
