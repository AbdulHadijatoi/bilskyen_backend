<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dmr_fact_vehicles') || Schema::hasColumn('dmr_fact_vehicles', 'deleted_at')) {
            return;
        }

        Schema::table('dmr_fact_vehicles', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('dmr_fact_vehicles') || ! Schema::hasColumn('dmr_fact_vehicles', 'deleted_at')) {
            return;
        }

        Schema::table('dmr_fact_vehicles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
