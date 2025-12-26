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
        Schema::dropIfExists('vehicles');
        
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            
            // Basic listing fields
            $table->string('title', 255);
            $table->string('registration', 20)->nullable();
            $table->string('vin', 17)->nullable();

            // Foreign keys
            $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('category_id')->nullable();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->unsignedInteger('brand_id')->nullable();
            $table->unsignedInteger('model_year_id')->nullable();
            $table->unsignedInteger('fuel_type_id');
            $table->unsignedInteger('vehicle_list_status_id');
            
            // Vehicle specifications
            $table->integer('km_driven')->nullable();
            $table->integer('price');
            $table->integer('mileage')->nullable();
            $table->integer('battery_capacity')->nullable();
            $table->integer('engine_power')->nullable();
            $table->integer('towing_weight')->nullable();
            $table->integer('ownership_tax')->nullable();
            $table->date('first_registration_date')->nullable();
            
            // Status and publishing
            $table->timestamp('published_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints for integer fields
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->foreign('brand_id')->references('id')->on('brands')->nullOnDelete();
            $table->foreign('model_year_id')->references('id')->on('model_years')->nullOnDelete();
            $table->foreign('fuel_type_id')->references('id')->on('fuel_types')->cascadeOnDelete();
            $table->foreign('vehicle_list_status_id')->references('id')->on('vehicle_list_statuses')->cascadeOnDelete();
            
            // Indexes for search/filtering
            $table->index('registration');
            $table->index('vin');
            $table->index(['vehicle_list_status_id', 'published_at']);
            $table->index(['vehicle_list_status_id', 'price']);
            $table->index(['vehicle_list_status_id', 'mileage']);
            $table->index(['location_id', 'price']);
            $table->index('category_id');
            $table->index('brand_id');
            $table->index('model_year_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
