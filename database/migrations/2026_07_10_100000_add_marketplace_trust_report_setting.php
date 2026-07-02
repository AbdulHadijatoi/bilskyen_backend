<?php

use App\Services\PlatformSettingService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = app(PlatformSettingService::class);

        if ($settings->get('marketplace', 'trust_report_enabled') === null) {
            $settings->set('marketplace', 'trust_report_enabled', 'true');
        }
    }

    public function down(): void
    {
        app(PlatformSettingService::class)->set('marketplace', 'trust_report_enabled', null);
    }
};
