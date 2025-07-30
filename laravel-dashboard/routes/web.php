<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Dashboard;
use App\Livewire\Jobs\Index as JobsIndex;
use App\Livewire\Jobs\JobDetails;
use App\Livewire\Companies\Index as CompaniesIndex;
use App\Livewire\Companies\CompanyDetails;
use App\Livewire\Ratings;
use App\Livewire\ProfileEdit;
use Illuminate\Support\Facades\DB;
use App\Livewire\JobRatings\Index as JobRatingsIndex;
use App\Livewire\JobRatings\Show as JobRatingsShow;
use App\Livewire\SearchFilters\SkillsFilterSimple;

// Public routes (no authentication required)
Route::get('/test', function () {
    return response('Laravel is working!');
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});

// Database test route
Route::get('/test-db', function () {
    try {
        $count = DB::table('job_postings')->count();
        return response("Database connection works! Found {$count} job postings.");
    } catch (Exception $e) {
        return response("Database error: " . $e->getMessage(), 500);
    }
});

// Protected routes (require authentication)
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard (main page)
    Route::get('/', Dashboard::class)->name('dashboard');

    // Job-related routes
    Route::get('/jobs', JobsIndex::class)->name('jobs');
    Route::get('/job/{jobId}', JobDetails::class)->name('job.details');

    // Company-related routes
    Route::get('/companies', CompaniesIndex::class)->name('companies');
    Route::get('/company/{companyId}', CompanyDetails::class)->name('company.details');
    Route::get('/ratings', Ratings::class)->name('ratings');

    // Profile route (from Breeze)
    Route::view('profile', 'profile')->name('profile');

    // Profile edit route
    Route::get('/profile/edit', ProfileEdit::class)->name('profile.edit');

    // AI Job Rating routes
    Route::get('/job-ratings', JobRatingsIndex::class)->name('job-ratings.index');
    Route::get('/job-ratings/{jobRating}', JobRatingsShow::class)->name('job-ratings.show');

    // Job Queue Management route
    Route::get('/job-queue', \App\Livewire\JobQueue\Index::class)->name('job-queue.index');
});

// Include Breeze authentication routes
require __DIR__.'/auth.php';
