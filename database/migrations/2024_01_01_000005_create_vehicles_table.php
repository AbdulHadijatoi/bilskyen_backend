<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('serial_no')->unique()->comment('Calculated serial number based on creation order');
            $table->string('registration_number', 20);
            $table->string('make', 20)->comment('Enum: VEHICLE_MAKES');
            $table->string('model', 50);
            $table->string('variant', 20);
            $table->unsignedSmallInteger('year')->comment('1885 to current year');
            $table->string('vehicle_type', 16)->comment('Enum: VEHICLE_TYPES');
            $table->string('vin', 17)->comment('Vehicle Identification Number, alphanumeric');
            $table->string('engine_number', 20)->comment('6-20 chars, alphanumeric with hyphens/slashes');
            $table->unsignedBigInteger('odometer')->comment('0 to 12,000,000,000,000');
            $table->string('status', 50)->comment('Enum: VEHICLE_STATUSES');
            $table->unsignedTinyInteger('ownership_count')->comment('1 to 20');
            $table->string('transmission_type', 50)->comment('Enum: VEHICLE_TRANSMISSION_TYPES');
            $table->string('fuel_type', 50)->comment('Enum: VEHICLE_FUEL_TYPES');
            $table->string('color', 30);
            $table->string('condition', 20)->comment('Enum: VEHICLE_CONDITIONS');
            $table->boolean('accident_history')->default(false);
            $table->json('blacklist_flags')->comment('Array of VEHICLE_BLACKLIST_TYPES');
            $table->date('inventory_date');
            $table->json('features')->comment('Array of strings, max 50 chars each, min 1 item');
            $table->json('pending_works')->comment('Array of strings, max 50 chars each');
            $table->unsignedBigInteger('listing_price')->comment('0 to 12,000,000,000,000');
            $table->json('images')->comment('Array of file URLs, 1-20 items, max 500 chars each');
            $table->text('description')->comment('Max 5000 chars');
            $table->text('remarks')->nullable()->comment('Max 3000 chars');
            $table->timestamps();

            // Indexes
            $table->index('created_at', 'idx_vehicles_created_at');
            $table->index('registration_number', 'idx_vehicles_registration_number');
            $table->index('make', 'idx_vehicles_make');
            $table->index('model', 'idx_vehicles_model');
            $table->index('status', 'idx_vehicles_status');
            $table->index('inventory_date', 'idx_vehicles_inventory_date');
            $table->index('vin', 'idx_vehicles_vin');
            $table->index(['make', 'model', 'year'], 'idx_vehicles_make_model_year');
            $table->index(['status', 'inventory_date'], 'idx_vehicles_status_inventory_date');
            $table->fullText(['registration_number', 'make', 'model'], 'idx_vehicles_search');
        });

        // Add CHECK constraints using raw SQL (not supported in SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE vehicles ADD CONSTRAINT chk_vehicles_odometer CHECK (odometer >= 0 AND odometer <= 12000000000000)');
            DB::statement('ALTER TABLE vehicles ADD CONSTRAINT chk_vehicles_ownership_count CHECK (ownership_count >= 1 AND ownership_count <= 20)');
            DB::statement('ALTER TABLE vehicles ADD CONSTRAINT chk_vehicles_listing_price CHECK (listing_price >= 0 AND listing_price <= 12000000000000)');
            
            // Add REGEXP constraints using raw SQL (MySQL 8.0.4+)
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE vehicles ADD CONSTRAINT chk_vehicles_vin_format CHECK (vin REGEXP \'^[A-HJ-NPR-Z0-9]{17}$\')');
                DB::statement('ALTER TABLE vehicles ADD CONSTRAINT chk_vehicles_engine_number_format CHECK (engine_number REGEXP \'^[A-Z0-9\\-\\/]{6,20}$\')');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};

