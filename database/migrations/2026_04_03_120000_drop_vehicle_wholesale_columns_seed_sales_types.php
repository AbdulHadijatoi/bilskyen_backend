<?php

use App\Services\LookupService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SALES_TYPE_NAMES = [
        'Kontantpris',
        'Formidlingssalg',
        'Engros / CVR',
        'Komissionssalg',
        'Leasingdetaljer',
    ];

    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'wholesale_price')) {
                $table->dropColumn('wholesale_price');
            }
            if (Schema::hasColumn('vehicles', 'price_without_tax')) {
                $table->dropColumn('price_without_tax');
            }
        });

        foreach (self::SALES_TYPE_NAMES as $name) {
            if (! DB::table('sales_types')->where('name', $name)->exists()) {
                DB::table('sales_types')->insert(['name' => $name]);
            }
        }

        LookupService::forgetLookupCacheGroup('sales_types');
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'wholesale_price')) {
                $table->decimal('wholesale_price', 12, 2)->nullable()->after('price');
            }
            if (! Schema::hasColumn('vehicles', 'price_without_tax')) {
                $table->decimal('price_without_tax', 12, 2)->nullable()->after('internal_cost_price');
            }
        });
    }
};
