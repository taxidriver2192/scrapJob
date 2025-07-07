<div>
    <div class="container mt-4">
        <h1><i class="fas fa-building text-primary me-2"></i>Companies</h1>
        
        <!-- Search Bar -->
        <div class="row mt-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search companies...">
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

        <!-- Companies Table -->
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>Company Directory</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Job Count</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($companies as $company)
                            <tr>
                                <td>
                                    <strong>{{ $company->name }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $company->job_postings_count }} jobs</span>
                                </td>
                                <td>
                                    <a href="/jobs?search={{ urlencode($company->name) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> View Jobs
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-4">
                                    <i class="fas fa-search fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">No companies found matching your search criteria.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                {{ $companies->links() }}
            </div>
        </div>
    </div>
</div>
