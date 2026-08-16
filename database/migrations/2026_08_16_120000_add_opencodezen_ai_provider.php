<?php

use App\Services\PlatformSettingService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = app(PlatformSettingService::class);

        if ($settings->get('ai', 'opencodezen_enabled') === null) {
            $settings->set('ai', 'opencodezen_enabled', 'false');
        }

        if ($settings->get('ai', 'opencodezen_model') === null) {
            $settings->set('ai', 'opencodezen_model', config('ai.providers.opencodezen.default_model', 'deepseek-v4-flash-free'));
        }
    }

    public function down(): void
    {
        // Settings keys are left in place; disabling is enough to roll back behaviour.
    }
};
