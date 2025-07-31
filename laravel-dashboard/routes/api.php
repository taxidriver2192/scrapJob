<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\JobPostingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Company API endpoints
Route::prefix('companies')->middleware('api.key')->group(function () {
    // Check if company exists by name
    Route::get('/exists', [CompanyController::class, 'exists']);

    // Create a new company
    Route::post('/', [CompanyController::class, 'store']);

    // Get company by ID
    Route::get('/{id}', [CompanyController::class, 'show']);
});

// Job Posting API endpoints
Route::prefix('jobs')->middleware('api.key')->group(function () {
    // Check if job posting exists by LinkedIn job ID
    Route::get('/exists', [JobPostingController::class, 'exists']);

    // Create a new job posting
    Route::post('/', [JobPostingController::class, 'store']);

    // Get job posting by ID
    Route::get('/{id}', [JobPostingController::class, 'show']);

    // Get job posting by LinkedIn job ID
    Route::get('/linkedin/{linkedinJobId}', [JobPostingController::class, 'showByLinkedInId']);
});
