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

        $hasFrom = Schema::hasColumn('vehicle_spec_definitions', 'model_year_from');
        $hasOldYear = Schema::hasColumn('vehicle_spec_definitions', 'model_year');

        if ($hasFrom && ! $hasOldYear) {
            $this->ensureRangeColumnsNotNullable();
            $this->ensureNewConstraints();

            return;
        }

        if ($hasOldYear && ! $hasFrom) {
            Schema::table('vehicle_spec_definitions', function (Blueprint $table) {
                $table->unsignedSmallInteger('model_year_from')->nullable()->after('variant_id');
                $table->unsignedSmallInteger('model_year_to')->nullable()->after('model_year_from');
            });
        }

        if (Schema::hasColumn('vehicle_spec_definitions', 'model_year')) {
            DB::statement('UPDATE vehicle_spec_definitions SET model_year_from = model_year, model_year_to = model_year WHERE model_year_from IS NULL OR model_year_to IS NULL');
        }

        $this->dropUniqueIfExists('vehicle_spec_definitions', 'vehicle_spec_definitions_scope_name_unique');
        $this->dropNonUniqueIndexOnColumn('vehicle_spec_definitions', 'model_year');

        if (Schema::hasColumn('vehicle_spec_definitions', 'model_year')) {
            Schema::table('vehicle_spec_definitions', function (Blueprint $table) {
                $table->dropColumn('model_year');
            });
        }

        $this->ensureRangeColumnsNotNullable();
        $this->ensureNewConstraints();
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicle_spec_definitions')) {
            return;
        }

        if (! Schema::hasColumn('vehicle_spec_definitions', 'model_year_from')) {
            return;
        }

        if (Schema::hasColumn('vehicle_spec_definitions', 'model_year')) {
            return;
        }

        $this->dropUniqueIfExists('vehicle_spec_definitions', 'vehicle_spec_definitions_scope_range_name_unique');
        $this->dropIndexIfExists('vehicle_spec_definitions', 'vehicle_spec_definitions_scope_range_idx');

        Schema::table('vehicle_spec_definitions', function (Blueprint $table) {
            $table->unsignedSmallInteger('model_year')->nullable()->after('variant_id');
        });

        DB::statement('UPDATE vehicle_spec_definitions SET model_year = model_year_from');

        Schema::table('vehicle_spec_definitions', function (Blueprint $table) {
            $table->unsignedSmallInteger('model_year')->nullable(false)->change();
        });

        Schema::table('vehicle_spec_definitions', function (Blueprint $table) {
            $table->dropColumn(['model_year_from', 'model_year_to']);
        });

        $this->dropUniqueIfExists('vehicle_spec_definitions', 'vehicle_spec_definitions_scope_name_unique');
        Schema::table('vehicle_spec_definitions', function (Blueprint $table) {
            $table->unique(
                ['brand_id', 'model_id', 'variant_id', 'model_year', 'name'],
                'vehicle_spec_definitions_scope_name_unique'
            );
            $table->index('model_year');
        });
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

    private function dropNonUniqueIndexOnColumn(string $table, string $column): void
    {
        foreach (Schema::getIndexes($table) as $idx) {
            if (($idx['columns'] ?? []) === [$column]
                && empty($idx['unique'])
                && empty($idx['primary'])) {
                Schema::table($table, function (Blueprint $blueprint) use ($idx): void {
                    $blueprint->dropIndex($idx['name']);
                });

                return;
            }
        }
    }

    private function ensureRangeColumnsNotNullable(): void
    {
        if (! Schema::hasColumn('vehicle_spec_definitions', 'model_year_from')) {
            return;
        }
        Schema::table('vehicle_spec_definitions', function (Blueprint $table): void {
            $table->unsignedSmallInteger('model_year_from')->nullable(false)->change();
            $table->unsignedSmallInteger('model_year_to')->nullable(false)->change();
        });
    }

    private function ensureNewConstraints(): void
    {
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
};
