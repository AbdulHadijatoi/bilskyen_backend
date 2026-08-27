<?php

use App\Services\PlatformSettingService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = app(PlatformSettingService::class);
        $existingId = trim((string) ($settings->get('marketing', 'microsoft_clarity_project_id', '') ?? ''));

        $settings->setGroup('marketing', [
            'microsoft_clarity_enabled' => 'true',
            'microsoft_clarity_project_id' => $existingId !== '' ? $existingId : 'y8l8s0praw',
        ]);
    }

    public function down(): void
    {
        app(PlatformSettingService::class)->setGroup('marketing', [
            'microsoft_clarity_enabled' => 'false',
        ]);
    }
};
