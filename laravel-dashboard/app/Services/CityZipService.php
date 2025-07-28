<?php

namespace App\Services;

use App\Models\CityAlias;
use App\Models\ZipCode;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CityZipService
{
    /**
     * Get all possible ZIP codes for a city name
     *
     * @param string $cityName Raw city name from user input
     * @return Collection<ZipCode>
     */
    public function zipsFor(string $cityName): Collection
    {
        $normalizedCity = $this->normalize($cityName);
        
        // First, check if this is a known alias
        $alias = CityAlias::where('alias', $normalizedCity)->first();
        if ($alias) {
            $normalizedCity = $alias->city_norm;
        }
        
        // Get all ZIP codes for this normalized city
        return ZipCode::where('city_norm', $normalizedCity)->get();
    }

    /**
     * Get the best/preferred ZIP code for a city
     *
     * @param string $cityName Raw city name from user input
     * @param string|null $contextZip Optional context ZIP (e.g., from company)
     * @return string|null The best ZIP code or null if none found
     */
    public function bestZip(string $cityName, ?string $contextZip = null): ?string
    {
        $zips = $this->zipsFor($cityName);
        
        if ($zips->isEmpty()) {
            return null;
        }
        
        // If we have context ZIP and it matches one of the possible zips, use it
        if ($contextZip && $zips->where('postnr', $contextZip)->isNotEmpty()) {
            return $contextZip;
        }
        
        // Otherwise, prefer ZIP codes with higher weight, then lowest postnr
        return $zips
            ->sortByDesc('weight')
            ->sortBy('postnr')
            ->first()
            ->postnr;
    }

    /**
     * Normalize a city name for consistent lookup
     *
     * @param string $cityName Raw city name
     * @return string Normalized city name
     */
    public function normalize(string $cityName): string
    {
        return Str::of($cityName)
            ->lower()
            ->trim()
            ->replace(['æ', 'ø', 'å'], ['ae', 'o', 'aa'])
            ->replaceMatches('/[^a-z0-9\s\-]/', '')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
    }

    /**
     * Check if a city name is recognized
     *
     * @param string $cityName Raw city name
     * @return bool True if the city is known
     */
    public function isKnownCity(string $cityName): bool
    {
        return !$this->zipsFor($cityName)->isEmpty();
    }

    /**
     * Get city information including all aliases and ZIP codes
     *
     * @param string $cityName Raw city name
     * @return array City information array
     */
    public function getCityInfo(string $cityName): array
    {
        $normalizedCity = $this->normalize($cityName);
        
        // Check if this is an alias
        $alias = CityAlias::where('alias', $normalizedCity)->first();
        $targetCity = $alias ? $alias->city_norm : $normalizedCity;
        
        // Get all ZIP codes
        $zips = ZipCode::where('city_norm', $targetCity)->get();
        
        // Get all aliases for this city
        $aliases = CityAlias::where('city_norm', $targetCity)->pluck('alias');
        
        return [
            'input' => $cityName,
            'normalized' => $normalizedCity,
            'target_city' => $targetCity,
            'is_alias' => (bool) $alias,
            'zip_codes' => $zips->pluck('postnr')->toArray(),
            'aliases' => $aliases->toArray(),
            'best_zip' => $this->bestZip($cityName),
        ];
    }
}
