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
        Schema::table('enquiries', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['vehicle_id']);
            
            // Make vehicle_id nullable
            $table->unsignedBigInteger('vehicle_id')->nullable()->change();
            
            // Re-add the foreign key constraint with nullOnDelete
            $table->foreign('vehicle_id')
                  ->references('id')
                  ->on('vehicles')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['vehicle_id']);
            
            // Make vehicle_id not nullable
            $table->unsignedBigInteger('vehicle_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint
            $table->foreign('vehicle_id')
                  ->references('id')
                  ->on('vehicles')
                  ->nullOnDelete();
        });
    }
};
