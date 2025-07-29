<?php

namespace Database\Seeders;

use App\Models\CityAlias;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CityAliasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Generating city aliases from zip codes data...');

        // Generate aliases dynamically from existing zip codes
        $aliasesCreated = $this->generateAliasesFromZipCodes();

        // Add manual overrides for special cases
        $manualAliases = $this->getManualAliases();
        $manualCount = $this->insertManualAliases($manualAliases);

        $this->command->info("City aliases seeded successfully: {$aliasesCreated} auto + {$manualCount} manual = " . ($aliasesCreated + $manualCount) . " total aliases");
    }

    /**
     * Generate aliases automatically from the zip_codes table
     */
    private function generateAliasesFromZipCodes(): int
    {
        $count = 0;

        // Get all unique normalized city names from zip_codes
        $cities = \DB::table('zip_codes')
            ->select('city_norm')
            ->distinct()
            ->whereNotNull('city_norm')
            ->get();

        foreach ($cities as $city) {
            $cityNorm = $city->city_norm;
            $aliases = $this->generateVariationsFor($cityNorm);

            foreach ($aliases as $alias) {
                CityAlias::updateOrCreate(
                    ['alias' => $alias],
                    ['city_norm' => $cityNorm]
                );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Generate common variations for a city name
     */
    private function generateVariationsFor(string $cityNorm): array
    {
        $variations = [$cityNorm]; // Include the normalized name itself

        // Add common suffixes
        $suffixes = ['', ' c', ' centrum', ' by'];
        foreach ($suffixes as $suffix) {
            if ($suffix !== '') {
                $variations[] = $cityNorm . $suffix;
            }
        }

        // Add København specific variations
        if (str_contains($cityNorm, 'kobenhavn') || $cityNorm === 'kobenhavn') {
            $variations = array_merge($variations, [
                'københavn', 'kbh', 'copenhagen', 'cph',
                'københavn k', 'kbh k', 'københavn c'
            ]);
        }

        // Add Aarhus/Århus variations
        if ($cityNorm === 'aarhus') {
            $variations[] = 'århus';
        }
        if ($cityNorm === 'århus') {
            $variations[] = 'aarhus';
        }

        // Add Aalborg/Ålborg variations
        if ($cityNorm === 'aalborg') {
            $variations[] = 'ålborg';
        }
        if ($cityNorm === 'ålborg') {
            $variations[] = 'aalborg';
        }

        return array_unique($variations);
    }

    /**
     * Get manual aliases for special cases that can't be auto-generated
     */
    private function getManualAliases(): array
    {
        // Load from config file
        $configPath = config_path('city_aliases.php');

        if (file_exists($configPath)) {
            return include $configPath;
        }

        // Return empty array if config doesn't exist - start clean
        return [];
    }

    /**
     * Insert manual aliases
     */
    private function insertManualAliases(array $manualAliases): int
    {
        $count = 0;

        foreach ($manualAliases as $alias => $cityNorm) {
            // Normalize the alias key to match how the service will search for it
            $normalizedAlias = Str::of($alias)
                ->lower()
                ->trim()
                ->replace(['æ', 'ø', 'å'], ['ae', 'o', 'aa'])
                ->replaceMatches('/[^a-z0-9\s\-]/', '')
                ->replaceMatches('/\s+/', ' ')
                ->trim()
                ->toString();

            CityAlias::updateOrCreate(
                ['alias' => $normalizedAlias],
                ['city_norm' => $cityNorm]
            );
            $count++;
        }

        return $count;
    }
}
