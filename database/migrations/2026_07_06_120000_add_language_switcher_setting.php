<?php

use App\Services\PlatformSettingService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = app(PlatformSettingService::class);

        if ($settings->get('general', 'language_switcher_enabled') === null) {
            $settings->set('general', 'language_switcher_enabled', 'true');
        }
    }

    public function down(): void
    {
        app(PlatformSettingService::class)->set('general', 'language_switcher_enabled', null);
    }
};
