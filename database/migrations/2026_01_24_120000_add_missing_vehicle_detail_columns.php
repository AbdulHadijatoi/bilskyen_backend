<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vehicle_details', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicle_details', 'production_date')) {
                $table->date('production_date')->nullable()->after('leasing_period_end');
            }
            if (!Schema::hasColumn('vehicle_details', 'cover_image_index')) {
                $table->unsignedInteger('cover_image_index')->nullable()->after('production_date');
            }
            if (!Schema::hasColumn('vehicle_details', 'fuel_consumption_wltp')) {
                $table->decimal('fuel_consumption_wltp', 8, 2)->nullable()->after('cover_image_index');
            }
            if (!Schema::hasColumn('vehicle_details', 'fuel_consumption_nedc')) {
                $table->decimal('fuel_consumption_nedc', 8, 2)->nullable()->after('fuel_consumption_wltp');
            }
            if (!Schema::hasColumn('vehicle_details', 'co2_emissions')) {
                $table->integer('co2_emissions')->nullable()->after('fuel_consumption_nedc');
            }
            if (!Schema::hasColumn('vehicle_details', 'is_import')) {
                $table->boolean('is_import')->nullable()->default(false)->after('co2_emissions');
            }
            if (!Schema::hasColumn('vehicle_details', 'is_factory_new')) {
                $table->boolean('is_factory_new')->nullable()->default(false)->after('is_import');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_details', function (Blueprint $table) {
            if (Schema::hasColumn('vehicle_details', 'is_factory_new')) {
                $table->dropColumn('is_factory_new');
            }
            if (Schema::hasColumn('vehicle_details', 'is_import')) {
                $table->dropColumn('is_import');
            }
            if (Schema::hasColumn('vehicle_details', 'co2_emissions')) {
                $table->dropColumn('co2_emissions');
            }
            if (Schema::hasColumn('vehicle_details', 'fuel_consumption_nedc')) {
                $table->dropColumn('fuel_consumption_nedc');
            }
            if (Schema::hasColumn('vehicle_details', 'fuel_consumption_wltp')) {
                $table->dropColumn('fuel_consumption_wltp');
            }
            if (Schema::hasColumn('vehicle_details', 'cover_image_index')) {
                $table->dropColumn('cover_image_index');
            }
            if (Schema::hasColumn('vehicle_details', 'production_date')) {
                $table->dropColumn('production_date');
            }
        });
    }
};
