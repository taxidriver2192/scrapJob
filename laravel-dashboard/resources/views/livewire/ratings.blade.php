<div>
    <flux:main container class="max-w-7xl mx-auto px-4 bg-white dark:bg-zinc-900">
        <flux:heading size="xl" class="text-blue-600 dark:text-blue-400 mb-6">
            <i class="fas fa-star mr-2"></i>Job Ratings
        </flux:heading>

        <!-- Advanced Search and Filter Row -->
        <div class="grid grid-cols-1 lg:grid-cols-6 gap-4 mb-6">
            <div class="lg:col-span-2">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search jobs or companies..."
                    icon="search"
                />
            </div>
            <div>
                <flux:select wire:model.live="selectedMetric">
                    <option value="overall_score">Overall Score</option>
                    <option value="location_score">Location</option>
                    <option value="tech_score">Tech</option>
                    <option value="team_size_score">Team Size</option>
                    <option value="leadership_score">Leadership</option>
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
            <div>
                <flux:select wire:model.live="ratingTypeFilter">
                    <option value="">All Rating Types</option>
                    @foreach($ratingTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <!-- Sorting Controls -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-sort mr-1"></i>Sort By
                </label>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">Choose which field to sort the results by</p>
                <flux:select wire:model.live="sortField">
                    <option value="overall_score">Overall Score</option>
                    <option value="location_score">Location Score</option>
                    <option value="tech_score">Tech Score</option>
                    <option value="team_size_score">Team Size Score</option>
                    <option value="leadership_score">Leadership Score</option>
                    <option value="company">Company Name</option>
                    <option value="rated_at">Rating Date</option>
                </flux:select>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-arrow-up-down mr-1"></i>Sort Direction
                </label>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">Choose ascending (lowest first) or descending (highest first)</p>
                <flux:select wire:model.live="sortDirection">
                    <option value="desc">Descending (High to Low)</option>
                    <option value="asc">Ascending (Low to High)</option>
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

            <flux:table :paginate="$ratings">
                <flux:table.columns>
                    <flux:table.column>Job Title</flux:table.column>
                    <flux:table.column>Company</flux:table.column>
                    <flux:table.column>
                        @switch($selectedMetric)
                            @case('overall_score')
                                <i class="fas fa-trophy mr-1 text-yellow-500"></i>
                                <span>Primary: Overall Score</span>
                                @break
                            @case('location_score')
                                <i class="fas fa-map-marker-alt mr-1 text-blue-500"></i>
                                <span>Primary: Location</span>
                                @break
                            @case('tech_score')
                                <i class="fas fa-code mr-1 text-purple-500"></i>
                                <span>Primary: Tech</span>
                                @break
                            @case('team_size_score')
                                <i class="fas fa-users mr-1 text-orange-500"></i>
                                <span>Primary: Team Size</span>
                                @break
                            @case('leadership_score')
                                <i class="fas fa-crown mr-1 text-indigo-500"></i>
                                <span>Primary: Leadership</span>
                                @break
                        @endswitch
                    </flux:table.column>
                    <flux:table.column>Rated At</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($ratings as $index => $rating)
                        <flux:table.row :key="$rating->id ?? $rating->rating_id" class="{{ $selectedRating && $selectedRating->id == ($rating->id ?? $rating->rating_id) ? 'bg-blue-100 dark:bg-blue-900/50 ring-2 ring-blue-500' : '' }}">
                            <flux:table.cell>
                                <button
                                    wire:click="viewDetails({{ $rating->id ?? $rating->rating_id }})"
                                    class="text-left block w-full hover:opacity-80 transition-opacity"
                                    title="Click to view details"
                                >
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100 hover:text-blue-600 dark:hover:text-blue-400">
                                        {{ $rating->jobPosting->title ?? 'N/A' }}
                                    </div>
                                    @if($rating->jobPosting->description)
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400 truncate max-w-[300px] mt-1">
                                            {{ Str::limit(strip_tags($rating->jobPosting->description), 100) }}
                                        </div>
                                    @endif
                                </button>
                            </flux:table.cell>

                            <flux:table.cell>
                                {{ $rating->jobPosting->company->name ?? 'N/A' }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="space-y-2">
                                    <!-- Overall Score -->
                                    <livewire:components.score-badge
                                        :key="'overall_' . ($rating->id ?? $rating->rating_id)"
                                        scoreType="overall_score"
                                        :score="$rating->overall_score"
                                        :selectedMetric="$selectedMetric"
                                    />

                                    <!-- Location Score -->
                                    <livewire:components.score-badge
                                        :key="'location_' . ($rating->id ?? $rating->rating_id)"
                                        scoreType="location_score"
                                        :score="$rating->location_score"
                                        :selectedMetric="$selectedMetric"
                                    />

                                    <!-- Tech Score -->
                                    <livewire:components.score-badge
                                        :key="'tech_' . ($rating->id ?? $rating->rating_id)"
                                        scoreType="tech_score"
                                        :score="$rating->tech_score"
                                        :selectedMetric="$selectedMetric"
                                    />

                                    <!-- Team Size Score -->
                                    <livewire:components.score-badge
                                        :key="'team_' . ($rating->id ?? $rating->rating_id)"
                                        scoreType="team_size_score"
                                        :score="$rating->team_size_score"
                                        :selectedMetric="$selectedMetric"
                                    />

                                    <!-- Leadership Score -->
                                    <livewire:components.score-badge
                                        :key="'leadership_' . ($rating->id ?? $rating->rating_id)"
                                        scoreType="leadership_score"
                                        :score="$rating->leadership_score"
                                        :selectedMetric="$selectedMetric"
                                    />
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="whitespace-nowrap">
                                {{ $rating->rated_at->format('Y-m-d H:i') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center text-zinc-500 dark:text-zinc-400 py-8">
                                <i class="fas fa-star text-4xl mb-4"></i>
                                <p class="text-lg">No ratings found matching your criteria.</p>
                                @if($search || $ratingTypeFilter || $companyFilter || $scoreRangeFilter || $locationFilter)
                                <flux:button size="sm" variant="outline" wire:click="clearAllFilters" class="mt-2">
                                    Clear all filters
                                </flux:button>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

        </flux:card>

        <!-- Job Modal Component -->
        <livewire:jobs.job-modal
            wire:key="job-modal-{{ $selectedRating?->id ?? 'none' }}"
            :rating="null"
            :currentIndex="0"
            :total="0"
        />

    </flux:main>
</div>
