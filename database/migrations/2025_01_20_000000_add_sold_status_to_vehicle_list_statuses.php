<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Constants\VehicleListStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert "Sold" status if it doesn't exist
        DB::table('vehicle_list_statuses')->updateOrInsert(
            ['id' => VehicleListStatus::SOLD],
            ['name' => 'Sold']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally remove the "Sold" status
        // Note: This might fail if vehicles are using this status
        // DB::table('vehicle_list_statuses')->where('id', VehicleListStatus::SOLD)->delete();
    }
};
