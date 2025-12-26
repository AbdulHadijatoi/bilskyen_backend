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
            // Add new foreign key fields
            $table->unsignedInteger('price_type_id')->nullable()->after('euronorm');
            $table->unsignedInteger('condition_id')->nullable()->after('price_type_id');
            $table->unsignedInteger('gear_type_id')->nullable()->after('condition_id');
            $table->unsignedInteger('sales_type_id')->nullable()->after('gear_type_id');
            
            // Remove model_year field
            $table->dropColumn('model_year');
            
            // Add indexes for new foreign keys
            $table->index('price_type_id');
            $table->index('condition_id');
            $table->index('gear_type_id');
            $table->index('sales_type_id');
        });
        
        // Add foreign key constraints
        Schema::table('vehicle_details', function (Blueprint $table) {
            $table->foreign('price_type_id')->references('id')->on('price_types')->nullOnDelete();
            $table->foreign('condition_id')->references('id')->on('conditions')->nullOnDelete();
            $table->foreign('gear_type_id')->references('id')->on('gear_types')->nullOnDelete();
            $table->foreign('sales_type_id')->references('id')->on('sales_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_details', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['price_type_id']);
            $table->dropForeign(['condition_id']);
            $table->dropForeign(['gear_type_id']);
            $table->dropForeign(['sales_type_id']);
            
            // Drop indexes
            $table->dropIndex(['price_type_id']);
            $table->dropIndex(['condition_id']);
            $table->dropIndex(['gear_type_id']);
            $table->dropIndex(['sales_type_id']);
            
            // Drop columns
            $table->dropColumn(['price_type_id', 'condition_id', 'gear_type_id', 'sales_type_id']);
            
            // Restore model_year field
            $table->string('model_year', 50)->nullable()->after('status_updated_date');
        });
    }
};

