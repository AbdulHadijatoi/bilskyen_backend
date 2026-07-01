<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'fuel_type_id')) {
                $after = Schema::hasColumn('vehicles', 'variant_id') ? 'variant_id' : 'model_id';
                $table->unsignedBigInteger('fuel_type_id')->nullable()->after($after);
            }
        });

        if (Schema::hasTable('dmr_fact_vehicles') && Schema::hasTable('dmr_bridge_vehicle_drivmiddel')) {
            // Primary drivmiddel line: drivmiddel_primaer first, then line_order, then id
            DB::statement('
                UPDATE vehicles v
                INNER JOIN dmr_fact_vehicles dfv ON dfv.id = v.dmr_fact_vehicle_id
                SET v.fuel_type_id = (
                    SELECT b.drive_energy_id
                    FROM dmr_bridge_vehicle_drivmiddel b
                    WHERE b.vehicle_id = dfv.id
                      AND b.drive_energy_id IS NOT NULL
                    ORDER BY b.drivmiddel_primaer DESC, b.line_order ASC, b.id ASC
                    LIMIT 1
                )
                WHERE v.fuel_type_id IS NULL
            ');
        }

        if (Schema::hasColumn('vehicles', 'fuel_type_id')) {
            DB::statement('ALTER TABLE `vehicles` MODIFY `fuel_type_id` BIGINT NULL');
        }

        $this->addIndexIfMissing('vehicles', 'vehicles_fuel_type_id_index', fn (Blueprint $table) => $table->index('fuel_type_id'));

        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasTable('dmr_drive_energies')) {
                try {
                    $table->foreign('fuel_type_id')->references('id')->on('dmr_drive_energies')->nullOnDelete();
                } catch (\Throwable) {
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            try {
                $table->dropForeign(['fuel_type_id']);
            } catch (\Throwable) {
            }
        });

        if (Schema::hasColumn('vehicles', 'fuel_type_id')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropColumn('fuel_type_id');
            });
        }
    }

    private function addIndexIfMissing(string $table, string $indexName, callable $callback): void
    {
        $exists = DB::select(
            'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [DB::getDatabaseName(), $table, $indexName]
        );

        if (! empty($exists)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($callback) {
            $callback($table);
        });
    }
};
