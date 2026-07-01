<?php

use App\Services\PlatformSettingService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $service = app(PlatformSettingService::class);
        if ($service->get('payment', 'instant_subscription_checkout') === null) {
            $service->set('payment', 'instant_subscription_checkout', 'true');
        }
    }

    public function down(): void
    {
        // non-destructive
    }
};
