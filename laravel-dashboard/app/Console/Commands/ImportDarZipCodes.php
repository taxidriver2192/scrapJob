<?php

namespace App\Console\Commands;

use App\Models\ZipCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportDarZipCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:dar-zips {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import ZIP codes from Danish Address and Real Estate Data (DAR/DAWA)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Importing ZIP codes from DAWA API...');

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('Running in dry-run mode - no changes will be made');
        }

        try {
            // Fetch data from DAWA API
            $response = Http::timeout(60)->get('https://api.dataforsyningen.dk/postnumre');

            if (!$response->successful()) {
                $this->error('Failed to fetch data from DAWA API');
                return 1;
            }

            $zipCodes = $response->json();
            $this->info('Retrieved ' . count($zipCodes) . ' ZIP codes from API');

            $imported = 0;
            $updated = 0;

            foreach ($zipCodes as $zipData) {
                $postnr = $zipData['nr'];
                $city = $zipData['navn'];
                $cityNorm = $this->normalizeCity($city);

                // Extract coordinates if available
                $lat = null;
                $lon = null;
                if (isset($zipData['visueltcenter']) && is_array($zipData['visueltcenter'])) {
                    $lon = $zipData['visueltcenter'][0] ?? null;
                    $lat = $zipData['visueltcenter'][1] ?? null;
                }

                if (!$dryRun) {
                    $zipCode = ZipCode::updateOrCreate(
                        ['postnr' => $postnr],
                        [
                            'city' => $city,
                            'city_norm' => $cityNorm,
                            'lat' => $lat,
                            'lon' => $lon,
                            'weight' => $this->calculateWeight($city, $postnr)
                        ]
                    );

                    if ($zipCode->wasRecentlyCreated) {
                        $imported++;
                    } else {
                        $updated++;
                    }
                } else {
                    $this->line("Would import: {$postnr} - {$city} (normalized: {$cityNorm})");
                }
            }

            if (!$dryRun) {
                $this->info("Import completed: {$imported} new, {$updated} updated ZIP codes");
            } else {
                $this->info("Dry run completed - would process " . count($zipCodes) . " ZIP codes");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Error importing ZIP codes: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Normalize city name for consistent lookups
     */
    private function normalizeCity(string $city): string
    {
        return Str::lower(
            str_replace(['ø', 'æ', 'å'], ['o', 'ae', 'aa'],
                trim(preg_replace('/[^a-zA-ZøæåØÆÅ\s]/', '', $city))
            )
        );
    }

    /**
     * Calculate weight for ZIP code preference
     * Higher weight = more preferred when multiple ZIPs exist for same city
     */
    private function calculateWeight(string $city, string $postnr): int
    {
        // Copenhagen center gets highest weight
        if (str_contains(strtolower($city), 'københavn') && in_array($postnr, ['1150', '1151', '1152'])) {
            return 100;
        }

        // Other Copenhagen districts
        if (str_contains(strtolower($city), 'københavn')) {
            return 50;
        }

        // Major cities get higher weight for their main ZIP
        $majorCities = [
            'århus' => ['8000'],
            'odense' => ['5000'],
            'aalborg' => ['9000'],
            'esbjerg' => ['6700'],
            'randers' => ['8900'],
            'kolding' => ['6000'],
            'horsens' => ['8700'],
            'vejle' => ['7100'],
            'roskilde' => ['4000'],
            'herning' => ['7400']
        ];

        $cityNorm = $this->normalizeCity($city);
        foreach ($majorCities as $majorCity => $mainZips) {
            if (str_contains($cityNorm, $majorCity) && in_array($postnr, $mainZips)) {
                return 75;
            }
        }

        return 1; // Default weight
    }
}
