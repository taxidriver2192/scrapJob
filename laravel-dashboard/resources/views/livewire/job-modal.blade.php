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
                        {{ $this->hasRating() ? 'Job Rating Details' : 'Job Details (Not Rated)' }}
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

            <!-- Job Information -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <flux:card>
                    <div class="p-4">
                        <flux:heading size="md" class="mb-4 text-blue-600">
                            <flux:icon.briefcase class="mr-2" />Job Information
                        </flux:heading>
                        <div class="space-y-3">
                            <div>
                                <flux:subheading class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Title</flux:subheading>
                                <p class="text-zinc-900 dark:text-zinc-100">{{ $this->getJobTitle() }}</p>
                            </div>
                            <div>
                                <flux:subheading class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Company</flux:subheading>
                                <p class="text-zinc-900 dark:text-zinc-100">{{ $this->getCompanyName() }}</p>
                            </div>
                            <div>
                                <flux:subheading class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Location</flux:subheading>
                                <p class="text-zinc-900 dark:text-zinc-100">{{ $this->getJobLocation() }}</p>
                                @if($this->getPostcode())
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->getPostcode() }}</p>
                                @endif
                            </div>
                            <div>
                                <flux:subheading class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Posted Date</flux:subheading>
                                <p class="text-zinc-900 dark:text-zinc-100">{{ $this->getPostedDate() }}</p>
                            </div>
                        </div>
                    </div>
                </flux:card>

                <flux:card>
                    <div class="relative">
                        @if($this->hasRating())
                            <!-- Confidence Badge -->
                            <div class="absolute top-4 right-4 z-10">
                                <flux:badge color="{{ $this->getConfidenceColor() }}" size="sm">
                                    <flux:icon.cpu-chip class="mr-1" />{{ $this->getConfidence() }}% AI Confidence
                                </flux:badge>
                            </div>

                            <flux:heading size="md" class="mb-6 text-green-600">
                                <flux:icon.viewfinder-circle class="mr-2" />Skills Radar Chart
                            </flux:heading>

                            <!-- Overall Score Progress Bar -->
                            <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-lg">
                                <div class="flex justify-between items-center mb-4">
                                    <span class="text-lg font-bold text-zinc-800 dark:text-zinc-200">Overall Score</span>
                                    <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $this->getOverallScore() }}%</span>
                                </div>

                                <!-- Individual Skills Breakdown -->
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <!-- Location Progress Bar -->
                                    <div class="relative">
                                        <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-6 overflow-hidden">
                                            <div class="h-full bg-blue-500 transition-all duration-300 flex items-center justify-center"
                                                 style="width: {{ $this->getLocationScore() }}%">
                                                <flux:icon.map-pin class="text-white size-4" />
                                                <span class="font-bold text-white ml-1">{{ $this->getLocationScore() }}%</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tech Skills Progress Bar -->
                                    <div class="relative">
                                        <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-6 overflow-hidden">
                                            <div class="h-full bg-purple-500 transition-all duration-300 flex items-center justify-center"
                                                 style="width: {{ $this->getTechScore() }}%">
                                                <flux:icon.code-bracket class="text-white size-4" />
                                                <span class="font-bold text-white ml-1">{{ $this->getTechScore() }}%</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Team Size Progress Bar -->
                                    <div class="relative">
                                        <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-6 overflow-hidden">
                                            <div class="h-full bg-orange-500 transition-all duration-300 flex items-center justify-center"
                                                 style="width: {{ $this->getTeamSizeScore() }}%">
                                                <flux:icon.user-group class="text-white size-4" />
                                                <span class="font-bold text-white ml-1">{{ $this->getTeamSizeScore() }}%</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Leadership Progress Bar -->
                                    <div class="relative">
                                        <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-6 overflow-hidden">
                                            <div class="h-full bg-indigo-500 transition-all duration-300 flex items-center justify-center"
                                                 style="width: {{ $this->getLeadershipScore() }}%">
                                                <flux:icon.academic-cap class="text-white size-4" />
                                                <span class="font-bold text-white ml-1">{{ $this->getLeadershipScore() }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Radar Chart -->
                            @if($chartData = $this->getRadarChartData())
                            <div class="flex flex-col items-center">
                                <div class="relative mb-6" x-data="{ hoveredPoint: null }">
                                    <svg width="{{ $chartData['size'] }}" height="{{ $chartData['size'] }}" class="drop-shadow-lg transition-all duration-300">
                                        <!-- Background circles (grid) -->
                                        @for($i = 1; $i <= 5; $i++)
                                            <circle cx="{{ $chartData['center'] }}" cy="{{ $chartData['center'] }}" r="{{ ($i * $chartData['maxRadius']) / 5 }}"
                                                   fill="none" stroke="currentColor" stroke-width="{{ $i == 5 ? '2' : '1' }}"
                                                   class="text-zinc-200 dark:text-zinc-700" opacity="{{ $i == 5 ? '0.8' : '0.4' }}"/>
                                        @endfor

                                        <!-- Axis lines -->
                                        @for($i = 0; $i < 4; $i++)
                                            @php
                                                $angle = ($i * 90 - 90) * pi() / 180;
                                                $endX = $chartData['center'] + cos($angle) * $chartData['maxRadius'];
                                                $endY = $chartData['center'] + sin($angle) * $chartData['maxRadius'];
                                            @endphp
                                            <line x1="{{ $chartData['center'] }}" y1="{{ $chartData['center'] }}"
                                                  x2="{{ $endX }}" y2="{{ $endY }}"
                                                  stroke="currentColor" stroke-width="1"
                                                  class="text-zinc-300 dark:text-zinc-600" opacity="0.6"/>
                                        @endfor

                                        <!-- Data polygon -->
                                        <defs>
                                            <radialGradient id="radarGradient" cx="50%" cy="50%" r="50%">
                                                <stop offset="0%" style="stop-color:rgba(59, 130, 246, 0.4);stop-opacity:1" />
                                                <stop offset="100%" style="stop-color:rgba(59, 130, 246, 0.1);stop-opacity:1" />
                                            </radialGradient>
                                        </defs>

                                        <polygon points="{{ $chartData['polygonPoints'] }}"
                                                fill="url(#radarGradient)"
                                                stroke="rgb(59, 130, 246)"
                                                stroke-width="3"
                                                class="transition-all duration-300"/>

                                        <!-- Interactive Data points -->
                                        @foreach($chartData['axes'] as $index => $axis)
                                            @php
                                                $angle = ($index * 90 - 90) * pi() / 180;
                                                $radius = ($axis['score'] / 100) * $chartData['maxRadius'];
                                                $x = $chartData['center'] + cos($angle) * $radius;
                                                $y = $chartData['center'] + sin($angle) * $radius;
                                            @endphp

                                            <!-- Hover area -->
                                            <circle cx="{{ $x }}" cy="{{ $y }}" r="20"
                                                   fill="transparent"
                                                   class="cursor-pointer"
                                                   x-on:mouseenter="hoveredPoint = {{ $index }}"
                                                   x-on:mouseleave="hoveredPoint = null"/>

                                            <!-- Point circle -->
                                            <circle cx="{{ $x }}" cy="{{ $y }}"
                                                   :r="hoveredPoint === {{ $index }} ? '18' : '16'"
                                                   fill="white"
                                                   stroke="{{ $axis['color'] }}"
                                                   stroke-width="3"
                                                   class="transition-all duration-200 cursor-pointer drop-shadow-lg"
                                                   x-on:mouseenter="hoveredPoint = {{ $index }}"
                                                   x-on:mouseleave="hoveredPoint = null"/>

                                            <!-- Icon -->
                                            <text x="{{ $x }}" y="{{ $y + 2 }}"
                                                  text-anchor="middle"
                                                  dominant-baseline="central"
                                                  font-size="12"
                                                  font-weight="bold"
                                                  fill="{{ $axis['color'] }}"
                                                  class="cursor-pointer"
                                                  x-on:mouseenter="hoveredPoint = {{ $index }}"
                                                  x-on:mouseleave="hoveredPoint = null">
                                                @switch($index)
                                                    @case(0) 📍 @break
                                                    @case(1) 💻 @break
                                                    @case(2) 👥 @break
                                                    @case(3) 🎓 @break
                                                @endswitch
                                            </text>

                                            <!-- Percentage -->
                                            <text x="{{ $x }}" y="{{ $y + 25 }}"
                                                  text-anchor="middle"
                                                  font-size="10"
                                                  font-weight="bold"
                                                  fill="{{ $axis['color'] }}">
                                                {{ $axis['score'] }}%
                                            </text>
                                        @endforeach
                                    </svg>

                                    <!-- Tooltips -->
                                    @foreach($chartData['axes'] as $index => $axis)
                                    <div x-show="hoveredPoint === {{ $index }}"
                                         x-transition
                                         class="absolute top-2 left-1/2 transform -translate-x-1/2 bg-zinc-800 dark:bg-zinc-200 text-white dark:text-zinc-800 px-3 py-2 rounded-lg shadow-lg z-10 pointer-events-none">
                                        <div class="flex items-center space-x-2 text-sm">
                                            @switch($index)
                                                @case(0) <flux:icon.map-pin class="size-4" style="color: {{ $axis['color'] }}" /> @break
                                                @case(1) <flux:icon.code-bracket class="size-4" style="color: {{ $axis['color'] }}" /> @break
                                                @case(2) <flux:icon.user-group class="size-4" style="color: {{ $axis['color'] }}" /> @break
                                                @case(3) <flux:icon.academic-cap class="size-4" style="color: {{ $axis['color'] }}" /> @break
                                            @endswitch
                                            <span class="font-semibold">{{ $axis['name'] }}</span>
                                            <span class="font-bold">{{ $axis['score'] }}%</span>
                                        </div>
                                        <div class="text-xs mt-1 text-zinc-300 dark:text-zinc-600">{{ $axis['tooltip'] }}</div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        @else
                            <!-- Not Rated Display -->
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
                                    icon="star"
                                    wire:click="rateJob"
                                >
                                    Rate This Job
                                </flux:button>
                            </div>
                        @endif
                    </div>
                </flux:card>
            </div>

            <!-- Job Description -->
            <div class="mt-6">
                <flux:card>
                    <div class="p-4">
                        <flux:heading size="md" class="mb-4 text-purple-600">
                            <flux:icon.document-text class="mr-2" />Job Description
                        </flux:heading>
                        <div class="prose prose-sm max-w-none text-zinc-700 dark:text-zinc-300 max-h-96 overflow-y-auto">
                            @if($this->getJobDescription())
                                <div class="formatted-content">{!! nl2br(strip_tags($this->getJobDescription(), '<p><br><strong><b><em><i><ul><ol><li><h1><h2><h3><h4><h5><h6><a><span><div>')) !!}</div>
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
                    <span>Rated on: {{ $this->getRatedDate() }}</span>
                    @if($this->getApplyUrl())
                    <flux:button size="sm" href="{{ $this->getApplyUrl() }}" target="_blank" icon="arrow-top-right-on-square">
                        View Original Job
                    </flux:button>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </flux:modal>
</div>
