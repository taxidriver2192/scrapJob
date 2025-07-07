<div>
    <div class="container mt-4">
        <h1><i class="fas fa-list text-primary me-2"></i>Job Queue</h1>

        <!-- Status Cards -->
        <div class="row mt-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>{{ $statusCounts['pending'] }}</h4>
                                <p class="mb-0">Pending</p>
                            </div>
                            <i class="fas fa-clock fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>{{ $statusCounts['in_progress'] }}</h4>
                                <p class="mb-0">In Progress</p>
                            </div>
                            <i class="fas fa-spinner fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>{{ $statusCounts['done'] }}</h4>
                                <p class="mb-0">Completed</p>
                            </div>
                            <i class="fas fa-check fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>{{ $statusCounts['error'] }}</h4>
                                <p class="mb-0">Errors</p>
                            </div>
                            <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="row mt-3">
            <div class="col-md-6">
                <select wire:model.live="statusFilter" class="form-select">
                    <option value="">All Status</option>
                    <option value="1">Pending</option>
                    <option value="2">In Progress</option>
                    <option value="3">Done</option>
                    <option value="4">Error</option>
                </select>
            </div>
            <div class="col-md-6">
                <select wire:model.live="perPage" class="form-select">
                    <option value="10">10 per page</option>
                    <option value="20">20 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                </select>
            </div>
        </div>

        <!-- Queue Table -->
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>Queue Items</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Company</th>
                                <th>Queued At</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($queueItems as $item)
                            <tr>
                                <td>{{ $item->jobPosting->title ?? 'N/A' }}</td>
                                <td>{{ $item->jobPosting->company->name ?? 'N/A' }}</td>
                                <td>{{ $item->queued_at->format('Y-m-d H:i:s') }}</td>
                                <td>
                                    <span class="badge bg-{{ $item->status_code == 1 ? 'warning' : ($item->status_code == 2 ? 'info' : ($item->status_code == 3 ? 'success' : 'danger')) }}">
                                        {{ $item->status_text }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <i class="fas fa-list fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">No queue items found.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                {{ $queueItems->links() }}
            </div>
        </div>
    </div>
</div>
