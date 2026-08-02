<?php

use App\Services\PlatformSettingService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = app(PlatformSettingService::class);

        if ($settings->get('ai', 'ollama_enabled') === null) {
            $settings->set('ai', 'ollama_enabled', 'false');
        }

        if ($settings->get('ai', 'ollama_model') === null) {
            $settings->set('ai', 'ollama_model', config('ai.providers.ollama.default_model', 'llama3.2'));
        }

        if ($settings->get('ai', 'ollama_base_url') === null) {
            $settings->set('ai', 'ollama_base_url', config('ai.providers.ollama.base_url', 'http://127.0.0.1:11434'));
        }
    }

    public function down(): void
    {
        // Settings keys are left in place; disabling is enough to roll back behaviour.
    }
};
