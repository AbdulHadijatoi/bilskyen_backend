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
            if (! Schema::hasColumn('vehicles', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('postcode');
            }
            if (! Schema::hasColumn('vehicles', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
            $drops = [];
            if (Schema::hasColumn('vehicles', 'longitude')) {
                $drops[] = 'longitude';
            }
            if (Schema::hasColumn('vehicles', 'latitude')) {
                $drops[] = 'latitude';
            }
            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};
