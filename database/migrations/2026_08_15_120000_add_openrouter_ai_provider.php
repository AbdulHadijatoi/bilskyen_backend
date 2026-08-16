<?php

use App\Services\PlatformSettingService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = app(PlatformSettingService::class);

        if ($settings->get('ai', 'openrouter_enabled') === null) {
            $settings->set('ai', 'openrouter_enabled', 'false');
        }

        if ($settings->get('ai', 'openrouter_model') === null) {
            $settings->set('ai', 'openrouter_model', config('ai.providers.openrouter.default_model', 'openrouter/free'));
        }
    }

    public function down(): void
    {
        // Settings keys are left in place; disabling is enough to roll back behaviour.
    }
};
