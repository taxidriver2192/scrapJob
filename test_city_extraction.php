<?php

// Test city extraction fixes
function extractCity($location) {
    if (!$location) {
        return null;
    }

    // Skip Swedish locations
    if (str_contains($location, 'Sverige')) {
        return null;
    }

    // Skip if the location starts with "Region" (it's not a city)
    if (preg_match('/^Region\s+/i', $location)) {
        return null;
    }

    $city = trim(strstr($location, ',', true) ?: $location); // first part before the first comma
    $city = preg_replace('/\s*\(.*?\)\s*/', '', $city); // drop parentheses if any

    // Handle "og omegn" (and surrounding area) pattern
    $city = preg_replace('/\s+og\s+omegn$/i', '', $city);

    // Handle special cases and remove municipality suffix
    $city = cleanCityName($city);

    return $city !== '' ? $city : null;
}

function cleanCityName($city) {
    // Handle "Københavns Kommune" → "København" specifically
    if (mb_strtolower($city) === 'københavns kommune') {
        return 'København';
    }

    // Remove municipality suffix
    $city = preg_replace('/\s+Kommune$/i', '', $city);

    // Handle specific municipality name mappings
    $cityMappings = [
        'brønshøj-husum' => 'brønshøj',
        'høje-taastrup' => 'taastrup', 
        'højetaastrup' => 'taastrup',
        'lyngby-taarbæk' => 'lyngby',
        'kongens lyngby enghave' => 'lyngby',
        'greve strand' => 'greve',
        'frederiksberg' => 'frederiksberg',
        'gladsaxe' => 'gladsaxe',
    ];

    $cityLower = mb_strtolower($city);
    if (isset($cityMappings[$cityLower])) {
        return $cityMappings[$cityLower];
    }

    return $city;
}

echo "Testing city extraction fixes...\n\n";

$testCases = [
    "Brønshøj-Husum, Region Hovedstaden, Danmark",
    "Frederiksberg Kommune, Region Hovedstaden, Danmark", 
    "Gladsaxe, Region Hovedstaden, Danmark",
    "Gladsaxe Kommune, Region Hovedstaden, Danmark",
    "Greve Strand, Region Sjælland, Danmark",
    "Høje-Taastrup Kommune, Region Hovedstaden, Danmark",
    "Kongens Lyngby Enghave, Region Hovedstaden, Danmark",
    "Lyngby-Taarbæk Kommune, Region Hovedstaden, Danmark",
    "Region Hovedstaden, Danmark"
];

foreach ($testCases as $location) {
    $extracted = extractCity($location);
    printf("%-50s -> %s\n", $location, $extracted ?: 'NULL');
}
