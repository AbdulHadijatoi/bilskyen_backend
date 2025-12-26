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
        Schema::table('vehicles', function (Blueprint $table) {
            // Add model_id (keep model_year_id)
            $table->unsignedInteger('model_id')->nullable()->after('brand_id');
            
            // Add listing_type_id
            $table->unsignedInteger('listing_type_id')->nullable()->after('vehicle_list_status_id');
            
            // Add indexes
            $table->index('model_id');
            $table->index('listing_type_id');
        });
        
        // Add foreign key constraints
        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreign('model_id')->references('id')->on('models')->nullOnDelete();
            $table->foreign('listing_type_id')->references('id')->on('listing_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['model_id']);
            $table->dropForeign(['listing_type_id']);
            
            // Drop indexes
            $table->dropIndex(['model_id']);
            $table->dropIndex(['listing_type_id']);
            
            // Drop columns
            $table->dropColumn(['model_id', 'listing_type_id']);
        });
    }
};

