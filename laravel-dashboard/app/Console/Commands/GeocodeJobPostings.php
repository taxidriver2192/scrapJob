<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\JobPosting;

class GeocodeJobPostings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:geocode {--force : Force re-geocoding of already geocoded jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Look up lat/lon & postcode for job locations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = JobPosting::query();

        if (!$this->option('force')) {
            $query->whereNull('lat');
        }

        $jobs = $query->cursor();
        $processed = 0;
        $geocoded = 0;

        $this->info('Starting geocoding process...');

        foreach ($jobs as $job) {
            if (empty($job->location)) {
                $this->warn("Job #{$job->job_id} has no location, skipping...");
                continue;
            }

            $key = 'geocode:' . md5($job->location);

            $data = Cache::rememberForever($key, function () use ($job) {
                $this->line("Geocoding: {$job->location}");

                // Check if location contains Danish keywords
                $inDenmark = str_contains(strtolower($job->location), 'danmark') ||
                           str_contains(strtolower($job->location), 'denmark') ||
                           str_contains(strtolower($job->location), 'københav') ||
                           str_contains(strtolower($job->location), 'århus') ||
                           str_contains(strtolower($job->location), 'odense') ||
                           str_contains(strtolower($job->location), 'aalborg');

                if ($inDenmark) {
                    return $this->geocodeWithDAWA($job->location);
                }

                return $this->geocodeWithNominatim($job->location);
            });

            if ($data) {
                $job->update([
                    'lat' => $data['lat'],
                    'lon' => $data['lon'],
                    'postcode' => $data['postcode'],
                ]);
                $geocoded++;
                $this->info("✓ Geocoded #{$job->job_id}: {$job->location} → {$data['postcode']} ({$data['lat']}, {$data['lon']})");
            } else {
                $this->warn("✗ No match for #{$job->job_id}: '{$job->location}'");
            }

            $processed++;

            // Progress indicator for large datasets
            if ($processed % 10 === 0) {
                $this->line("Processed {$processed} jobs...");
            }
        }

        $this->info("Geocoding complete! Processed: {$processed}, Successfully geocoded: {$geocoded}");
        return 0;
    }

    /**
     * Geocode using Danish DAWA service
     */
    private function geocodeWithDAWA(string $location): ?array
    {
        try {
            // Extract city name from location string
            $cityName = $this->extractCityName($location);

            $response = Http::timeout(10)->get('https://api.dataforsyningen.dk/stednavne', [
                'primærtnavn' => $cityName,
                'format' => 'json',
                'per_side' => 1
            ]);

            if ($response->successful()) {
                $results = $response->json();
                $item = $results[0] ?? null;

                if ($item && isset($item['visueltcenter'])) {
                    return [
                        'lat' => $item['visueltcenter'][1], // DAWA returns [lon, lat]
                        'lon' => $item['visueltcenter'][0],
                        'postcode' => $item['postnummer'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->error("DAWA API error for '{$location}': " . $e->getMessage());
        }

        return null;
    }

    /**
     * Geocode using OpenStreetMap Nominatim
     */
    private function geocodeWithNominatim(string $location): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'ScrapJobLaravelApp/1.0 (contact@scrapjob.com)'
            ])->timeout(10)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $location,
                'format' => 'json',
                'addressdetails' => 1,
                'limit' => 1,
            ]);

            // Respect the 1 request per second limit
            sleep(1);

            if ($response->successful()) {
                $results = $response->json();
                $item = $results[0] ?? null;

                if ($item) {
                    return [
                        'lat' => $item['lat'],
                        'lon' => $item['lon'],
                        'postcode' => $item['address']['postcode'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->error("Nominatim API error for '{$location}': " . $e->getMessage());
        }

        return null;
    }

    /**
     * Extract city name from location string
     */
    private function extractCityName(string $location): string
    {
        // Split by common separators and take the first part (usually the city)
        $parts = preg_split('/[,\-\s]+/', $location);
        return trim($parts[0]);
    }
}
