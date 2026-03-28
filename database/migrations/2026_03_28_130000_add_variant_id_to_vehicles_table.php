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
            if (! Schema::hasColumn('vehicles', 'variant_id')) {
                $table->unsignedBigInteger('variant_id')->nullable()->after('model_id');
            }
        });

        if (Schema::hasTable('dmr_fact_vehicles')) {
            DB::statement('
                UPDATE vehicles v
                INNER JOIN dmr_fact_vehicles dfv ON dfv.id = v.dmr_fact_vehicle_id
                SET v.variant_id = COALESCE(v.variant_id, dfv.variant_id)
                WHERE dfv.variant_id IS NOT NULL
            ');
        }

        if (Schema::hasColumn('vehicles', 'variant_id')) {
            DB::statement('ALTER TABLE `vehicles` MODIFY `variant_id` BIGINT NULL');
        }

        $this->addIndexIfMissing('vehicles', 'vehicles_variant_id_index', fn (Blueprint $table) => $table->index('variant_id'));

        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasTable('dmr_variants')) {
                try {
                    $table->foreign('variant_id')->references('id')->on('dmr_variants')->nullOnDelete();
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
                $table->dropForeign(['variant_id']);
            } catch (\Throwable) {
            }
        });

        if (Schema::hasColumn('vehicles', 'variant_id')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropColumn('variant_id');
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
