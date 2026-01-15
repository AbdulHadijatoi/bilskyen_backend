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
        // Use raw SQL for MySQL compatibility when changing column types
        DB::statement('ALTER TABLE vehicle_details MODIFY COLUMN wheels TEXT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert text back to integer - only numeric values will be preserved
        DB::statement('UPDATE vehicle_details SET wheels = CAST(wheels AS UNSIGNED) WHERE wheels REGEXP "^[0-9]+$"');
        DB::statement('UPDATE vehicle_details SET wheels = NULL WHERE wheels NOT REGEXP "^[0-9]+$"');
        DB::statement('ALTER TABLE vehicle_details MODIFY COLUMN wheels INT NULL');
    }
};
