<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->dmrSoftDeleteColumnsPresent()) {
            return;
        }

        $now = now();

        DB::table('dmr_fact_vehicles')
            ->whereNull('registrering_nummer')
            ->whereNull('deleted_at')
            ->update(['deleted_at' => $now]);

        DB::table('dmr_variants')
            ->whereNull('deleted_at')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('dmr_fact_vehicles')
                    ->whereColumn('dmr_fact_vehicles.variant_id', 'dmr_variants.id')
                    ->whereNull('dmr_fact_vehicles.deleted_at');
            })
            ->update(['deleted_at' => $now]);

        DB::table('dmr_models')
            ->whereNull('deleted_at')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('dmr_variants')
                    ->whereColumn('dmr_variants.model_id', 'dmr_models.id')
                    ->whereNull('dmr_variants.deleted_at');
            })
            ->update(['deleted_at' => $now]);

        DB::table('dmr_brands')
            ->whereNull('deleted_at')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('dmr_models')
                    ->whereColumn('dmr_models.brand_id', 'dmr_brands.id')
                    ->whereNull('dmr_models.deleted_at');
            })
            ->update(['deleted_at' => $now]);
    }

    public function down(): void
    {
        // Irreversible: restored deleted_at values are not recorded.
    }

    private function dmrSoftDeleteColumnsPresent(): bool
    {
        foreach (['dmr_fact_vehicles', 'dmr_variants', 'dmr_models', 'dmr_brands'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'deleted_at')) {
                return false;
            }
        }

        return true;
    }
};
