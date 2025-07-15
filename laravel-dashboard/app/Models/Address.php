<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'vejnavn',
        'husnr',
        'postnr',
        'postnrnavn',
        'full_address',
    ];

    /**
     * Get formatted full address
     */
    public function getFormattedAddressAttribute(): string
    {
        return "{$this->vejnavn} {$this->husnr}, {$this->postnr} {$this->postnrnavn}";
    }

    /**
     * Search addresses by query with intelligent parsing for Danish address format
     * Handles format: "Street Number, Postal Code City" (e.g., "Holbækvej 43, 4000 Roskilde")
     */
    public static function searchByQuery(string $query, int $limit = 10)
    {
        $query = trim($query);
        
        if (strlen($query) < 2) {
            return collect();
        }

        $addressQuery = self::query();

        // Check if query contains a comma (Danish address format)
        if (strpos($query, ',') !== false) {
            return self::searchDanishAddressFormat($query, $limit);
        }

        // Check if the query looks like a postal code (4 digits)
        if (preg_match('/^\d{4}/', $query)) {
            return self::searchByPostalCode($query, $limit);
        }

        // Check if query starts with digits (could be house number)
        if (preg_match('/^\d+/', $query)) {
            return self::searchByHouseNumber($query, $limit);
        }

        // General text search for street names and cities
        return self::searchGeneral($query, $limit);
    }

    /**
     * Search using Danish address format: "Street Number, PostalCode City"
     */
    private static function searchDanishAddressFormat(string $query, int $limit = 10)
    {
        [$streetPart, $cityPart] = array_map('trim', explode(',', $query, 2));
        
        $addressQuery = self::query();

        // Parse the city part for postal code
        $postalCode = null;
        $cityName = null;
        
        if (preg_match('/^(\d{4})\s*(.*)/', $cityPart, $matches)) {
            $postalCode = $matches[1];
            $cityName = trim($matches[2]);
        } else {
            // If no postal code in city part, treat entire city part as city name
            $cityName = $cityPart;
        }

        // Parse the street part for street name and house number
        $streetName = null;
        $houseNumber = null;
        
        if (preg_match('/^(.+?)\s+(\d+[a-zA-Z]*)$/', $streetPart, $matches)) {
            $streetName = trim($matches[1]);
            $houseNumber = trim($matches[2]);
        } else {
            $streetName = $streetPart;
        }

        // Build the query with filters
        $addressQuery->where(function ($q) use ($postalCode, $cityName, $streetName, $houseNumber) {
            // Filter by postal code if provided
            if ($postalCode) {
                $q->where('postnr', $postalCode);
            }

            // Filter by city name if provided
            if (!empty($cityName)) {
                $q->where('postnrnavn', 'LIKE', '%' . $cityName . '%');
            }

            // Filter by street name if provided
            if (!empty($streetName)) {
                $q->where('vejnavn', 'LIKE', '%' . $streetName . '%');
            }

            // Filter by house number if provided
            if ($houseNumber) {
                $q->where('husnr', 'LIKE', $houseNumber . '%');
            }
        });

        // Order by relevance
        $addressQuery->orderByRaw("
            CASE 
                WHEN vejnavn LIKE ? AND husnr LIKE ? THEN 1
                WHEN vejnavn LIKE ? THEN 2
                WHEN postnrnavn LIKE ? THEN 3
                ELSE 4 
            END, vejnavn, husnr
        ", [
            $streetName . '%', 
            ($houseNumber ? $houseNumber . '%' : '%'),
            $streetName . '%', 
            ($cityName ? $cityName . '%' : '%')
        ]);

        return $addressQuery->limit($limit)->get();
    }

    /**
     * Search by postal code
     */
    private static function searchByPostalCode(string $query, int $limit = 10)
    {
        // Extract 4-digit postal code
        preg_match('/^(\d{4})(.*)/', $query, $matches);
        $postalCode = $matches[1];
        $remaining = trim($matches[2]);

        $addressQuery = self::where('postnr', $postalCode);

        if (!empty($remaining)) {
            $addressQuery->where(function ($q) use ($remaining) {
                $q->where('vejnavn', 'LIKE', '%' . $remaining . '%')
                  ->orWhere('postnrnavn', 'LIKE', '%' . $remaining . '%');
            });
        }

        return $addressQuery->orderBy('vejnavn')->orderBy('husnr')->limit($limit)->get();
    }

    /**
     * Search by house number
     */
    private static function searchByHouseNumber(string $query, int $limit = 10)
    {
        preg_match('/^(\d+[a-zA-Z]?)(.*)/', $query, $matches);
        $houseNumber = $matches[1];
        $remaining = trim($matches[2]);

        $addressQuery = self::where('husnr', 'LIKE', $houseNumber . '%');

        if (!empty($remaining)) {
            $words = array_filter(explode(' ', $remaining));
            foreach ($words as $word) {
                $addressQuery->where(function ($q) use ($word) {
                    $q->where('vejnavn', 'LIKE', '%' . $word . '%')
                      ->orWhere('postnrnavn', 'LIKE', '%' . $word . '%');
                });
            }
        }

        return $addressQuery->orderBy('vejnavn')->orderBy('husnr')->limit($limit)->get();
    }

    /**
     * General text search
     */
    private static function searchGeneral(string $query, int $limit = 10)
    {
        $words = array_filter(explode(' ', $query));
        
        $addressQuery = self::query();

        foreach ($words as $word) {
            $addressQuery->where(function ($q) use ($word) {
                $q->where('vejnavn', 'LIKE', '%' . $word . '%')
                  ->orWhere('postnrnavn', 'LIKE', '%' . $word . '%')
                  ->orWhere('full_address', 'LIKE', '%' . $word . '%');
            });
        }

        // Order by relevance
        $addressQuery->orderByRaw("
            CASE 
                WHEN vejnavn LIKE ? THEN 1
                WHEN postnrnavn LIKE ? THEN 2  
                WHEN full_address LIKE ? THEN 3
                ELSE 4 
            END, vejnavn, husnr
        ", [$query . '%', $query . '%', $query . '%']);

        return $addressQuery->limit($limit)->get();
    }
}
