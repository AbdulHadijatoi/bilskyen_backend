<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicle_spec_definitions')) {
            return;
        }

        if (! Schema::hasColumn('vehicle_spec_definitions', 'variant_ids')) {
            Schema::table('vehicle_spec_definitions', function (Blueprint $table): void {
                $table->json('variant_ids')->nullable()->after('model_id');
            });
        }

        $this->backfillVariantIdsFromVariantId();

        $this->dropUniqueIfExists('vehicle_spec_definitions', 'vehicle_spec_definitions_scope_range_name_unique');
        $this->dropIndexIfExists('vehicle_spec_definitions', 'vehicle_spec_definitions_scope_range_idx');

        if (Schema::hasColumn('vehicle_spec_definitions', 'variant_id')) {
            $this->dropForeignKeysOnColumn('vehicle_spec_definitions', 'variant_id');
            Schema::table('vehicle_spec_definitions', function (Blueprint $table): void {
                $table->dropColumn('variant_id');
            });
        }

        if (! in_array('vehicle_spec_definitions_catalog_idx', $this->indexNames('vehicle_spec_definitions'), true)) {
            Schema::table('vehicle_spec_definitions', function (Blueprint $table): void {
                $table->index(
                    ['brand_id', 'model_id', 'model_year_from', 'model_year_to'],
                    'vehicle_spec_definitions_catalog_idx'
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicle_spec_definitions')) {
            return;
        }

        $this->dropIndexIfExists('vehicle_spec_definitions', 'vehicle_spec_definitions_catalog_idx');

        if (! Schema::hasColumn('vehicle_spec_definitions', 'variant_id')) {
            Schema::table('vehicle_spec_definitions', function (Blueprint $table): void {
                $table->unsignedBigInteger('variant_id')->nullable()->after('model_id');
            });
            if (Schema::hasTable('dmr_variants')) {
                try {
                    Schema::table('vehicle_spec_definitions', function (Blueprint $table): void {
                        $table->foreign('variant_id')->references('id')->on('dmr_variants')->cascadeOnDelete();
                    });
                } catch (\Throwable) {
                }
            }
        }

        $this->restoreVariantIdFromVariantIdsJson();

        if (Schema::hasColumn('vehicle_spec_definitions', 'variant_ids')) {
            Schema::table('vehicle_spec_definitions', function (Blueprint $table): void {
                $table->dropColumn('variant_ids');
            });
        }

        if (! in_array('vehicle_spec_definitions_scope_range_name_unique', $this->indexNames('vehicle_spec_definitions'), true)) {
            Schema::table('vehicle_spec_definitions', function (Blueprint $table): void {
                $table->unique(
                    ['brand_id', 'model_id', 'variant_id', 'model_year_from', 'model_year_to', 'name'],
                    'vehicle_spec_definitions_scope_range_name_unique'
                );
            });
        }
        if (! in_array('vehicle_spec_definitions_scope_range_idx', $this->indexNames('vehicle_spec_definitions'), true)) {
            Schema::table('vehicle_spec_definitions', function (Blueprint $table): void {
                $table->index(
                    ['brand_id', 'model_id', 'variant_id', 'model_year_from', 'model_year_to'],
                    'vehicle_spec_definitions_scope_range_idx'
                );
            });
        }
    }

    private function backfillVariantIdsFromVariantId(): void
    {
        if (! Schema::hasColumn('vehicle_spec_definitions', 'variant_id')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement(
                'UPDATE vehicle_spec_definitions SET variant_ids = JSON_ARRAY(variant_id) WHERE variant_id IS NOT NULL'
            );
        } else {
            foreach (DB::table('vehicle_spec_definitions')->whereNotNull('variant_id')->cursor() as $row) {
                $vid = (int) $row->variant_id;
                DB::table('vehicle_spec_definitions')->where('id', $row->id)->update([
                    'variant_ids' => json_encode([$vid]),
                ]);
            }
        }
    }

    private function restoreVariantIdFromVariantIdsJson(): void
    {
        if (! Schema::hasColumn('vehicle_spec_definitions', 'variant_ids')) {
            return;
        }

        foreach (DB::table('vehicle_spec_definitions')->orderBy('id')->cursor() as $row) {
            $raw = $row->variant_ids ?? null;
            $first = null;
            if ($raw !== null && $raw !== '') {
                $decoded = json_decode((string) $raw, true);
                if (is_array($decoded) && $decoded !== []) {
                    $first = (int) reset($decoded);
                }
            }
            DB::table('vehicle_spec_definitions')->where('id', $row->id)->update([
                'variant_id' => $first,
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function indexNames(string $table): array
    {
        return array_values(array_map(
            static fn (array $idx): string => $idx['name'],
            Schema::getIndexes($table)
        ));
    }

    private function dropUniqueIfExists(string $table, string $name): void
    {
        if (! in_array($name, $this->indexNames($table), true)) {
            return;
        }
        Schema::table($table, function (Blueprint $blueprint) use ($name): void {
            $blueprint->dropUnique($name);
        });
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        if (! in_array($name, $this->indexNames($table), true)) {
            return;
        }
        Schema::table($table, function (Blueprint $blueprint) use ($name): void {
            $blueprint->dropIndex($name);
        });
    }

    private function dropForeignKeysOnColumn(string $table, string $column): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
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

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column): void {
            try {
                $blueprint->dropForeign([$column]);
            } catch (\Throwable) {
            }
        });
    }
};
