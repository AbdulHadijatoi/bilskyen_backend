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
            $table->string('seller_phone', 30)->nullable()->after('sales_type_id');
            $table->text('seller_address')->nullable()->after('seller_phone');
            $table->string('seller_postcode', 10)->nullable()->after('seller_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_details', function (Blueprint $table) {
            $table->dropColumn(['seller_phone', 'seller_address', 'seller_postcode']);
        });
    }
};
