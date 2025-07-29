<?php

/**
 * Manual city aliases configuration
 *
 * This file contains manual overrides for city name aliases that cannot be
 * automatically generated. Add new aliases here when needed.
 *
 * Format: 'alias_name' => 'normalized_city_name'
 */

return [

    // Municipality names (Kommune) - Remove "Kommune" suffix
    'rødovre kommune' => 'rodovre',
    'ballerup kommune' => 'ballerup',
    'hvidovre kommune' => 'hvidovre',
    'frederiksberg kommune' => 'frederiksberg',
    'glostrup kommune' => 'glostrup',
    'brøndby kommune' => 'brondby',
    'greve kommune' => 'greve',
    'aarhus kommune' => 'aarhus c',
    'viborg kommune' => 'viborg',
    'fredericia kommune' => 'fredericia',
    'herning kommune' => 'herning',
    'kolding kommune' => 'kolding',

    // Cities without specific district - map to main postal code
    'aarhus' => 'aarhus c',           // Main Aarhus postal district
    'aarhus og omegn' => 'aarhus c',  // Aarhus and surroundings

    // Special cases for Viby (there are multiple Viby cities)
    'viby' => 'viby j',               // Default to Viby J (near Aarhus) - most common

    // Regions (these are too broad, but we can map to capital city)
    'region hovedstaden' => 'kobenhavn',      // Capital region -> Copenhagen
    'region midtjylland' => 'aarhus c',       // Central Jutland -> Aarhus

    /*
     * Add more aliases here as we encounter them during backfilling.
     *
     * Examples:
     * 'custom_alias' => 'target_city_norm',
     */

];
