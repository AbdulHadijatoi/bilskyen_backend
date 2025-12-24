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
            $table->string('registration', 20)->nullable()->after('location_id');
            $table->string('vin', 17)->nullable()->after('registration');
            
            // Add indexes for Nummerplade API lookups
            $table->index('registration');
            $table->index('vin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['registration']);
            $table->dropIndex(['vin']);
            $table->dropColumn(['registration', 'vin']);
        });
    }
};
