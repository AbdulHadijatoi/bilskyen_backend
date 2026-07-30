<?php

use App\Services\PlatformSettingService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $settings = app(PlatformSettingService::class);

        $existingToken = $settings->get('marketing', 'meta_catalog_feed_token', '');
        if ($existingToken === null || $existingToken === '') {
            $settings->set('marketing', 'meta_catalog_feed_token', Str::random(48));
        }

        $settings->setGroup('marketing', [
            'meta_pixel_enabled' => $settings->get('marketing', 'meta_pixel_enabled', false) ?: 'false',
            'meta_pixel_id' => (string) ($settings->get('marketing', 'meta_pixel_id', '') ?? ''),
            'meta_capi_access_token' => (string) ($settings->get('marketing', 'meta_capi_access_token', '') ?? ''),
            'meta_capi_test_event_code' => (string) ($settings->get('marketing', 'meta_capi_test_event_code', '') ?? ''),
        ]);
    }

    public function down(): void
    {
        // Settings rows are left in place; removing them could break live Pixel config.
    }
};
