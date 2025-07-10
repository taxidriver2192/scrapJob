<div>
    <!-- Job Details Modal -->
    <flux:modal name="job-details-modal" class="max-w-7xl w-full mx-4"
                x-on:keydown.arrow-left.prevent="$wire.previousRating()"
                x-on:keydown.arrow-right.prevent="$wire.nextRating()"
                x-on:keydown.arrow-up.prevent="$wire.previousRating()"
                x-on:keydown.arrow-down.prevent="$wire.nextRating()"
                tabindex="0">
        @if($rating)
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-4">
                    <flux:heading size="lg">
                        <flux:icon.star class="mr-2 text-yellow-500" />Job Rating Details
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
                    <flux:button variant="ghost" icon="x" size="sm">
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
                                <p class="text-zinc-900 dark:text-zinc-100">{{ $rating->jobPosting->title ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <flux:subheading class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Company</flux:subheading>
                                <p class="text-zinc-900 dark:text-zinc-100">{{ $rating->jobPosting->company->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <flux:subheading class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Location</flux:subheading>
                                <p class="text-zinc-900 dark:text-zinc-100">{{ $rating->jobPosting->location ?? 'N/A' }}</p>
                                @if($rating->jobPosting->postcode ?? false)
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $rating->jobPosting->postcode }}</p>
                                @endif
                            </div>
                            <div>
                                <flux:subheading class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Posted Date</flux:subheading>
                                <p class="text-zinc-900 dark:text-zinc-100">{{ $rating->jobPosting->posted_date ? $rating->jobPosting->posted_date->format('M j, Y') : 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </flux:card>

                <flux:card>
                    <div class="relative">
                        <!-- Confidence Badge in Right Corner -->
                        @php
                            $criteria = is_string($rating->criteria ?? '')
                                ? json_decode($rating->criteria, true)
                                : ($rating->criteria ?? []);
                            $confidence = $criteria['confidence'] ?? 0;
                        @endphp

                        @if($confidence > 0)
                            <div class="absolute top-4 right-4 z-10 right-0">
                                <flux:badge
                                    color="{{ $confidence >= 80 ? 'green' : ($confidence >= 60 ? 'yellow' : 'red') }}"
                                    size="sm"
                                >
                                    <flux:icon.cpu-chip class="mr-1" />{{ $confidence }}% AI Confidence
                                </flux:badge>
                            </div>
                        @endif

                        <flux:heading size="md" class="mb-6 text-green-600">
                            <flux:icon.viewfinder-circle class="mr-2" />Skills Radar Chart
                        </flux:heading>

                        <!-- Overall Score Progress Bar -->
                        <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-lg">
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-lg font-bold text-zinc-800 dark:text-zinc-200">Overall Score</span>
                                <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $rating->overall_score ?? 0 }}%</span>
                            </div>


                            <!-- Individual Skills Breakdown -->
                            <div class="grid grid-cols-2 gap-3 text-sm">

                                <!-- Location Progress Bar -->
                                <div class="relative">
                                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-6 overflow-hidden">
                                        <div class="h-full bg-blue-500 transition-all duration-300 flex items-center justify-center"
                                             style="width: {{ $rating->location_score ?? 0 }}%">
                                            <flux:icon.map-pin class="text-blue-500 size-4" />
                                            <span class="font-bold text-blue-600">{{ $rating->location_score ?? 0 }}%</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tech Skills Progress Bar -->
                                <div class="relative">
                                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-6 overflow-hidden">
                                        <div class="h-full bg-purple-500 transition-all duration-300 flex items-center justify-center"
                                             style="width: {{ $rating->tech_score ?? 0 }}%">
                                            <flux:icon.code-bracket class="text-purple-500 size-4" />
                                            <span class="font-bold text-purple-600">{{ $rating->tech_score ?? 0 }}%</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Team Size Progress Bar -->
                                <div class="relative">
                                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-6 overflow-hidden">
                                        <div class="h-full bg-orange-500 transition-all duration-300 flex items-center justify-center"
                                             style="width: {{ $rating->team_size_score ?? 0 }}%">
                                            <flux:icon.user-group class="text-orange-500 size-4" />
                                            <span class="font-bold text-orange-600">{{ $rating->team_size_score ?? 0 }}%</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Leadership Progress Bar -->
                                <div class="relative">
                                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-6 overflow-hidden">
                                        <div class="h-full bg-indigo-500 transition-all duration-300 flex items-center justify-center"
                                             style="width: {{ $rating->leadership_score ?? 0 }}%">
                                            <flux:icon.academic-cap class="text-indigo-500 size-4" />
                                            <span class="font-bold text-indigo-600">{{ $rating->leadership_score ?? 0 }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Radar Chart (FIFA-style) -->
                        <div class="flex flex-col items-center">
                            @php
                                $criteria = is_string($rating->criteria ?? '')
                                    ? json_decode($rating->criteria, true)
                                    : ($rating->criteria ?? []);

                                // Prepare scores (0-100 scale)
                                $locationScore = $rating->location_score ?? 0;
                                $techScore = $rating->tech_score ?? 0;
                                $teamSizeScore = $rating->team_size_score ?? 0;
                                $leadershipScore = $rating->leadership_score ?? 0;

                                // Chart dimensions
                                $size = 240;
                                $center = $size / 2;
                                $maxRadius = 90;

                                // Calculate positions for 4 axes (top, right, bottom, left)
                                $axes = [
                                    ['name' => 'Location', 'score' => $locationScore, 'color' => '#3b82f6', 'icon' => 'map-pin', 'tooltip' => $criteria['location'] ?? 'Location analysis not available'],
                                    ['name' => 'Tech Skills', 'score' => $techScore, 'color' => '#8b5cf6', 'icon' => 'code-bracket', 'tooltip' => $criteria['tech_match'] ?? 'Technical skills analysis not available'],
                                    ['name' => 'Team Size', 'score' => $teamSizeScore, 'color' => '#f97316', 'icon' => 'user-group', 'tooltip' => $criteria['company_fit'] ?? 'Company culture and team size analysis not available'],
                                    ['name' => 'Leadership', 'score' => $leadershipScore, 'color' => '#6366f1', 'icon' => 'academic-cap', 'tooltip' => $criteria['seniority_fit'] ?? 'Leadership and seniority level analysis not available']
                                ];

                                // Calculate polygon points
                                $points = [];
                                for ($i = 0; $i < 4; $i++) {
                                    $angle = ($i * 90 - 90) * pi() / 180; // Start from top and go clockwise
                                    $radius = ($axes[$i]['score'] / 100) * $maxRadius;
                                    $x = $center + cos($angle) * $radius;
                                    $y = $center + sin($angle) * $radius;
                                    $points[] = "$x,$y";
                                }
                                $polygonPoints = implode(' ', $points);
                            @endphp

                            <!-- Radar Chart SVG with Interactive Points -->
                            <div class="relative mb-6" x-data="{ hoveredPoint: null }">
                                <svg width="{{ $size }}" height="{{ $size }}" class="drop-shadow-lg transition-all duration-300">
                                    <!-- Background circles (grid) -->
                                    @for($i = 1; $i <= 5; $i++)
                                        <circle cx="{{ $center }}" cy="{{ $center }}" r="{{ ($i * $maxRadius) / 5 }}"
                                               fill="none" stroke="currentColor" stroke-width="{{ $i == 5 ? '2' : '1' }}"
                                               class="text-zinc-200 dark:text-zinc-700" opacity="{{ $i == 5 ? '0.8' : '0.4' }}"/>
                                    @endfor

                                    <!-- Axis lines -->
                                    @for($i = 0; $i < 4; $i++)
                                        @php
                                            $angle = ($i * 90 - 90) * pi() / 180;
                                            $endX = $center + cos($angle) * $maxRadius;
                                            $endY = $center + sin($angle) * $maxRadius;
                                        @endphp
                                        <line x1="{{ $center }}" y1="{{ $center }}"
                                              x2="{{ $endX }}" y2="{{ $endY }}"
                                              stroke="currentColor" stroke-width="1"
                                              class="text-zinc-300 dark:text-zinc-600" opacity="0.6"/>
                                    @endfor

                                    <!-- Data polygon with gradient -->
                                    <defs>
                                        <radialGradient id="radarGradient" cx="50%" cy="50%" r="50%">
                                            <stop offset="0%" style="stop-color:rgba(59, 130, 246, 0.4);stop-opacity:1" />
                                            <stop offset="100%" style="stop-color:rgba(59, 130, 246, 0.1);stop-opacity:1" />
                                        </radialGradient>
                                    </defs>

                                    <polygon points="{{ $polygonPoints }}"
                                            fill="url(#radarGradient)"
                                            stroke="rgb(59, 130, 246)"
                                            stroke-width="3"
                                            class="transition-all duration-300"/>

                                    <!-- Interactive Data points with icons and percentages -->
                                    @foreach($axes as $index => $axis)
                                        @php
                                            $angle = ($index * 90 - 90) * pi() / 180;
                                            $radius = ($axis['score'] / 100) * $maxRadius;
                                            $x = $center + cos($angle) * $radius;
                                            $y = $center + sin($angle) * $radius;
                                            $pointId = 'point-' . $index;
                                        @endphp

                                        <!-- Larger invisible hover area -->
                                        <circle cx="{{ $x }}" cy="{{ $y }}" r="20"
                                               fill="transparent"
                                               class="cursor-pointer"
                                               x-on:mouseenter="hoveredPoint = {{ $index }}"
                                               x-on:mouseleave="hoveredPoint = null"/>

                                        <!-- Background circle for icon -->
                                        <circle cx="{{ $x }}" cy="{{ $y }}"
                                               :r="hoveredPoint === {{ $index }} ? '18' : '16'"
                                               fill="white"
                                               stroke="{{ $axis['color'] }}"
                                               stroke-width="3"
                                               class="transition-all duration-200 cursor-pointer drop-shadow-lg"
                                               :class="hoveredPoint === {{ $index }} ? 'animate-pulse' : ''"
                                               x-on:mouseenter="hoveredPoint = {{ $index }}"
                                               x-on:mouseleave="hoveredPoint = null"/>

                                        <!-- Icon (using simple text symbols) -->
                                        <text x="{{ $x }}" y="{{ $y + 2 }}"
                                              text-anchor="middle"
                                              dominant-baseline="central"
                                              font-family="Arial, sans-serif"
                                              font-size="12"
                                              font-weight="bold"
                                              fill="{{ $axis['color'] }}"
                                              class="cursor-pointer transition-all duration-200"
                                              :class="hoveredPoint === {{ $index }} ? 'animate-pulse' : ''"
                                              x-on:mouseenter="hoveredPoint = {{ $index }}"
                                              x-on:mouseleave="hoveredPoint = null">
                                            @switch($index)
                                                @case(0) 📍 @break {{-- location --}}
                                                @case(1) 💻 @break {{-- tech --}}
                                                @case(2) 👥 @break {{-- team --}}
                                                @case(3) 🎓 @break {{-- leadership --}}
                                            @endswitch
                                        </text>

                                        <!-- Percentage text below icon -->
                                        <text x="{{ $x }}" y="{{ $y + 25 }}"
                                              text-anchor="middle"
                                              dominant-baseline="central"
                                              font-family="Arial, sans-serif"
                                              font-size="10"
                                              font-weight="bold"
                                              fill="{{ $axis['color'] }}"
                                              class="cursor-pointer transition-all duration-200"
                                              x-on:mouseenter="hoveredPoint = {{ $index }}"
                                              x-on:mouseleave="hoveredPoint = null">
                                            {{ $axis['score'] }}%
                                        </text>
                                    @endforeach
                                </svg>

                                <!-- Floating tooltips for each point -->
                                @foreach($axes as $index => $axis)
                                <div x-show="hoveredPoint === {{ $index }}"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform scale-95"
                                     x-transition:enter-end="opacity-100 transform scale-100"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100 transform scale-100"
                                     x-transition:leave-end="opacity-0 transform scale-95"
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
                            @if($rating->jobPosting->description ?? false)
                                <div class="formatted-content">{!! nl2br(strip_tags($rating->jobPosting->description, '<p><br><strong><b><em><i><ul><ol><li><h1><h2><h3><h4><h5><h6><a><span><div>')) !!}</div>
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
                    <span>Rated on: {{ $rating->rated_at ? \Carbon\Carbon::parse($rating->rated_at)->format('F j, Y \a\t g:i A') : 'N/A' }}</span>
                    @if($rating->jobPosting->job_url ?? false)
                    <flux:button size="sm" href="{{ $rating->jobPosting->job_url }}" target="_blank" icon="external-link">
                        View Original Job
                    </flux:button>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </flux:modal>

    @script
    <script>
        // Nothing needed here anymore
    </script>
    @endscript
</div>
