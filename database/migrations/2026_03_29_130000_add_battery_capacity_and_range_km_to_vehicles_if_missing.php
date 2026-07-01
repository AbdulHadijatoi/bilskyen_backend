<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'battery_capacity')) {
                $table->unsignedInteger('battery_capacity')->nullable()->after('towing_weight');
            }
        });

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'range_km')) {
                $table->unsignedInteger('range_km')->nullable()->after('battery_capacity');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'range_km')) {
                $table->dropColumn('range_km');
            }
            if (Schema::hasColumn('vehicles', 'battery_capacity')) {
                $table->dropColumn('battery_capacity');
            }
        });
    }
};
