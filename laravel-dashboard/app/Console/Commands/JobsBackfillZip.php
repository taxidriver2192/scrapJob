<?php

namespace App\Console\Commands;

use App\Models\JobPosting;
use App\Services\CityZipService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class JobsBackfillZip extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'jobs:backfill-zip
                            {--dry-run : Show what would be updated without making changes}
                            {--chunk=1000 : Number of jobs to process in each batch}
                            {--limit= : Maximum number of jobs to process (optional)}';

    /**
     * The console command description.
     */
    protected $description = 'Backfill city names and ZIP codes for job postings using the new city-zip service';

    private CityZipService $cityZipService;
    private string $logChannel = 'zip_backfill';

    public function __construct(CityZipService $cityZipService)
    {
        parent::__construct();
        $this->cityZipService = $cityZipService;
    }

    /**
     * Log message to both console and dedicated log file
     */
    private function logInfo(string $message): void
    {
        $this->info($message);
        Log::channel($this->logChannel)->info($message);
    }

    /**
     * Log warning to both console and dedicated log file
     */
    private function logWarning(string $message): void
    {
        $this->warn($message);
        Log::channel($this->logChannel)->warning($message);
    }

    /**
     * Log error to both console and dedicated log file
     */
    private function logError(string $message): void
    {
        $this->error($message);
        Log::channel($this->logChannel)->error($message);
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->logInfo('🔄 Starting city and ZIP code backfill...');
        $this->logInfo('Mode: ' . ($isDryRun ? 'DRY RUN' : 'LIVE UPDATE'));
        $this->logInfo('Chunk size: ' . $chunkSize);
        if ($limit) {
            $this->logInfo('Limit: ' . $limit . ' jobs');
        }

        // Count jobs that need ZIP code or city updates
        $query = JobPosting::where(function($q) {
                $q->whereNull('zipcode')
                  ->orWhere('zipcode', '')
                  ->orWhereNull('city')
                  ->orWhere('city', '');
            })
            ->orderBy('job_id');

        if ($limit) {
            $query->limit($limit);
        }

        $totalJobs = $query->count();
        $this->logInfo("Found {$totalJobs} jobs needing city or ZIP code updates");

        if ($totalJobs === 0) {
            $this->logInfo('✅ No jobs need city or ZIP code updates');
            return 0;
        }

        $processed = 0;
        $updated = 0;
        $skipped = 0;

        $query->chunk($chunkSize, function ($jobs) use ($isDryRun, &$processed, &$updated, &$skipped) {
            foreach ($jobs as $job) {
                $processed++;

                if ($processed % 100 === 0) {
                    $this->logInfo("Progress: {$processed}/{$this->getTotalJobs()} jobs processed");
                }

                $this->processJob($job, $isDryRun, $updated, $skipped);
            }
        });

        $this->logSummary($processed, $updated, $skipped, $isDryRun);

        return 0;
    }

    /**
     * Process a single job
     */
    private function processJob(JobPosting $job, bool $isDryRun, int &$updated, int &$skipped): void
    {
        // Extract city from job location
        $city = $this->extractCity($job->location ?? '');

        if (!$city) {
            $skipped++;
            Log::channel($this->logChannel)->debug("Skipped job {$job->job_id}: No city found in location '{$job->location}'");
            return;
        }

        // Get the best ZIP code using our service
        $contextZip = $job->company?->zipcode;
        $bestZip = $this->cityZipService->bestZip($city, $contextZip);

        if ($bestZip) {
            if (!$isDryRun) {
                $job->update([
                    'zipcode' => $bestZip,
                    'city' => $city
                ]);
            }
            $updated++;

            Log::channel($this->logChannel)->info(
                ($isDryRun ? 'Would update' : 'Updated') .
                " job {$job->job_id}: city='{$city}', zipcode={$bestZip}" .
                ($contextZip ? " (context: {$contextZip})" : "")
            );
        } else {
            $skipped++;
            Log::channel($this->logChannel)->warning("No ZIP found for job {$job->job_id}: '{$city}' (location: '{$job->location}')");
        }
    }

    /**
     * Log the final summary
     */
    private function logSummary(int $processed, int $updated, int $skipped, bool $isDryRun): void
    {
        $successRate = $processed > 0 ? round(($updated / $processed) * 100, 1) : 0;

        $this->logInfo('📊 Backfill Summary:');
        $this->logInfo("Total processed: {$processed}");
        $this->logInfo("Successfully updated: {$updated}");
        $this->logInfo("Skipped (no city/ZIP): {$skipped}");
        $this->logInfo("Success rate: {$successRate}%");

        if ($isDryRun) {
            $this->logInfo('🔍 This was a dry run. Remove --dry-run to apply changes.');
        } else {
            $this->logInfo('✅ City and ZIP code backfill completed!');
        }
    }

    /**
     * Get total jobs count (helper for progress reporting)
     */
    private function getTotalJobs(): int
    {
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $query = JobPosting::where(function($q) {
            $q->whereNull('zipcode')
              ->orWhere('zipcode', '')
              ->orWhereNull('city')
              ->orWhere('city', '');
        });

        if ($limit) {
            $query->limit($limit);
        }

        return $query->count();
    }

    /**
     * Extract city name from job location string
     *
     * @param string $location
     * @return string|null
     */
    private function extractCity(string $location): ?string
    {
        if (empty($location)) {
            return null;
        }

        // Common patterns in Danish job locations:
        // "København, Danmark"
        // "Copenhagen, Denmark"
        // "Aalborg"
        // "8000 Aarhus C"
        // "2100 København Ø"

        // First try: Remove common suffixes
        $cleaned = preg_replace('/,?\s*(Danmark|Denmark|DK)\s*$/i', '', $location);

        // Second try: Extract city from "postnr city" format
        if (preg_match('/^\d{4}\s+(.+)$/', $cleaned, $matches)) {
            return trim($matches[1]);
        }

        // Third try: Take everything before first comma
        $parts = explode(',', $cleaned);
        $city = trim($parts[0]);

        // Remove any leading postal codes
        $city = preg_replace('/^\d{4}\s+/', '', $city);

        return !empty($city) ? $city : null;
    }
}
