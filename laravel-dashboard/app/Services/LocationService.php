<?php

namespace App\Services;

use App\Models\Address;

class LocationService
{
    /**
     * Get location suggestions based on search query
     */
    public static function getSuggestions(string $query, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $addresses = Address::searchByQuery($query, $limit);

        // Return unique formatted addresses
        return $addresses->map(function ($address) {
            return $address->formatted_address;
        })->unique()->values()->toArray();
    }

    /**
     * Validate if a location exists in the database
     * Check both full_address and formatted_address
     */
    public static function isValidLocation(string $location): bool
    {
        return Address::where('full_address', $location)
            ->orWhere(function($query) use ($location) {
                // Also check if it matches a formatted address
                $query->whereRaw("CONCAT(vejnavn, ' ', husnr, ', ', postnr, ' ', postnrnavn) = ?", [$location]);
            })
            ->exists();
    }

    /**
     * Search for addresses based on input query
     */
    public function searchAddresses(string $query, int $limit = 10): array
    {
        if (strlen($query) < 3) {
            return [];
        }

        return Address::searchByQuery($query, $limit)
            ->map(function ($address) {
                return $address->formatted_address;
            })
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get all available locations (for fallback or testing)
     */
    public static function getAllLocations(): array
    {
        return Address::limit(1000)
            ->get()
            ->map(function ($address) {
                return $address->formatted_address;
            })
            ->toArray();
    }
}
