<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vehicle image optimization profile
    |--------------------------------------------------------------------------
    |
    | Applied to all new dealer/seller vehicle photo uploads before storage.
    |
    */
    'vehicle' => [
        'max_long_edge' => (int) env('VEHICLE_IMAGE_MAX_LONG_EDGE', 2048),
        'start_quality' => (int) env('VEHICLE_IMAGE_START_QUALITY', 82),
        'min_quality' => (int) env('VEHICLE_IMAGE_MIN_QUALITY', 68),
        'target_max_bytes' => (int) env('VEHICLE_IMAGE_TARGET_MAX_BYTES', 819200), // ~800 KB
        'thumbnail_max_long_edge' => (int) env('VEHICLE_IMAGE_THUMBNAIL_MAX_LONG_EDGE', 480),
        'thumbnail_quality' => (int) env('VEHICLE_IMAGE_THUMBNAIL_QUALITY', 75),
        'directory' => 'vehicles',
        'disk' => 'public',
        'remote_max_bytes' => (int) env('VEHICLE_IMAGE_REMOTE_MAX_BYTES', 10485760), // 10 MB
    ],

];
