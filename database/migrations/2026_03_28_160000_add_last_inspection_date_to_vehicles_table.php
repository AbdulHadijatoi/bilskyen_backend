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
            if (! Schema::hasColumn('vehicles', 'last_inspection_date')) {
                $table->date('last_inspection_date')->nullable()->after('first_registration_year');
            }
        });

        if (Schema::hasTable('vehicle_details') && Schema::hasColumn('vehicle_details', 'last_inspection_date')) {
            DB::statement('
                UPDATE vehicles v
                INNER JOIN vehicle_details vd ON vd.vehicle_id = v.id
                SET v.last_inspection_date = vd.last_inspection_date
                WHERE v.last_inspection_date IS NULL
                  AND vd.last_inspection_date IS NOT NULL
            ');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles') || ! Schema::hasColumn('vehicles', 'last_inspection_date')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('last_inspection_date');
        });
    }
};
