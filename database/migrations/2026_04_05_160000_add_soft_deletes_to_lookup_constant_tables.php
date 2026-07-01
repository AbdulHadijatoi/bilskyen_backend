<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function addSoftDeletesIfMissing(string $table): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'deleted_at')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->softDeletes();
        });
    }

    private function dropSoftDeletesIfPresent(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'deleted_at')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->dropSoftDeletes();
        });
    }

    public function up(): void
    {
        foreach ([
            'dmr_drive_energies',
            'gear_types',
            'listing_types',
            'dmr_body_types',
            'dmr_colours',
            'conditions',
            'sales_types',
            'price_types',
            'dmr_emission_norms',
            'dmr_vehicle_uses',
            'vehicle_list_statuses',
            'equipment_types',
            'equipments',
        ] as $table) {
            $this->addSoftDeletesIfMissing($table);
        }
    }

    public function down(): void
    {
        foreach ([
            'equipments',
            'equipment_types',
            'vehicle_list_statuses',
            'dmr_vehicle_uses',
            'dmr_emission_norms',
            'price_types',
            'sales_types',
            'conditions',
            'dmr_colours',
            'dmr_body_types',
            'listing_types',
            'gear_types',
            'dmr_drive_energies',
        ] as $table) {
            $this->dropSoftDeletesIfPresent($table);
        }
    }
};
