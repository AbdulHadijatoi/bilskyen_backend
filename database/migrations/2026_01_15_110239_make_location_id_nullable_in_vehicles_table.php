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
            // Drop the existing foreign key constraint
            $table->dropForeign(['location_id']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            // Modify the column to be nullable
            $table->foreignId('location_id')->nullable()->change();
        });

        Schema::table('vehicles', function (Blueprint $table) {
            // Re-add the foreign key constraint with nullOnDelete since it's nullable
            $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['location_id']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            // Make the column non-nullable again
            $table->foreignId('location_id')->nullable(false)->change();
        });

        Schema::table('vehicles', function (Blueprint $table) {
            // Re-add the foreign key constraint with cascadeOnDelete
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
        });
    }
};
