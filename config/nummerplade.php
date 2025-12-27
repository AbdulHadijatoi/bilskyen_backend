<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Nummerplade API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Nummerplade API integration
    |
    */

    'base_url' => env('NUMMERPLADE_BASE_URL', 'https://api.nrpla.de'),
    
    'api_token' => env('NUMMERPLADE_API_TOKEN'),
    
    'timeout' => env('NUMMERPLADE_TIMEOUT', 60), // seconds (default timeout for reference data)
    
    'vehicle_lookup_timeout' => env('NUMMERPLADE_VEHICLE_LOOKUP_TIMEOUT', 60), // seconds (timeout for vehicle lookups, can be slower)
    
    'cache' => [
        'ttl' => env('NUMMERPLADE_CACHE_TTL', 86400), // 24 hours in seconds
        'reference_data_ttl' => env('NUMMERPLADE_REFERENCE_CACHE_TTL', 86400), // 24 hours
    ],
    
    'rate_limiting' => [
        'enabled' => env('NUMMERPLADE_RATE_LIMITING_ENABLED', true),
        'requests_per_minute' => env('NUMMERPLADE_RATE_LIMIT', 20),
    ],
];

