<?php

use App\Services\PlatformSettingService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = app(PlatformSettingService::class);

        if ($settings->get('ai', 'deepseek_enabled') === null) {
            $settings->set('ai', 'deepseek_enabled', 'false');
        }

        if ($settings->get('ai', 'deepseek_model') === null) {
            $settings->set('ai', 'deepseek_model', config('ai.providers.deepseek.default_model', 'deepseek-v4-flash'));
        }
    }

    public function down(): void
    {
        // Settings keys are left in place; disabling is enough to roll back behaviour.
    }
};
