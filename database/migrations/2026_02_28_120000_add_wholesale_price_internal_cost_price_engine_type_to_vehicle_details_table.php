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
            $table->string('engine_type', 100)->nullable()->after('engine_code');
            $table->decimal('wholesale_price', 12, 2)->nullable()->after('is_factory_new');
            $table->decimal('internal_cost_price', 12, 2)->nullable()->after('wholesale_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_details', function (Blueprint $table) {
            $table->dropColumn(['wholesale_price', 'internal_cost_price', 'engine_type']);
        });
    }
};
