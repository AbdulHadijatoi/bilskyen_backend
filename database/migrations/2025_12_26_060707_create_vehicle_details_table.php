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
        Schema::dropIfExists('vehicle_details');
        
        Schema::create('vehicle_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->unique()->constrained('vehicles')->cascadeOnDelete();
            
            // Basic details
            $table->text('description')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            
            // Registration and identification
            $table->string('vin_location', 255)->nullable();
            $table->unsignedInteger('type_id')->nullable();
            $table->string('version', 100)->nullable();
            $table->string('type_name', 255)->nullable();
            $table->string('registration_status', 100)->nullable();
            $table->date('registration_status_updated_date')->nullable();
            $table->date('expire_date')->nullable();
            $table->date('status_updated_date')->nullable();
            
            // Vehicle specifications
            $table->string('model_year', 50)->nullable();
            $table->integer('total_weight')->nullable();
            $table->integer('vehicle_weight')->nullable();
            $table->integer('technical_total_weight')->nullable();
            $table->integer('coupling')->nullable();
            $table->integer('towing_weight_brakes')->nullable();
            $table->integer('minimum_weight')->nullable();
            $table->integer('gross_combination_weight')->nullable();
            $table->decimal('fuel_efficiency', 8, 2)->nullable();
            $table->integer('engine_displacement')->nullable();
            $table->integer('engine_cylinders')->nullable();
            $table->string('engine_code', 100)->nullable();
            $table->string('category', 100)->nullable();
            $table->date('last_inspection_date')->nullable();
            $table->string('last_inspection_result', 100)->nullable();
            $table->integer('last_inspection_odometer')->nullable();
            $table->string('type_approval_code', 100)->nullable();
            $table->integer('top_speed')->nullable();
            $table->integer('doors')->nullable();
            $table->integer('minimum_seats')->nullable();
            $table->integer('maximum_seats')->nullable();
            $table->integer('wheels')->nullable();
            $table->text('extra_equipment')->nullable();
            $table->integer('axles')->nullable();
            $table->integer('drive_axles')->nullable();
            $table->integer('wheelbase')->nullable();
            $table->date('leasing_period_start')->nullable();
            $table->date('leasing_period_end')->nullable();
            
            // Additional details
            $table->unsignedInteger('use_id')->nullable();
            $table->unsignedInteger('color_id')->nullable();
            $table->unsignedInteger('body_type_id')->nullable();
            $table->text('dispensations')->nullable();
            $table->text('permits')->nullable();
            $table->boolean('ncap_five')->nullable();
            $table->integer('airbags')->nullable();
            $table->integer('integrated_child_seats')->nullable();
            $table->integer('seat_belt_alarms')->nullable();
            $table->string('euronorm', 50)->nullable();
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('type_id')->references('id')->on('types')->nullOnDelete();
            $table->foreign('use_id')->references('id')->on('uses')->nullOnDelete();
            $table->foreign('color_id')->references('id')->on('colors')->nullOnDelete();
            $table->foreign('body_type_id')->references('id')->on('body_types')->nullOnDelete();
            
            // Indexes
            $table->index('vehicle_id');
            $table->index('type_id');
            $table->index('use_id');
            $table->index('color_id');
            $table->index('body_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_details');
    }
};
