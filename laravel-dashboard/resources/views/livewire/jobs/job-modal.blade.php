<div>
    <!-- Job Details Modal -->
    <flux:modal name="job-details-modal" class="max-w-7xl w-full mx-4"
                x-on:keydown.arrow-left.prevent="$wire.previousRating()"
                x-on:keydown.arrow-right.prevent="$wire.nextRating()"
                x-on:keydown.arrow-up.prevent="$wire.previousRating()"
                x-on:keydown.arrow-down.prevent="$wire.nextRating()"
                tabindex="0">
        @if($jobPosting)
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-4">
                    <flux:heading size="lg">
                        <flux:icon.star class="mr-2 text-yellow-500" />
                        {{ $rating && data_get($rating, 'overall_score', 0) > 0 ? 'Job Rating Details' : 'Job Details (Not Rated)' }}
                    </flux:heading>
                    <div class="flex items-center space-x-2">
                        <flux:button
                            size="sm"
                            wire:click="previousRating"
                            variant="outline"
                            icon="chevron-left"
                            :disabled="!$this->canNavigatePrevious()"
                        >
                            Previous
                        </flux:button>
                        <span class="text-sm text-zinc-500 px-2">{{ $currentIndex + 1 }} of {{ $total }}</span>
                        <flux:button
                            size="sm"
                            wire:click="nextRating"
                            variant="outline"
                            icon="chevron-right"
                            :disabled="!$this->canNavigateNext()"
                        >
                            Next
                        </flux:button>
                    </div>
                </div>
                <flux:modal.close>
                    <flux:button variant="ghost" icon="x-mark" size="sm">
                    </flux:button>
                </flux:modal.close>
            </div>

            <!-- Job Information and Rating Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Job Information Component -->
                <livewire:jobs.modal.job-information :jobPosting="$jobPosting" :key="'job-info-'.$jobPosting->job_id" />

                <!-- Job Rating Component -->
                <div class="lg:col-span-2">
                    <livewire:jobs.modal.job-rating :rating="$rating" :jobPosting="$jobPosting" :key="'job-rating-'.$jobPosting->job_id" />
                </div>
            </div>

            <!-- Job Description -->
            <div class="mt-6">
                <flux:card>
                    <div class="p-4">
                        <flux:heading size="md" class="mb-4 text-purple-600">
                            <flux:icon.document-text class="mr-2" />Job Description
                        </flux:heading>
                        <div class="prose prose-sm max-w-none text-zinc-700 dark:text-zinc-300 max-h-96 overflow-y-auto">
                            @if(data_get($jobPosting, 'description'))
                                <div class="formatted-content">{!! nl2br(strip_tags(data_get($jobPosting, 'description'), '<p><br><strong><b><em><i><ul><ol><li><h1><h2><h3><h4><h5><h6><a><span><div>')) !!}</div>
                            @else
                                <p class="text-gray-500 italic">No job description available.</p>
                            @endif
                        </div>
                    </div>
                </flux:card>
            </div>

            <!-- Rating Metadata -->
            <div class="mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <div class="flex justify-between items-center text-sm text-zinc-500 dark:text-zinc-400">
                    <span>Rated on: {{ $rating && data_get($rating, 'rated_at') ? \Carbon\Carbon::parse(data_get($rating, 'rated_at'))->format('F j, Y \a\t g:i A') : 'N/A' }}</span>
                    @if(data_get($jobPosting, 'apply_url'))
                    <flux:button size="sm" href="{{ data_get($jobPosting, 'apply_url') }}" target="_blank" icon="arrow-top-right-on-square">
                        View Original Job
                    </flux:button>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </flux:modal>
</div>
