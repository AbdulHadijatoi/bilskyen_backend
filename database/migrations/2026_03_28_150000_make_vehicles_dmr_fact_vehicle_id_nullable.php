<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles') || ! Schema::hasColumn('vehicles', 'dmr_fact_vehicle_id')) {
            return;
        }

        $this->dropForeignKeysOnColumn('vehicles', 'dmr_fact_vehicle_id');

        DB::statement('ALTER TABLE `vehicles` MODIFY `dmr_fact_vehicle_id` BIGINT NULL');

        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasTable('dmr_fact_vehicles')) {
                try {
                    $table->foreign('dmr_fact_vehicle_id')->references('id')->on('dmr_fact_vehicles')->restrictOnDelete();
                } catch (\Throwable) {
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles') || ! Schema::hasColumn('vehicles', 'dmr_fact_vehicle_id')) {
            return;
        }

        if (DB::table('vehicles')->whereNull('dmr_fact_vehicle_id')->exists()) {
            throw new \RuntimeException('Cannot revert: vehicles rows exist with dmr_fact_vehicle_id NULL.');
        }

        $this->dropForeignKeysOnColumn('vehicles', 'dmr_fact_vehicle_id');

        DB::statement('ALTER TABLE `vehicles` MODIFY `dmr_fact_vehicle_id` BIGINT NOT NULL');

        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasTable('dmr_fact_vehicles')) {
                try {
                    $table->foreign('dmr_fact_vehicle_id')->references('id')->on('dmr_fact_vehicles')->restrictOnDelete();
                } catch (\Throwable) {
                }
            }
        });
    }

    private function dropForeignKeysOnColumn(string $table, string $column): void
    {
        $db = DB::getDatabaseName();
        $fks = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$db, $table, $column]
        );
        foreach ($fks as $fk) {
            try {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            } catch (\Throwable) {
            }
        }
    }
};
