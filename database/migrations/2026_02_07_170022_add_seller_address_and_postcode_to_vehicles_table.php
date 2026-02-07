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
        // Add columns to vehicles table
        Schema::table('vehicles', function (Blueprint $table) {
            $table->text('seller_address')->nullable()->after('published_at');
            $table->string('seller_postcode', 10)->nullable()->after('seller_address');
        });

        // Migrate existing data from vehicle_details to vehicles
        DB::statement("
            UPDATE vehicles v
            INNER JOIN vehicle_details vd ON v.id = vd.vehicle_id
            SET v.seller_address = vd.seller_address,
                v.seller_postcode = vd.seller_postcode
            WHERE vd.seller_address IS NOT NULL OR vd.seller_postcode IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['seller_address', 'seller_postcode']);
        });
    }
};
