<?php

return [

    'timezone' => env('APP_TIMEZONE', 'Europe/Copenhagen'),

    'moderate_seller_listings' => (bool) env('MARKETPLACE_MODERATE_SELLER_LISTINGS', true),

    'block_publish_on_overdue_invoice' => (bool) env('MARKETPLACE_BLOCK_ON_OVERDUE_INVOICE', true),

    'invoice_payment_grace_days' => (int) env('MARKETPLACE_INVOICE_PAYMENT_GRACE_DAYS', 14),

    'listing_expiry_days' => [
        'seller' => (int) env('MARKETPLACE_SELLER_LISTING_EXPIRY_DAYS', 90),
        'dealer_subscription' => (int) env('MARKETPLACE_DEALER_SUBSCRIPTION_LISTING_EXPIRY_DAYS', 0),
        'dealer_usage' => (int) env('MARKETPLACE_DEALER_USAGE_LISTING_EXPIRY_DAYS', 0),
    ],

    'listing_expiry_warning_days' => (int) env('MARKETPLACE_LISTING_EXPIRY_WARNING_DAYS', 7),

    'usage_invoice_day_of_month' => (int) env('MARKETPLACE_USAGE_INVOICE_DAY', 1),

];
