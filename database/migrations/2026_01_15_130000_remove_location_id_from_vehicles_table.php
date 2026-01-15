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
            // Drop the foreign key constraint first
            $table->dropForeign(['location_id']);
            // Drop the column
            $table->dropColumn('location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('category_id');
            $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
        });
    }
};
