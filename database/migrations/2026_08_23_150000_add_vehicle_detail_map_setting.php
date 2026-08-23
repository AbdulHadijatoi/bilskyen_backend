<?php

use App\Services\PlatformSettingService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = app(PlatformSettingService::class);

        if ($settings->get('marketplace', 'vehicle_detail_map_enabled') === null) {
            $settings->set('marketplace', 'vehicle_detail_map_enabled', 'true');
        }
    }

    public function down(): void
    {
        app(PlatformSettingService::class)->set('marketplace', 'vehicle_detail_map_enabled', null);
    }
};
