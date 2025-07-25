<?php

namespace App\Console\Commands;

use App\Models\Address;
use App\Models\JobPosting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BackfillJobZipFromAddresses extends Command
{
    protected $signature = 'jobs:backfill-zip
                            {--dry-run : Do not write anything to the database}
                            {--limit=0 : Limit number of rows processed}
                            {--chunk=500 : Chunk size}
                            {--city= : Only process rows where the parsed city equals this (debug)}
                            {--debug : Show detailed debug information about non-matching cities}';

    protected $description = 'Infer and fill job_postings.zipcode (and city) from addresses table using the location string.';

    public function handle(): int
    {
        $options = $this->getCommandOptions();

        // Build city-to-zipcode lookup table once at the start
        $this->info('Building city-to-zipcode lookup table...');
        $cityLookup = $this->buildCityLookupTable();
        $this->info('Found ' . count($cityLookup) . ' unique city names in addresses table.');

        $query = $this->buildQuery($options);
        $total = $query->count();
        $this->info("Processing {$total} job_postings…");

        if ($total === 0) {
            return self::SUCCESS;
        }

        $stats = $this->processJobsWithLookup($query, $options, $cityLookup);
        $this->displayResults($stats, $options['dry']);

        return self::SUCCESS;
    }

    private function getCommandOptions(): array
    {
        return [
            'dry' => (bool) $this->option('dry-run'),
            'limit' => (int) $this->option('limit'),
            'chunk' => (int) $this->option('chunk'),
            'only' => $this->option('city'),
            'debug' => (bool) $this->option('debug'),
        ];
    }

    private function buildQuery(array $options)
    {
        $query = JobPosting::query()
            ->whereNull('zipcode')
            ->whereNotNull('location');

        if ($options['only']) {
            $query->where('location', 'like', "%{$options['only']}%");
        }

        if ($options['limit'] > 0) {
            $query->limit($options['limit']);
        }

        return $query;
    }

    /**
     * Build a lookup table of city names to possible zipcodes
     * This is much more efficient than querying for each job
     */
    private function buildCityLookupTable(): array
    {
        $lookup = [];

        // Get all unique city-zipcode combinations efficiently
        $cityZips = Address::select('postnrnavn', 'postnr')
            ->distinct()
            ->orderBy('postnrnavn')
            ->get();

        foreach ($cityZips as $record) {
            $cityName = mb_strtolower(trim($record->postnrnavn));

            if (!isset($lookup[$cityName])) {
                $lookup[$cityName] = [];
            }

            if (!in_array($record->postnr, $lookup[$cityName])) {
                $lookup[$cityName][] = $record->postnr;
            }

            // Also add partial matches for major cities
            // E.g., "København K" -> also match "København"
            $baseCityName = $this->extractBaseCityName($cityName);
            if ($baseCityName !== $cityName) {
                if (!isset($lookup[$baseCityName])) {
                    $lookup[$baseCityName] = [];
                }
                if (!in_array($record->postnr, $lookup[$baseCityName])) {
                    $lookup[$baseCityName][] = $record->postnr;
                }
            }
        }

        return $lookup;
    }

    /**
     * Extract base city name from district names
     * E.g., "København K" -> "København", "Aarhus C" -> "Aarhus"
     */
    private function extractBaseCityName(string $cityName): string
    {
        // Common Danish city district patterns
        $patterns = [
            '/^(.+?)\s+[kvcnøsøvst]$/i',  // København K, Aarhus C, etc.
            '/^(.+?)\s+(øst|vest|nord|syd|centrum)$/i',  // Aalborg Øst, etc.
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $cityName, $matches)) {
                return mb_strtolower(trim($matches[1]));
            }
        }

        return $cityName;
    }

    private function processJobsWithLookup($query, array $options, array $cityLookup): array
    {
        $stats = ['updated' => 0, 'ambiguous' => 0, 'missingCity' => 0];
        $bar = $this->output->createProgressBar($query->count());
        $bar->start();

        $query->chunkById($options['chunk'], function ($jobs) use (&$stats, $options, $cityLookup, $bar) {
            foreach ($jobs as $job) {
                $this->processJobWithLookup($job, $stats, $options, $cityLookup);
                $bar->advance();
            }
        });

        $bar->finish();
        return $stats;
    }

    private function processJobWithLookup($job, array &$stats, array $options, array $cityLookup): void
    {
        $city = $this->extractCity($job->location);

        if ($options['only'] && mb_strtolower($options['only']) !== mb_strtolower($city ?? '')) {
            return;
        }

        if (!$city) {
            $stats['missingCity']++;
            if ($options['debug']) {
                $this->warn("No city extracted from location: '{$job->location}'");
            }
            return;
        }

        $selectedZip = $this->findZipFromLookup($city, $job, $cityLookup);

        if ($selectedZip) {
            if (!$options['dry']) {
                $job->update(['city' => $city, 'zipcode' => (string) $selectedZip]);
            }
            $stats['updated']++;
            if ($options['debug']) {
                $this->info("✓ Matched '{$city}' → {$selectedZip} (from: '{$job->location}')");
            }
        } else {
            $stats['ambiguous']++;
            if ($options['debug']) {
                $candidates = $cityLookup[mb_strtolower($city)] ?? [];
                $this->warn("✗ No match for '{$city}' (from: '{$job->location}') - candidates: " . implode(', ', $candidates));
            }
            $this->logAmbiguousCity($job, $city, $cityLookup);
        }
    }

    private function findZipFromLookup(string $city, $job, array $cityLookup): ?string
    {
        $cityKey = mb_strtolower($city);
        $zips = $cityLookup[$cityKey] ?? [];

        if (empty($zips)) {
            return null;
        }

        if (count($zips) === 1) {
            return $zips[0];
        }

        // If company zipcode helps disambiguate
        if ($job->company?->zipcode && in_array($job->company->zipcode, $zips, true)) {
            return $job->company->zipcode;
        }

        // For København, prefer central zip codes
        if ($cityKey === 'københavn') {
            return $this->findPreferredKoebenhavnZip($zips);
        }

        // For other cities with multiple candidates, don't guess
        return null;
    }

    private function findPreferredKoebenhavnZip(array $zips): ?string
    {
        $preferredZips = ['1150', '1151', '1152', '1153', '1154', '1155', '1156', '1157', '1158', '1159', '1160'];

        foreach ($preferredZips as $preferred) {
            if (in_array($preferred, $zips, true)) {
                return $preferred;
            }
        }

        return null;
    }

    private function logAmbiguousCity($job, string $city, array $cityLookup): void
    {
        $cityKey = mb_strtolower($city);
        $zips = $cityLookup[$cityKey] ?? [];

        Log::info('Ambiguous or missing zip for city', [
            'job_id' => $job->job_id,
            'city' => $city,
            'candidates' => $zips,
            'company_zip' => $job->company->zipcode ?? null,
        ]);
    }

    private function displayResults(array $stats, bool $dry): void
    {
        $this->newLine(2);
        $this->info("Updated: {$stats['updated']}");
        $this->info("Ambiguous/missing zip: {$stats['ambiguous']}");
        $this->info("Missing city in location: {$stats['missingCity']}");

        if ($dry) {
            $this->warn('Dry run: no rows were updated.');
        }
    }

    /**
     * Extracts the city from strings like:
     * "Taastrup, Region Hovedstaden, Danmark"
     * "Rødovre Kommune, Region Hovedstaden, Danmark"
     */
    private function extractCity(?string $location): ?string
    {
        if (!$location) {
            return null;
        }

        // Skip Swedish locations
        if (str_contains($location, 'Sverige')) {
            return null;
        }

        $city = trim(Str::before($location, ',')); // first part before the first comma
        $city = preg_replace('/\s*\(.*?\)\s*/', '', $city); // drop parentheses if any

        // Handle special cases and remove municipality suffix
        $city = $this->cleanCityName($city);

        return $city !== '' ? $city : null;
    }

    private function cleanCityName(string $city): string
    {
        // Handle "Københavns Kommune" → "København" specifically
        if (mb_strtolower($city) === 'københavns kommune') {
            return 'København';
        }

        // Remove municipality suffix
        return preg_replace('/\s+Kommune$/i', '', $city);
    }
}
