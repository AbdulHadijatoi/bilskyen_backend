<?php

use App\Services\PlatformSettingService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dealers') && ! Schema::hasColumn('dealers', 'finance_calculator_enabled')) {
            Schema::table('dealers', function (Blueprint $table) {
                $table->boolean('finance_calculator_enabled')->nullable()->after('finance_partner_url');
            });
        }

        if (class_exists(PlatformSettingService::class)) {
            $settings = app(PlatformSettingService::class);
            if ($settings->get('finance', 'calculator_enabled') === null) {
                $settings->set('finance', 'calculator_enabled', 'true');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dealers') && Schema::hasColumn('dealers', 'finance_calculator_enabled')) {
            Schema::table('dealers', function (Blueprint $table) {
                $table->dropColumn('finance_calculator_enabled');
            });
        }

        if (class_exists(PlatformSettingService::class)) {
            app(PlatformSettingService::class)->set('finance', 'calculator_enabled', null);
        }
    }
};
