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
            if (! Schema::hasColumn('vehicles', 'body_type_id')) {
                $table->unsignedBigInteger('body_type_id')->nullable()->after('measurement_norm_id');
            }
            if (! Schema::hasColumn('vehicles', 'colour_id')) {
                $table->unsignedBigInteger('colour_id')->nullable()->after('body_type_id');
            }
            if (! Schema::hasColumn('vehicles', 'emission_norm_id')) {
                $table->unsignedBigInteger('emission_norm_id')->nullable()->after('colour_id');
            }
            if (! Schema::hasColumn('vehicles', 'model_id')) {
                $table->unsignedBigInteger('model_id')->nullable()->after('emission_norm_id');
            }
            if (! Schema::hasColumn('vehicles', 'vehicle_use_id')) {
                $table->unsignedBigInteger('vehicle_use_id')->nullable()->after('model_id');
            }
            if (! Schema::hasColumn('vehicles', 'brand_id')) {
                $table->unsignedBigInteger('brand_id')->nullable()->after('vehicle_use_id');
            }
        });

        if (Schema::hasTable('dmr_fact_vehicles')) {
            DB::statement('
                UPDATE vehicles v
                INNER JOIN dmr_fact_vehicles dfv ON dfv.id = v.dmr_fact_vehicle_id
                LEFT JOIN dmr_variants dv ON dv.id = dfv.variant_id
                LEFT JOIN dmr_models dm ON dm.id = dv.model_id
                SET
                    v.body_type_id = COALESCE(v.body_type_id, dfv.body_type_id),
                    v.colour_id = COALESCE(v.colour_id, dfv.colour_id),
                    v.emission_norm_id = COALESCE(v.emission_norm_id, dfv.emission_norm_id),
                    v.model_id = COALESCE(v.model_id, dv.model_id),
                    v.vehicle_use_id = COALESCE(v.vehicle_use_id, dfv.vehicle_use_id),
                    v.brand_id = COALESCE(v.brand_id, dm.brand_id)
            ');
        }

        // DMR PKs are signed BIGINT. Align local FK columns before adding constraints.
        foreach (['body_type_id', 'colour_id', 'emission_norm_id', 'model_id', 'vehicle_use_id', 'brand_id'] as $column) {
            if (Schema::hasColumn('vehicles', $column)) {
                DB::statement("ALTER TABLE `vehicles` MODIFY `{$column}` BIGINT NULL");
            }
        }

        $this->addIndexIfMissing('vehicles', 'vehicles_body_type_id_index', fn (Blueprint $table) => $table->index('body_type_id'));
        $this->addIndexIfMissing('vehicles', 'vehicles_colour_id_index', fn (Blueprint $table) => $table->index('colour_id'));
        $this->addIndexIfMissing('vehicles', 'vehicles_emission_norm_id_index', fn (Blueprint $table) => $table->index('emission_norm_id'));
        $this->addIndexIfMissing('vehicles', 'vehicles_model_id_index', fn (Blueprint $table) => $table->index('model_id'));
        $this->addIndexIfMissing('vehicles', 'vehicles_vehicle_use_id_index', fn (Blueprint $table) => $table->index('vehicle_use_id'));
        $this->addIndexIfMissing('vehicles', 'vehicles_brand_id_index', fn (Blueprint $table) => $table->index('brand_id'));

        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasTable('dmr_body_types')) {
                try {
                    $table->foreign('body_type_id')->references('id')->on('dmr_body_types')->nullOnDelete();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasTable('dmr_colours')) {
                try {
                    $table->foreign('colour_id')->references('id')->on('dmr_colours')->nullOnDelete();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasTable('dmr_emission_norms')) {
                try {
                    $table->foreign('emission_norm_id')->references('id')->on('dmr_emission_norms')->nullOnDelete();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasTable('dmr_models')) {
                try {
                    $table->foreign('model_id')->references('id')->on('dmr_models')->nullOnDelete();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasTable('dmr_vehicle_uses')) {
                try {
                    $table->foreign('vehicle_use_id')->references('id')->on('dmr_vehicle_uses')->nullOnDelete();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasTable('dmr_brands')) {
                try {
                    $table->foreign('brand_id')->references('id')->on('dmr_brands')->nullOnDelete();
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
            foreach (['body_type_id', 'colour_id', 'emission_norm_id', 'model_id', 'vehicle_use_id', 'brand_id'] as $column) {
                try {
                    $table->dropForeign([$column]);
                } catch (\Throwable) {
                }
            }
        });

        Schema::table('vehicles', function (Blueprint $table) {
            foreach (['body_type_id', 'colour_id', 'emission_norm_id', 'model_id', 'vehicle_use_id', 'brand_id'] as $column) {
                if (Schema::hasColumn('vehicles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
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
