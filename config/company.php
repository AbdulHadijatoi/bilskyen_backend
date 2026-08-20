<?php

return [
    'name' => 'Bilskyen',
    'legal_name' => 'Bilskyen ApS',
    'cvr' => '45251853',
    'email' => 'info@bilskyen.dk',
    'phone' => env('COMPANY_PHONE', ''),
    'street' => 'Smedeland 7',
    'postal_code' => '2600',
    'city' => 'Glostrup',
    'region' => 'Capital Region',
    'country' => 'DK',
    'country_name' => 'Denmark',
    'logo' => '/images/og-image.jpg',
    'same_as' => array_values(array_filter([
        env('COMPANY_LINKEDIN_URL', ''),
        env('COMPANY_FACEBOOK_URL', ''),
        env('COMPANY_INSTAGRAM_URL', ''),
    ])),
];
