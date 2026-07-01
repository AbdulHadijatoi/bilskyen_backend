<?php

return [

    'panel_url' => env('VUE_PANEL_URL', env('FRONTEND_URL', 'http://localhost:5173')),

    'currency' => strtolower(env('PAYMENT_CURRENCY', 'dkk')),

    'stripe_webhook_path' => '/api/v1/webhooks/stripe',

];
