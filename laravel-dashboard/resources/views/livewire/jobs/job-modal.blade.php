<div>
    <!-- Job Details Modal -->
    <flux:modal name="job-rating-modal"
                class="max-w-7xl w-full mx-4"
                wire:model.self="showModal"
                tabindex="0"
                x-on:keydown.arrow-left.prevent="$wire.previousRating()"
                x-on:keydown.arrow-right.prevent="$wire.nextRating()"
                x-on:keydown.arrow-up.prevent="$wire.previousRating()"
                x-on:keydown.arrow-down.prevent="$wire.nextRating()"
                x-on:close="$wire.closeModal()"
    >
        <!-- Use the shared job content component -->
        <livewire:jobs.shared-job-content
            :jobPosting="$jobPosting"
            :rating="$rating"
            :currentIndex="$currentIndex"
            :total="$total"
            :showNavigation="true"
            :showBackButton="false"
            :key="'modal-job-content-'.($jobPosting?->job_id ?? 'none')"
            @previousRating="previousRating"
            @nextRating="nextRating"
        />
    </flux:modal>
</div>
