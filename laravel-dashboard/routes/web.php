<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Dashboard;
use App\Livewire\Jobs\Index as JobsIndex;
use App\Livewire\Companies;
use App\Livewire\Queue;
use App\Livewire\Ratings;
use App\Livewire\ProfileEdit;
use Illuminate\Support\Facades\DB;

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

    // Other application routes
    Route::get('/companies', Companies::class)->name('companies');
    Route::get('/queue', Queue::class)->name('queue');
    Route::get('/ratings', Ratings::class)->name('ratings');

    // Profile route (from Breeze)
    Route::view('profile', 'profile')->name('profile');

    // Profile edit route
    Route::get('/profile/edit', ProfileEdit::class)->name('profile.edit');
});

// Include Breeze authentication routes
require __DIR__.'/auth.php';
