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
            // Remove euronorm string column if it exists
            if (Schema::hasColumn('vehicle_details', 'euronorm')) {
                $table->dropColumn('euronorm');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_details', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicle_details', 'euronorm')) {
                $table->string('euronorm', 50)->nullable()->after('seat_belt_alarms');
            }
        });
    }
};

