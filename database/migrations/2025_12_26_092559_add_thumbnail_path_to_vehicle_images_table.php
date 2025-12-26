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
        if (!Schema::hasColumn('vehicle_images', 'thumbnail_path')) {
            Schema::table('vehicle_images', function (Blueprint $table) {
                $table->string('thumbnail_path', 255)->nullable()->after('image_path');
            });
        }
        
        // Try to add index, ignore if it already exists
        try {
            Schema::table('vehicle_images', function (Blueprint $table) {
                $table->index('vehicle_id');
            });
        } catch (\Exception $e) {
            // Index might already exist, ignore
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_images', function (Blueprint $table) {
            $table->dropColumn('thumbnail_path');
        });
    }
};
