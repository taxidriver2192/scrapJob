<?php

namespace App\Services;

use App\Models\ZipCode;
use App\Models\CityAlias;

class LocationService
{
    private CityZipService $cityZipService;

    public function __construct(CityZipService $cityZipService)
    {
        $this->cityZipService = $cityZipService;
    }

    /**
     * Get city suggestions based on search query
     */
    public function getSuggestions(string $query, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $normalizedQuery = $this->cityZipService->normalize($query);
        
        // Search in both city names and aliases
        $cities = collect();
        
        // Search in ZIP codes (city names)
        $zipCities = ZipCode::where('city_norm', 'like', $normalizedQuery . '%')
            ->orWhere('city', 'like', $query . '%')
            ->distinct()
            ->limit($limit)
            ->get(['city', 'city_norm'])
            ->pluck('city');
            
        $cities = $cities->merge($zipCities);
        
        // Search in aliases
        $aliasCities = CityAlias::where('alias', 'like', $normalizedQuery . '%')
            ->limit($limit)
            ->get()
            ->map(function($alias) {
                return ZipCode::where('city_norm', $alias->city_norm)->first()?->city;
            })
            ->filter();
            
        $cities = $cities->merge($aliasCities);
        
        return $cities->unique()->take($limit)->values()->toArray();
    }

    /**
     * Validate if a city/location is recognized
     */
    public function isValidLocation(string $location): bool
    {
        return $this->cityZipService->isKnownCity($location);
    }

    /**
     * Get the best ZIP code for a location
     */
    public function getBestZipCode(string $location, ?string $contextZip = null): ?string
    {
        return $this->cityZipService->bestZip($location, $contextZip);
    }

    /**
     * Get all possible ZIP codes for a location
     */
    public function getZipCodes(string $location): array
    {
        return $this->cityZipService->zipsFor($location)->pluck('postnr')->toArray();
    }

    /**
     * Get comprehensive location information
     */
    public function getLocationInfo(string $location): array
    {
        return $this->cityZipService->getCityInfo($location);
    }

    /**
     * Extract city name from a location string
     * This method can be used by other services to parse location strings
     */
    public function extractCityFromLocation(string $location): ?string
    {
        // Remove common patterns that are not city names
        $location = trim($location);
        
        if (empty($location)) {
            return null;
        }
        
        // Remove ", Region X, Danmark" pattern
        $location = preg_replace('/,\s*Region\s+[^,]+,\s*Danmark\s*$/i', '', $location);
        
        // Remove "Kommune" suffix for municipality names
        $location = preg_replace('/\s+Kommune\s*$/i', '', $location);
        
        // Remove " og omegn" (and surroundings)
        $location = preg_replace('/\s+og\s+omegn\s*$/i', '', $location);
        
        // Split by comma and take the first part (usually the city)
        $parts = explode(',', $location);
        $cityPart = trim($parts[0]);
        
        return !empty($cityPart) ? $cityPart : null;
    }

    /**
     * Get all available cities (for dropdowns, autocomplete, etc.)
     */
    public function getAllCities(int $limit = 100): array
    {
        return ZipCode::select('city')
            ->distinct()
            ->orderBy('city')
            ->limit($limit)
            ->pluck('city')
            ->toArray();
    }

    /**
     * Static helper methods for backward compatibility
     */
    public static function getSuggestionsStatic(string $query, int $limit = 10): array
    {
        $service = new static(new CityZipService());
        return $service->getSuggestions($query, $limit);
    }

    public static function isValidLocationStatic(string $location): bool
    {
        $service = new static(new CityZipService());
        return $service->isValidLocation($location);
    }
}
