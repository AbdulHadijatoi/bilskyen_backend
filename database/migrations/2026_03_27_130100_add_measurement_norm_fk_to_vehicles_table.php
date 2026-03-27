<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles') || ! Schema::hasTable('measurement_norms')) {
            return;
        }

        if (! Schema::hasColumn('vehicles', 'measurement_norm_id')) {
            return;
        }

        // vehicle.measurement_norm_id was backfilled from DMR bridge IDs; align local norms first.
        if (Schema::hasTable('dmr_measurement_norms')) {
            DB::statement('
                INSERT INTO measurement_norms (id, name)
                SELECT
                    d.id,
                    LEFT(CONCAT(CAST(d.id AS CHAR), " ", COALESCE(d.name, "")), 100) AS name
                FROM dmr_measurement_norms d
                ON DUPLICATE KEY UPDATE name = VALUES(name)
            ');
        }

        DB::statement('
            UPDATE vehicles v
            LEFT JOIN measurement_norms m ON m.id = v.measurement_norm_id
            SET v.measurement_norm_id = NULL
            WHERE v.measurement_norm_id IS NOT NULL AND m.id IS NULL
        ');

        Schema::table('vehicles', function (Blueprint $table) {
            try {
                $table->foreign('measurement_norm_id')
                    ->references('id')
                    ->on('measurement_norms')
                    ->nullOnDelete();
            } catch (\Throwable) {
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles') || ! Schema::hasColumn('vehicles', 'measurement_norm_id')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            try {
                $table->dropForeign(['measurement_norm_id']);
            } catch (\Throwable) {
            }
        });
    }
};
