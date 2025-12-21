<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        env('FRONTEND_URL', 'http://localhost:5173'),
        env('ADMIN_PANEL_URL', 'http://localhost:5174'),
        'https://panel.bilskyen.dk',
        'https://bilskyen.dk',
        // Allow any subdomain of bilskyen.dk
    ]),

    'allowed_origins_patterns' => [
        '#^https://.*\.bilskyen\.dk$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'Authorization',
        'X-Requested-With',
        'Content-Type',
        'Accept',
    ],

    'max_age' => 86400, // 24 hours - cache preflight requests

    'supports_credentials' => true,

];

