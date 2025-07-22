<flux:card>
    <div class="relative">
        @if($this->hasRating())
            <!-- Confidence Badge -->
            <div class="absolute top-4 right-4 z-10">
                <flux:badge color="{{ $this->getConfidenceColor() }}" size="sm">
                    <flux:icon.cpu-chip class="mr-1" />{{ $this->getConfidence() }}% AI Confidence
                </flux:badge>
            </div>

            <div class="flex items-center justify-between mb-6">
                <flux:heading size="md" class="text-green-600">
                    <flux:icon.viewfinder-circle class="mr-2" />Skills Radar Chart
                </flux:heading>

                @if($this->isAiRating() && $this->getAiRatingId())
                <flux:button
                    size="sm"
                    variant="outline"
                    href="{{ route('ai-job-ratings.show', $this->getAiRatingId()) }}"
                    icon="eye"
                    class="text-purple-600 border-purple-300 hover:bg-purple-50"
                >
                    View Details
                </flux:button>
                @endif
            </div>

            <!-- Overall Score Progress Bar -->
            <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-lg">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-lg font-bold text-zinc-800 dark:text-zinc-200">Overall Score</span>
                    <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $this->getOverallScore() }}%</span>
                </div>

                <!-- Individual Skills Breakdown -->
                @if($chartData = $this->getRadarChartData())
                <div class="grid grid-cols-2 gap-3 text-sm" x-data="{ hoveredSkill: null }">
                    @foreach($chartData['axes'] as $index => $axis)
                    <div class="relative" x-on:mouseenter="hoveredSkill = {{ $index }}" x-on:mouseleave="hoveredSkill = null">
                        <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-6 overflow-hidden">
                            <div @class([
                                'h-full transition-all duration-300 flex items-center justify-center',
                                'bg-blue-500' => $index === 0,
                                'bg-purple-500' => $index === 1,
                                'bg-orange-500' => $index === 2,
                                'bg-indigo-500' => $index === 3,
                            ]) style="width: {{ $axis['score'] }}%">
                                <flux:icon.{{ $axis['icon'] }} class="text-white size-4" />
                                <span class="font-bold text-white ml-1">{{ $axis['score'] }}%</span>
                            </div>
                        </div>
                        <div x-show="hoveredSkill === {{ $index }}" x-transition
                             class="absolute bottom-full mb-2 w-max max-w-xs bg-zinc-800 text-white text-xs rounded py-2 px-3 text-left z-10 pointer-events-none shadow-lg">
                            <div class="flex items-center font-bold mb-1">
                                <flux:icon.{{ $axis['icon'] }} class="size-4 mr-2" />
                                <span>{{ $axis['name'] }}</span>
                            </div>
                            <p class="font-normal">{{ $axis['tooltip'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        @else
            <!-- Not Rated Display -->
            @if($this->isJobClosed())
                <!-- Job Closed Display -->
                <div class="p-8 text-center bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 rounded-lg border border-red-200 dark:border-red-800">
                    <div class="mb-6">
                        <flux:icon.x-circle class="mx-auto text-red-500 dark:text-red-400 size-16 mb-4" />
                        <flux:heading size="md" class="text-red-600 dark:text-red-400 mb-2">
                            Job Position Closed
                        </flux:heading>
                        <p class="text-red-500 dark:text-red-400 text-sm mb-6">
                            This job posting is no longer accepting applications. The position has been closed and cannot be rated at this time.
                        </p>
                        @if($this->jobPosting->job_post_closed_date)
                            <p class="text-red-400 dark:text-red-500 text-xs">
                                Closed on {{ \Carbon\Carbon::parse($this->jobPosting->job_post_closed_date)->format('M j, Y \a\t g:i A') }}
                            </p>
                        @endif
                    </div>
                </div>
            @else
                <!-- Job Open - Not Rated Display -->
                <div class="p-8 text-center bg-gradient-to-r from-zinc-50 to-zinc-100 dark:from-zinc-800 dark:to-zinc-700 rounded-lg">
                    <div class="mb-6">
                        <flux:icon.question-mark-circle class="mx-auto text-zinc-400 dark:text-zinc-500 size-16 mb-4" />
                        <flux:heading size="md" class="text-zinc-600 dark:text-zinc-300 mb-2">
                            Job Not Rated Yet
                        </flux:heading>
                        <p class="text-zinc-500 dark:text-zinc-400 text-sm mb-6">
                            This job hasn't been analyzed by our AI rating system. Click the button below to get an AI-powered match score based on your preferences.
                        </p>
                    </div>

                    <flux:button
                        variant="primary"
                        size="sm"
                        icon="sparkles"
                        wire:click="requestRating"
                        class="bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700"
                    >
                        Rate This Job with AI
                    </flux:button>
                </div>
            @endif
        @endif
    </div>
</flux:card>
