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
        // First, update all existing null values to 1
        DB::table('vehicle_details')
            ->whereNull('condition_id')
            ->update(['condition_id' => 2]);
        
        // Then, set the default value to 1
        Schema::table('vehicle_details', function (Blueprint $table) {
            $table->unsignedInteger('condition_id')->default(2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_details', function (Blueprint $table) {
            $table->unsignedInteger('condition_id')->nullable()->change();
        });
    }
};
