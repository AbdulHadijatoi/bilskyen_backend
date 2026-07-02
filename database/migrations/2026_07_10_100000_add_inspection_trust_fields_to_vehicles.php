<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'last_inspection_result')) {
                $table->string('last_inspection_result', 100)->nullable()->after('last_inspection_date');
            }
            if (! Schema::hasColumn('vehicles', 'last_inspection_odometer')) {
                $table->unsignedInteger('last_inspection_odometer')->nullable()->after('last_inspection_result');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            foreach (['last_inspection_odometer', 'last_inspection_result'] as $col) {
                if (Schema::hasColumn('vehicles', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
