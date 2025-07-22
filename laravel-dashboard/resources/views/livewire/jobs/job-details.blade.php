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