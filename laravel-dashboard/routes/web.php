<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Dashboard;
use App\Livewire\Jobs;
use App\Livewire\Companies;
use App\Livewire\Queue;
use App\Livewire\Ratings;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('dashboard');
});

Route::get('/jobs', function () {
    return view('jobs');
});

Route::get('/companies', function () {
    return view('companies');
});

Route::get('/queue', function () {
    return view('queue');
});

Route::get('/ratings', function () {
    return view('ratings');
});

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
