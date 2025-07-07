<div>
    <flux:main class="max-w-7xl mx-auto px-4 bg-white dark:bg-zinc-900">
        <flux:heading size="xl" class="text-blue-600 dark:text-blue-400 mb-6">
            <i class="fas fa-star mr-2"></i>Job Ratings
        </flux:heading>

        <!-- Advanced Search and Filter Row -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 mb-6">
            <div class="lg:col-span-2">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search jobs or companies..."
                    icon="search"
                />
            </div>
            <div>
                <flux:select wire:model.live="ratingTypeFilter">
                    <option value="">All Rating Types</option>
                    @foreach($ratingTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div>
                <flux:select wire:model.live="companyFilter">
                    <option value="">All Companies</option>
                    @foreach($companies as $company)
                        <option value="{{ $company }}">{{ $company }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div>
                <flux:select wire:model.live="scoreRangeFilter">
                    <option value="">All Scores</option>
                    <option value="high">High (80-100%)</option>
                    <option value="medium">Medium (60-79%)</option>
                    <option value="low">Low (0-59%)</option>
                </flux:select>
            </div>
        </div>

        <!-- Active Filters -->
        @if($search || $ratingTypeFilter || $companyFilter || $scoreRangeFilter || $locationFilter || $dateFilter)
        <div class="flex flex-wrap gap-2 mb-6">
            <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Active filters:</span>

            @if($search)
            <flux:badge color="blue" class="cursor-pointer" wire:click="clearFilter('search')">
                Search: "{{ $search }}" ×
            </flux:badge>
            @endif

            @if($ratingTypeFilter)
            <flux:badge color="purple" class="cursor-pointer" wire:click="clearFilter('ratingType')">
                Type: {{ ucfirst(str_replace('_', ' ', $ratingTypeFilter)) }} ×
            </flux:badge>
            @endif

            @if($companyFilter)
            <flux:badge color="green" class="cursor-pointer" wire:click="clearFilter('company')">
                Company: {{ $companyFilter }} ×
            </flux:badge>
            @endif

            @if($scoreRangeFilter)
            <flux:badge color="orange" class="cursor-pointer" wire:click="clearFilter('scoreRange')">
                Score: {{ ucfirst($scoreRangeFilter) }} ×
            </flux:badge>
            @endif

            @if($locationFilter)
            <flux:badge color="indigo" class="cursor-pointer" wire:click="clearFilter('location')">
                Location: {{ $locationFilter }} ×
            </flux:badge>
            @endif

            @if($dateFilter)
            <flux:badge color="cyan" class="cursor-pointer" wire:click="clearFilter('date')">
                Date: {{ $dateFilter }} ×
            </flux:badge>
            @endif

            <flux:button size="sm" variant="outline" wire:click="clearAllFilters">
                Clear All
            </flux:button>
        </div>
        @endif

        <!-- Ratings Table -->
        <flux:card class="bg-white dark:bg-zinc-900">
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-6">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-100">
                        <i class="fas fa-list mr-2 text-zinc-600 dark:text-zinc-400"></i>AI Job Ratings
                    </flux:heading>
                    <div class="flex items-center space-x-2 text-sm text-zinc-500 dark:text-zinc-400">
                        <i class="fas fa-sort mr-1"></i>
                        <span>Sorted by:</span>
                        <flux:badge color="blue">
                            {{ ucfirst(str_replace('_', ' ', $sortField)) }}
                            @if($sortDirection === 'asc')
                                ↑
                            @else
                                ↓
                            @endif
                        </flux:badge>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Job Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <button wire:click="sortBy('company')" class="flex items-center space-x-1 hover:text-zinc-700 dark:hover:text-zinc-200">
                                    <span>Company</span>
                                    @if($sortField === 'company')
                                        @if($sortDirection === 'asc')
                                            <i class="fas fa-sort-up text-blue-500"></i>
                                        @else
                                            <i class="fas fa-sort-down text-blue-500"></i>
                                        @endif
                                    @else
                                        <i class="fas fa-sort text-zinc-400"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <button wire:click="sortBy('overall_score')" class="flex items-center space-x-1 hover:text-zinc-700 dark:hover:text-zinc-200">
                                    <span>Overall Score</span>
                                    @if($sortField === 'overall_score')
                                        @if($sortDirection === 'asc')
                                            <i class="fas fa-sort-up text-blue-500"></i>
                                        @else
                                            <i class="fas fa-sort-down text-blue-500"></i>
                                        @endif
                                    @else
                                        <i class="fas fa-sort text-zinc-400"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <button wire:click="sortBy('location_score')" class="flex items-center space-x-1 hover:text-zinc-700 dark:hover:text-zinc-200">
                                    <span>Location</span>
                                    @if($sortField === 'location_score')
                                        @if($sortDirection === 'asc')
                                            <i class="fas fa-sort-up text-blue-500"></i>
                                        @else
                                            <i class="fas fa-sort-down text-blue-500"></i>
                                        @endif
                                    @else
                                        <i class="fas fa-sort text-zinc-400"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <button wire:click="sortBy('tech_score')" class="flex items-center space-x-1 hover:text-zinc-700 dark:hover:text-zinc-200">
                                    <span>Tech</span>
                                    @if($sortField === 'tech_score')
                                        @if($sortDirection === 'asc')
                                            <i class="fas fa-sort-up text-blue-500"></i>
                                        @else
                                            <i class="fas fa-sort-down text-blue-500"></i>
                                        @endif
                                    @else
                                        <i class="fas fa-sort text-zinc-400"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <button wire:click="sortBy('team_size_score')" class="flex items-center space-x-1 hover:text-zinc-700 dark:hover:text-zinc-200">
                                    <span>Team Size</span>
                                    @if($sortField === 'team_size_score')
                                        @if($sortDirection === 'asc')
                                            <i class="fas fa-sort-up text-blue-500"></i>
                                        @else
                                            <i class="fas fa-sort-down text-blue-500"></i>
                                        @endif
                                    @else
                                        <i class="fas fa-sort text-zinc-400"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <button wire:click="sortBy('leadership_score')" class="flex items-center space-x-1 hover:text-zinc-700 dark:hover:text-zinc-200">
                                    <span>Leadership</span>
                                    @if($sortField === 'leadership_score')
                                        @if($sortDirection === 'asc')
                                            <i class="fas fa-sort-up text-blue-500"></i>
                                        @else
                                            <i class="fas fa-sort-down text-blue-500"></i>
                                        @endif
                                    @else
                                        <i class="fas fa-sort text-zinc-400"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <button wire:click="sortBy('rated_at')" class="flex items-center space-x-1 hover:text-zinc-700 dark:hover:text-zinc-200">
                                    <span>Rated At</span>
                                    @if($sortField === 'rated_at')
                                        @if($sortDirection === 'asc')
                                            <i class="fas fa-sort-up text-blue-500"></i>
                                        @else
                                            <i class="fas fa-sort-down text-blue-500"></i>
                                        @endif
                                    @else
                                        <i class="fas fa-sort text-zinc-400"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($ratings as $index => $rating)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 {{ $selectedRating && $selectedRating->id == ($rating->id ?? $rating->rating_id) ? 'bg-blue-100 dark:bg-blue-900/50 ring-2 ring-blue-500' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $rating->jobPosting->title ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                @if($rating->jobPosting->company->name ?? false)
                                    <button
                                        wire:click="filterByCompany('{{ $rating->jobPosting->company->name }}')"
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline decoration-dotted cursor-pointer"
                                        title="Filter by this company"
                                    >
                                        {{ $rating->jobPosting->company->name }}
                                    </button>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                @if($rating->overall_score)
                                    <button
                                        wire:click="filterByScoreRange('{{ $rating->overall_score >= 80 ? 'high' : ($rating->overall_score >= 60 ? 'medium' : 'low') }}')"
                                        class="cursor-pointer"
                                        title="Filter by score range"
                                    >
                                        <flux:badge color="{{ $rating->overall_score >= 80 ? 'green' : ($rating->overall_score >= 60 ? 'yellow' : 'red') }}">
                                            {{ $rating->overall_score }}%
                                        </flux:badge>
                                    </button>
                                @else
                                    <span class="text-zinc-500 dark:text-zinc-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                @if($rating->location_score)
                                    <button
                                        wire:click="filterByScoreRange('{{ $rating->location_score >= 80 ? 'high' : ($rating->location_score >= 60 ? 'medium' : 'low') }}')"
                                        class="cursor-pointer hover:scale-105 transition-transform"
                                        title="Filter by score range"
                                    >
                                        <flux:badge color="zinc">{{ $rating->location_score }}%</flux:badge>
                                    </button>
                                @else
                                    <span class="text-zinc-500 dark:text-zinc-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                @if($rating->tech_score)
                                    <button
                                        wire:click="filterByScoreRange('{{ $rating->tech_score >= 80 ? 'high' : ($rating->tech_score >= 60 ? 'medium' : 'low') }}')"
                                        class="cursor-pointer hover:scale-105 transition-transform"
                                        title="Filter by score range"
                                    >
                                        <flux:badge color="zinc">{{ $rating->tech_score }}%</flux:badge>
                                    </button>
                                @else
                                    <span class="text-zinc-500 dark:text-zinc-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                @if($rating->team_size_score)
                                    <button
                                        wire:click="filterByScoreRange('{{ $rating->team_size_score >= 80 ? 'high' : ($rating->team_size_score >= 60 ? 'medium' : 'low') }}')"
                                        class="cursor-pointer hover:scale-105 transition-transform"
                                        title="Filter by score range"
                                    >
                                        <flux:badge color="zinc">{{ $rating->team_size_score }}%</flux:badge>
                                    </button>
                                @else
                                    <span class="text-zinc-500 dark:text-zinc-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                @if($rating->leadership_score)
                                    <button
                                        wire:click="filterByScoreRange('{{ $rating->leadership_score >= 80 ? 'high' : ($rating->leadership_score >= 60 ? 'medium' : 'low') }}')"
                                        class="cursor-pointer hover:scale-105 transition-transform"
                                        title="Filter by score range"
                                    >
                                        <flux:badge color="zinc">{{ $rating->leadership_score }}%</flux:badge>
                                    </button>
                                @else
                                    <span class="text-zinc-500 dark:text-zinc-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                <button
                                    wire:click="filterByDate('{{ $rating->rated_at->format('Y-m-d') }}')"
                                    class="text-zinc-600 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 hover:underline cursor-pointer"
                                    title="Filter by this date"
                                >
                                    {{ $rating->rated_at->format('Y-m-d H:i') }}
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                <flux:button size="sm" wire:click="viewDetails({{ $rating->id ?? $rating->rating_id }})" icon="eye">
                                    View Details
                                </flux:button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                <i class="fas fa-star text-4xl mb-4"></i>
                                <p class="text-lg">No ratings found matching your criteria.</p>
                                @if($search || $ratingTypeFilter || $companyFilter || $scoreRangeFilter || $locationFilter)
                                <flux:button size="sm" variant="outline" wire:click="clearAllFilters" class="mt-2">
                                    Clear all filters
                                </flux:button>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-6">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">Show:</span>
                        <flux:select wire:model.live="perPage" class="w-20">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </flux:select>
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">per page</span>
                    </div>
                    <div>
                        {{ $ratings->links() }}
                    </div>
                </div>
            </div>
        </flux:card>

        <!-- Job Details Modal -->
        <flux:modal name="job-details-modal" class="max-w-7xl w-full mx-4"
                    x-data="{
                        init() {
                            this.$watch('show', value => {
                                if (value) {
                                    this.$nextTick(() => {
                                        this.$el.focus();
                                    });
                                }
                            });
                        }
                    }"
                    x-on:keydown.arrow-left.prevent="$wire.previousRating()"
                    x-on:keydown.arrow-right.prevent="$wire.nextRating()"
                    x-on:keydown.arrow-up.prevent="$wire.previousRating()"
                    x-on:keydown.arrow-down.prevent="$wire.nextRating()"
                    tabindex="0">
            @if($selectedRating)
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        <flux:heading size="lg">
                            <i class="fas fa-star mr-2 text-yellow-500"></i>Job Rating Details
                        </flux:heading>
                        <div class="flex items-center space-x-2">
                            <flux:button size="sm" wire:click="previousRating" variant="outline" icon="chevron-left">
                                Previous
                            </flux:button>
                            <span class="text-sm text-zinc-500 px-2">{{ $currentRatingIndex + 1 }} of {{ $totalRatings }}</span>
                            <flux:button size="sm" wire:click="nextRating" variant="outline" icon="chevron-right">
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
                                <i class="fas fa-briefcase mr-2"></i>Job Information
                            </flux:heading>
                            <div class="space-y-3">
                                <div>
                                    <flux:subheading class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Title</flux:subheading>
                                    <p class="text-zinc-900 dark:text-zinc-100">{{ $selectedRating->jobPosting->title ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <flux:subheading class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Company</flux:subheading>
                                    <p class="text-zinc-900 dark:text-zinc-100">{{ $selectedRating->jobPosting->company->name ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <flux:subheading class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Location</flux:subheading>
                                    <p class="text-zinc-900 dark:text-zinc-100">{{ $selectedRating->jobPosting->location ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <flux:subheading class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Posted Date</flux:subheading>
                                    <p class="text-zinc-900 dark:text-zinc-100">{{ $selectedRating->jobPosting->posted_date ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </flux:card>

                    <flux:card>
                        <div class="p-4 relative">
                            <!-- Confidence Level Sticky Note -->
                            @php
                                $criteria = is_string($selectedRating->criteria ?? '')
                                    ? json_decode($selectedRating->criteria, true)
                                    : ($selectedRating->criteria ?? []);
                                $confidence = $criteria['confidence'] ?? 0;
                                $confidenceColor = $confidence >= 80 ? 'bg-green-100 border-green-300 text-green-800' :
                                                  ($confidence >= 60 ? 'bg-yellow-100 border-yellow-300 text-yellow-800' :
                                                   'bg-red-100 border-red-300 text-red-800');
                            @endphp


                            <flux:heading size="md" class="mb-6 text-green-600">
                                <i class="fas fa-bullseye mr-2"></i>Skills Radar Chart
                            </flux:heading>

                            <!-- Overall Score Progress Bar -->
                            <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-lg">
                                <div class="flex justify-between items-center mb-4">
                                    <span class="text-lg font-bold text-zinc-800 dark:text-zinc-200">Overall Score</span>
                                    <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $selectedRating->overall_score ?? 0 }}%</span>
                                </div>

                                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-4">
                                    <div class="h-4 rounded-full transition-all duration-300 {{ $selectedRating->overall_score >= 80 ? 'bg-green-500' : ($selectedRating->overall_score >= 60 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                         style="width: {{ $selectedRating->overall_score ?? 0 }}%"></div>
                                </div>
                            </div>

                            <!-- AI Analysis Summary -->
                            <div class="mb-6 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                <div class="text-sm text-zinc-700 dark:text-zinc-300">
                                    <div class="font-medium">AI Analysis Summary</div>
                                    @if(isset($criteria['confidence']))
                                    <div class="text-zinc-600 dark:text-zinc-400 mt-1">
                                        {{ $criteria['confidence'] }}% confidence
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Radar Chart (FIFA-style) -->
                            <div class="flex flex-col items-center">
                                @php
                                    $criteria = is_string($selectedRating->criteria ?? '')
                                        ? json_decode($selectedRating->criteria, true)
                                        : ($selectedRating->criteria ?? []);

                                    // Prepare scores (0-100 scale)
                                    $locationScore = $selectedRating->location_score ?? 0;
                                    $techScore = $selectedRating->tech_score ?? 0;
                                    $teamSizeScore = $selectedRating->team_size_score ?? 0;
                                    $leadershipScore = $selectedRating->leadership_score ?? 0;

                                    // Chart dimensions
                                    $size = 240;
                                    $center = $size / 2;
                                    $maxRadius = 90;

                                    // Calculate positions for 4 axes (top, right, bottom, left)
                                    $axes = [
                                        ['name' => 'Location', 'score' => $locationScore, 'color' => '#3b82f6', 'icon' => 'fas fa-map-marker-alt', 'tooltip' => $criteria['location'] ?? 'Location analysis not available'],
                                        ['name' => 'Tech Skills', 'score' => $techScore, 'color' => '#8b5cf6', 'icon' => 'fas fa-code', 'tooltip' => $criteria['tech_match'] ?? 'Technical skills analysis not available'],
                                        ['name' => 'Team Size', 'score' => $teamSizeScore, 'color' => '#f97316', 'icon' => 'fas fa-users', 'tooltip' => $criteria['company_fit'] ?? 'Company culture and team size analysis not available'],
                                        ['name' => 'Leadership', 'score' => $leadershipScore, 'color' => '#6366f1', 'icon' => 'fas fa-crown', 'tooltip' => $criteria['seniority_fit'] ?? 'Leadership and seniority level analysis not available']
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

                                            <!-- Icon (using FontAwesome Unicode) -->
                                            <text x="{{ $x }}" y="{{ $y + 2 }}"
                                                  text-anchor="middle"
                                                  dominant-baseline="central"
                                                  font-family="FontAwesome"
                                                  font-size="12"
                                                  fill="{{ $axis['color'] }}"
                                                  class="cursor-pointer transition-all duration-200"
                                                  :class="hoveredPoint === {{ $index }} ? 'animate-pulse' : ''"
                                                  x-on:mouseenter="hoveredPoint = {{ $index }}"
                                                  x-on:mouseleave="hoveredPoint = null">
                                                @switch($index)
                                                    @case(0) &#xf3c5; @break {{-- map-marker-alt --}}
                                                    @case(1) &#xf121; @break {{-- code --}}
                                                    @case(2) &#xf0c0; @break {{-- users --}}
                                                    @case(3) &#xf521; @break {{-- crown --}}
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
                                            <i class="{{ $axis['icon'] }}" style="color: {{ $axis['color'] }}"></i>
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
                                <i class="fas fa-file-text mr-2"></i>Job Description
                            </flux:heading>
                            <div class="prose prose-sm max-w-none text-zinc-700 dark:text-zinc-300 max-h-96 overflow-y-auto">
                                @if($selectedRating->jobPosting->description)
                                    <div class="formatted-content">{!! nl2br(strip_tags($selectedRating->jobPosting->description, '<p><br><strong><b><em><i><ul><ol><li><h1><h2><h3><h4><h5><h6><a><span><div>')) !!}</div>
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
                        <span>Rated on: {{ $selectedRating->rated_at->format('F j, Y \a\t g:i A') }}</span>
                        @if($selectedRating->jobPosting->job_url)
                        <flux:button size="sm" href="{{ $selectedRating->jobPosting->job_url }}" target="_blank" icon="external-link">
                            View Original Job
                        </flux:button>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </flux:modal>
    </flux:main>
</div>
