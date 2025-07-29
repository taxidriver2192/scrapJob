@push('breadcrumbs')
    @if($this->getBreadcrumbItems())
        <livewire:components.breadcrumbs
            :items="$this->getBreadcrumbItems()"
        />
    @endif
@endpush

<div wire:listen="requestAiRating=rateJobWithAi">
    @if($jobPosting)
    <div class="p-6">
        <!-- Headline -->
        @if($this->getHeadlineData())
            @php $headlineData = $this->getHeadlineData(); @endphp
            <livewire:components.headline
                :title="$headlineData['title']"
                :subtitle="$headlineData['subtitle']"
                :icon="$headlineData['icon']"
            />
        @endif

        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4">
                @if($showBackButton)
                <flux:button
                    size="sm"
                    wire:click="goBackToDashboard"
                    variant="outline"
                    icon="arrow-left"
                >
                    Back to Dashboard
                </flux:button>
                @endif
                @if($showNavigation && $currentIndex !== null && $total !== null)
                <div class="flex items-center space-x-2">
                    <flux:button
                        size="sm"
                        wire:click="previousRating"
                        variant="outline"
                        icon="chevron-left"
                    >
                        Previous
                    </flux:button>
                    <span class="text-sm text-zinc-500 px-2">{{ $currentIndex + 1 }} of {{ $total }}</span>
                    <flux:button
                        size="sm"
                        wire:click="nextRating"
                        variant="outline"
                        icon="chevron-right"
                    >
                        Next
                    </flux:button>
                </div>
                @endif
            </div>

            <!-- Favorite Button -->
            @auth
                <div class="flex items-center space-x-2">
                    @if($this->isFavorited())
                        <flux:button
                            size="sm"
                            wire:click="toggleFavorite"
                            variant="filled"
                            class="text-yellow-700 bg-yellow-100 border-yellow-300 hover:bg-yellow-200"
                        >
                            <flux:icon.bookmark variant="solid" class="w-4 h-4 mr-1" />
                            Saved
                        </flux:button>
                    @else
                        <flux:button
                            size="sm"
                            wire:click="toggleFavorite"
                            variant="outline"
                            class="hover:text-yellow-600 hover:border-yellow-300"
                        >
                            <flux:icon.bookmark class="w-4 h-4 mr-1" />
                            Save Job
                        </flux:button>
                    @endif
                </div>
            @endauth
        </div>

        <!-- Job Information, Skills, and Summary Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Job Information Component -->
            <livewire:jobs.modal.job-information :jobPosting="$jobPosting" :key="'job-info-'.$jobPosting->job_id" />

            <!-- Job Skills Component -->
            <livewire:jobs.modal.job-skills :jobPosting="$jobPosting" :key="'job-skills-'.$jobPosting->job_id" />

            <!-- Job Summary Component -->
            <livewire:jobs.modal.job-summary :jobPosting="$jobPosting" :key="'job-summary-'.$jobPosting->job_id" />
        </div>

        <!-- Job Rating Component (Full Width) -->
        <div class="mb-6">
        <livewire:jobs.modal.job-rating :rating="$rating" :jobPosting="$jobPosting" :key="'job-rating-'.$jobPosting->job_id" />
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
                <div class="flex items-center space-x-2">
                    @if(data_get($jobPosting, 'apply_url'))
                    <flux:button size="sm" href="{{ data_get($jobPosting, 'apply_url') }}" target="_blank" icon="arrow-top-right-on-square">
                        View Original Job
                    </flux:button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="p-6 text-center">
        <p class="text-gray-500">Job not found.</p>
        @if($showBackButton)
        <flux:button
            size="sm"
            wire:click="goBackToDashboard"
            variant="outline"
            icon="arrow-left"
            class="mt-4"
        >
            Back to Dashboard
        </flux:button>
        @endif
    </div>
    @endif
</div>
