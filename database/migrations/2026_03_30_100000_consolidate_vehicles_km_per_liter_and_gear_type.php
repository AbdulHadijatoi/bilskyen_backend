<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Single source of truth: {@see km_per_liter} (not fuel_efficiency) and {@see gear_type_id} (not transmission_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        if (Schema::hasColumn('vehicles', 'fuel_efficiency') && Schema::hasColumn('vehicles', 'km_per_liter')) {
            DB::statement('
                UPDATE vehicles
                SET km_per_liter = COALESCE(km_per_liter, fuel_efficiency)
                WHERE fuel_efficiency IS NOT NULL
            ');
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropColumn('fuel_efficiency');
            });
        }

        if (Schema::hasColumn('vehicles', 'transmission_id') && Schema::hasColumn('vehicles', 'gear_type_id')) {
            if (Schema::hasTable('transmissions') && Schema::hasTable('gear_types')) {
                DB::statement('
                    UPDATE vehicles v
                    INNER JOIN transmissions t ON t.id = v.transmission_id
                    INNER JOIN gear_types g ON LOWER(TRIM(g.name)) = LOWER(TRIM(t.name))
                    SET v.gear_type_id = COALESCE(v.gear_type_id, g.id)
                    WHERE v.transmission_id IS NOT NULL
                ');
            }
            DB::statement('
                UPDATE vehicles
                SET gear_type_id = COALESCE(gear_type_id, transmission_id)
                WHERE transmission_id IS NOT NULL AND gear_type_id IS NULL
            ');

            try {
                Schema::table('vehicles', function (Blueprint $table) {
                    $table->dropForeign(['transmission_id']);
                });
            } catch (\Throwable) {
            }
            Schema::table('vehicles', function (Blueprint $table) {
                if (Schema::hasColumn('vehicles', 'transmission_id')) {
                    $table->dropColumn('transmission_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        if (! Schema::hasColumn('vehicles', 'fuel_efficiency') && Schema::hasColumn('vehicles', 'km_per_liter')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->decimal('fuel_efficiency', 8, 2)->nullable()->after('gear_type_id');
            });
            DB::statement('UPDATE vehicles SET fuel_efficiency = km_per_liter WHERE km_per_liter IS NOT NULL');
        }

        if (! Schema::hasColumn('vehicles', 'transmission_id')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->unsignedInteger('transmission_id')->nullable()->after('body_type_id');
            });
            if (Schema::hasTable('transmissions')) {
                try {
                    Schema::table('vehicles', function (Blueprint $table) {
                        $table->foreign('transmission_id')->references('id')->on('transmissions')->nullOnDelete();
                    });
                } catch (\Throwable) {
                }
            }
            DB::statement('UPDATE vehicles SET transmission_id = gear_type_id WHERE gear_type_id IS NOT NULL');
        }
    }
};
