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

        $this->dropUniqueIfExists('vehicle_spec_definitions', 'vehicle_spec_definitions_scope_range_name_unique');
        $this->dropIndexIfExists('vehicle_spec_definitions', 'vehicle_spec_definitions_scope_range_idx');

        $this->dropForeignKeysOnColumn('vehicle_spec_definitions', 'variant_id');

        Schema::table('vehicle_spec_definitions', function (Blueprint $table): void {
            // Match dmr_variants.id (signed BIGINT), same as vehicles.variant_id.
            $table->bigInteger('variant_id')->nullable()->change();
        });

        Schema::table('vehicle_spec_definitions', function (Blueprint $table): void {
            if (Schema::hasTable('dmr_variants')) {
                try {
                    $table->foreign('variant_id')->references('id')->on('dmr_variants')->cascadeOnDelete();
                } catch (\Throwable) {
                }
            }
        });

        $this->ensureRangeConstraints();
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicle_spec_definitions')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            $hasNull = Schema::getConnection()->table('vehicle_spec_definitions')->whereNull('variant_id')->exists();
            if ($hasNull) {
                throw new \RuntimeException('Cannot revert: vehicle_spec_definitions rows exist with variant_id NULL.');
            }
        }

        $this->dropUniqueIfExists('vehicle_spec_definitions', 'vehicle_spec_definitions_scope_range_name_unique');
        $this->dropIndexIfExists('vehicle_spec_definitions', 'vehicle_spec_definitions_scope_range_idx');

        $this->dropForeignKeysOnColumn('vehicle_spec_definitions', 'variant_id');

        Schema::table('vehicle_spec_definitions', function (Blueprint $table): void {
            $table->bigInteger('variant_id')->nullable(false)->change();
        });

        Schema::table('vehicle_spec_definitions', function (Blueprint $table): void {
            if (Schema::hasTable('dmr_variants')) {
                try {
                    $table->foreign('variant_id')->references('id')->on('dmr_variants')->cascadeOnDelete();
                } catch (\Throwable) {
                }
            }
        });

        $this->ensureRangeConstraints();
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

    private function ensureRangeConstraints(): void
    {
        if (! Schema::hasTable('vehicle_spec_definitions')) {
            return;
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
