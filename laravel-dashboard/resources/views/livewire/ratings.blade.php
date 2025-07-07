<div>
    <div class="container mt-4">
        <h1><i class="fas fa-star text-primary me-2"></i>Job Ratings</h1>
        
        <!-- Search and Filter -->
        <div class="row mt-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search jobs or companies...">
                </div>
            </div>
            <div class="col-md-4">
                <select wire:model.live="ratingTypeFilter" class="form-select">
                    <option value="">All Rating Types</option>
                    @foreach($ratingTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <select wire:model.live="perPage" class="form-select">
                    <option value="10">10 per page</option>
                    <option value="20">20 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                </select>
            </div>
        </div>

        <!-- Ratings Table -->
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>AI Job Ratings</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Company</th>
                                <th>Overall Score</th>
                                <th>Location</th>
                                <th>Tech</th>
                                <th>Team Size</th>
                                <th>Leadership</th>
                                <th>Rated At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ratings as $rating)
                            <tr>
                                <td>{{ $rating->jobPosting->title ?? 'N/A' }}</td>
                                <td>{{ $rating->jobPosting->company->name ?? 'N/A' }}</td>
                                <td>
                                    @if($rating->overall_score)
                                        <span class="badge bg-{{ $rating->overall_score >= 80 ? 'success' : ($rating->overall_score >= 60 ? 'warning' : 'danger') }}">
                                            {{ $rating->overall_score }}%
                                        </span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($rating->location_score)
                                        <span class="badge bg-secondary">{{ $rating->location_score }}%</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($rating->tech_score)
                                        <span class="badge bg-secondary">{{ $rating->tech_score }}%</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($rating->team_size_score)
                                        <span class="badge bg-secondary">{{ $rating->team_size_score }}%</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($rating->leadership_score)
                                        <span class="badge bg-secondary">{{ $rating->leadership_score }}%</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>{{ $rating->rated_at->format('Y-m-d H:i') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-star fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">No ratings found matching your criteria.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                {{ $ratings->links() }}
            </div>
        </div>
    </div>
</div>
