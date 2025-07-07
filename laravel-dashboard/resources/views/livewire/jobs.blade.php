<div>
    <div class="container mt-4">
        <h1><i class="fas fa-briefcase text-primary me-2"></i>Jobs</h1>
        
        <!-- Search Bar -->
        <div class="row mt-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search jobs, companies, or locations...">
                </div>
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

        <!-- Jobs Table -->
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>Job Listings</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Company</th>
                                <th>Location</th>
                                <th>Posted</th>
                                <th>Work Type</th>
                                <th>Match Score</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jobs as $job)
                            <tr>
                                <td>
                                    <strong>{{ $job->title }}</strong>
                                    @if($job->applicants)
                                        <br><small class="text-muted">{{ $job->applicants }} applicants</small>
                                    @endif
                                </td>
                                <td>{{ $job->company->name ?? 'N/A' }}</td>
                                <td>{{ $job->location }}</td>
                                <td>{{ $job->posted_date ? $job->posted_date->format('Y-m-d') : 'N/A' }}</td>
                                <td>
                                    @if($job->work_type)
                                        <span class="badge bg-secondary">{{ $job->work_type }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($job->jobRatings->isNotEmpty())
                                        @php $rating = $job->jobRatings->first(); @endphp
                                        <span class="badge bg-{{ $rating->overall_score >= 80 ? 'success' : ($rating->overall_score >= 60 ? 'warning' : 'danger') }}">
                                            {{ $rating->overall_score }}%
                                        </span>
                                    @else
                                        <span class="text-muted">No rating</span>
                                    @endif
                                </td>
                                <td>
                                    @if($job->apply_url)
                                        <a href="{{ $job->apply_url }}" target="_blank" class="btn btn-sm btn-primary">
                                            <i class="fas fa-external-link-alt"></i> Apply
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-search fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">No jobs found matching your search criteria.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                {{ $jobs->links() }}
            </div>
        </div>
    </div>
</div>
