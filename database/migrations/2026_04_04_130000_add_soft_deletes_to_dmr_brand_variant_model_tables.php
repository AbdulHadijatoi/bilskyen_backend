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
        $this->addSoftDeletesIfMissing('dmr_brands');
        $this->addSoftDeletesIfMissing('dmr_models');
        $this->addSoftDeletesIfMissing('dmr_variants');
    }

    public function down(): void
    {
        $this->dropSoftDeletesIfPresent('dmr_variants');
        $this->dropSoftDeletesIfPresent('dmr_models');
        $this->dropSoftDeletesIfPresent('dmr_brands');
    }
};
