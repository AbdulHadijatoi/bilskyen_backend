<?php

/**
 * Equipment mutual-exclusion and compatibility rules for sell/add vehicle forms.
 * Patterns are matched case-insensitively against equipment display names.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Mutual exclusion groups (single-select within each group)
    |--------------------------------------------------------------------------
    */
    'exclusive_groups' => [
        'headlights' => [
            'led headlight',
            'xenon',
            'halogen',
            'matrix led',
            'laser light',
            'adaptive headlight',
        ],
        'wheel_size' => [
            'inch',
            '" wheels',
            '” wheels',
            'alu fælge',
            'alloy wheel',
        ],
        'upholstery' => [
            'leather',
            'læder',
            'alcantara',
            'fabric seat',
            'stof',
            'velour',
        ],
        'seats' => [
            'seat configuration',
            'sædekonfiguration',
            '2 seats',
            '4 seats',
            '5 seats',
            '7 seats',
            '2 sæder',
            '4 sæder',
            '5 sæder',
            '7 sæder',
        ],
        'roof' => [
            'panoramic',
            'sunroof',
            'soltag',
            'convertible',
            'cabriolet',
            'soft top',
            'hard top',
        ],
        'climate' => [
            'climate zone',
            'klima',
            '1-zone',
            '2-zone',
            '3-zone',
            '4-zone',
        ],
        'transmission' => [
            'automatic',
            'manual',
            'automatisk',
            'manuel',
            'cvt',
            'dsg',
        ],
        'airbags' => [
            'airbag',
            'airbags',
        ],
        'drive' => [
            'four-wheel',
            '4wd',
            'awd',
            '4x4',
            'front-wheel',
            'rear-wheel',
            'fwd',
            'rwd',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Duplicate label patterns (keep first match, hide later duplicates)
    |--------------------------------------------------------------------------
    */
    'duplicate_normalize' => true,

    /*
    |--------------------------------------------------------------------------
    | Fuel / body compatibility (hide when incompatible)
    |--------------------------------------------------------------------------
    */
    'ev_only_patterns' => [
        'charging',
        'battery',
        'kwh',
        'heat pump',
        'regenerative',
        'one-pedal',
        'ev ',
        'electric range',
        'kabinevarmer',
        'cabin heater',
        'oil boiler',
    ],

    'ice_only_patterns' => [
        'particulate filter',
        'adblue',
        'diesel',
        'turbo petrol',
    ],
];
