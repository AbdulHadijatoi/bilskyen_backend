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
            // Add variant_id
            if (!Schema::hasColumn('vehicle_details', 'variant_id')) {
                $table->unsignedInteger('variant_id')->nullable()->after('body_type_id');
                $table->index('variant_id');
            }
            
            // Add euronom_id
            if (!Schema::hasColumn('vehicle_details', 'euronom_id')) {
                $table->unsignedInteger('euronom_id')->nullable()->after('seat_belt_alarms');
                $table->index('euronom_id');
            }
            
            // Add servicebog
            if (!Schema::hasColumn('vehicle_details', 'servicebog')) {
                $table->enum('servicebog', ['Yes', 'No', 'Default'])->nullable()->after('euronom_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_details', function (Blueprint $table) {
            if (Schema::hasColumn('vehicle_details', 'variant_id')) {
                $table->dropIndex(['variant_id']);
                $table->dropColumn('variant_id');
            }
            if (Schema::hasColumn('vehicle_details', 'euronom_id')) {
                $table->dropIndex(['euronom_id']);
                $table->dropColumn('euronom_id');
            }
            if (Schema::hasColumn('vehicle_details', 'servicebog')) {
                $table->dropColumn('servicebog');
            }
        });
    }
};

