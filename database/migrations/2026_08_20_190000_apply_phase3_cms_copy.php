<?php

use App\Services\CmsPhase3CopyService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! app()->bound(CmsPhase3CopyService::class) && ! class_exists(CmsPhase3CopyService::class)) {
            return;
        }

        app(CmsPhase3CopyService::class)->apply();
    }

    public function down(): void
    {
        // Copy is idempotent CMS content; previous HTML is not restored.
    }
};
