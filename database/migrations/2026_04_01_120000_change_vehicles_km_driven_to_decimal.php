<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles') || ! Schema::hasColumn('vehicles', 'km_driven')) {
            return;
        }

        DB::statement('ALTER TABLE `vehicles` MODIFY `km_driven` DECIMAL(12,3) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles') || ! Schema::hasColumn('vehicles', 'km_driven')) {
            return;
        }

        DB::statement('ALTER TABLE `vehicles` MODIFY `km_driven` INT NULL');
    }
};
