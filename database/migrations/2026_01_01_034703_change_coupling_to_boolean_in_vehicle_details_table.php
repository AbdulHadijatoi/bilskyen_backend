<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vehicle_details', function (Blueprint $table) {
            // First, convert existing integer values to boolean
            // 0 or null -> false, any other number -> true
            DB::statement('UPDATE vehicle_details SET coupling = CASE WHEN coupling IS NULL OR coupling = 0 THEN 0 ELSE 1 END');
            
            // Change column type from integer to boolean
            $table->boolean('coupling')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_details', function (Blueprint $table) {
            // Convert boolean back to integer
            DB::statement('UPDATE vehicle_details SET coupling = CASE WHEN coupling = 1 THEN 1 ELSE 0 END');
            
            $table->integer('coupling')->nullable()->change();
        });
    }
};
