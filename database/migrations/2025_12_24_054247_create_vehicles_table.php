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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();

            $table->string('title', 255);
            $table->text('description');

            $table->integer('price');
            $table->integer('mileage');
            $table->unsignedSmallInteger('year');

            $table->unsignedInteger('fuel_type_id');
            $table->unsignedInteger('transmission_id');
            $table->string('body_type', 50);

            // Searchable extracted flags
            $table->boolean('has_carplay')->default(false);
            $table->boolean('has_adaptive_cruise')->default(false);
            $table->boolean('is_electric')->default(false);

            $table->json('specs')->nullable();
            $table->json('equipment')->nullable();

            $table->unsignedInteger('vehicle_list_status_id');
            $table->timestamp('published_at')->nullable();

            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['vehicle_list_status_id', 'published_at']);
            $table->index(['vehicle_list_status_id', 'price']);
            $table->index(['vehicle_list_status_id', 'mileage']);
            $table->index(['vehicle_list_status_id', 'year']);
            $table->index(['location_id', 'price']);
        });

        // Add foreign key constraints for integer fields
        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreign('fuel_type_id')->references('id')->on('fuel_types')->cascadeOnDelete();
            $table->foreign('transmission_id')->references('id')->on('transmissions')->cascadeOnDelete();
            $table->foreign('vehicle_list_status_id')->references('id')->on('vehicle_list_statuses')->cascadeOnDelete();
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
