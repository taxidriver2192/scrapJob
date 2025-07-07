<div>
    <div class="container mt-4">
        <h1><i class="fas fa-chart-line text-primary me-2"></i>Dashboard Overview</h1>
        
        <div class="row mt-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>{{ $totalJobs }}</h4>
                                <p class="mb-0">Total Jobs</p>
                            </div>
                            <i class="fas fa-briefcase fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>{{ $totalCompanies }}</h4>
                                <p class="mb-0">Companies</p>
                            </div>
                            <i class="fas fa-building fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>{{ $totalRatings }}</h4>
                                <p class="mb-0">AI Ratings</p>
                            </div>
                            <i class="fas fa-star fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>{{ $avgScore ? round($avgScore) : 'N/A' }}</h4>
                                <p class="mb-0">Avg Match Score</p>
                            </div>
                            <i class="fas fa-chart-bar fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-clock me-2"></i>Recent Jobs</h5>
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
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentJobs as $job)
                                    <tr>
                                        <td>{{ $job->title }}</td>
                                        <td>{{ $job->company->name ?? 'N/A' }}</td>
                                        <td>{{ $job->location }}</td>
                                        <td>{{ $job->posted_date ? $job->posted_date->format('Y-m-d') : 'N/A' }}</td>
                                        <td>
                                            @if($job->apply_url)
                                            <a href="{{ $job->apply_url }}" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="fas fa-external-link-alt"></i> Apply
                                            </a>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="/jobs" class="btn btn-primary">View All Jobs</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
