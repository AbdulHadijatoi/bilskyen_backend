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
            if (! Schema::hasColumn('vehicles', 'listing_type_id')) {
                $table->unsignedInteger('listing_type_id')->nullable()->after('vehicle_list_status_id');
            }
            if (! Schema::hasColumn('vehicles', 'sales_type_id')) {
                $table->unsignedInteger('sales_type_id')->nullable()->after('listing_type_id');
            }
            if (! Schema::hasColumn('vehicles', 'price_type_id')) {
                $table->unsignedInteger('price_type_id')->nullable()->after('sales_type_id');
            }
            if (! Schema::hasColumn('vehicles', 'category_id')) {
                $table->unsignedInteger('category_id')->nullable()->after('price_type_id');
            }
            if (! Schema::hasColumn('vehicles', 'type_id')) {
                $table->unsignedInteger('type_id')->nullable()->after('category_id');
            }
            if (! Schema::hasColumn('vehicles', 'transmission_id')) {
                $table->unsignedInteger('transmission_id')->nullable()->after('type_id');
            }
            if (! Schema::hasColumn('vehicles', 'towing_weight')) {
                $table->integer('towing_weight')->nullable()->after('range_km');
            }
            if (! Schema::hasColumn('vehicles', 'airbags')) {
                $table->integer('airbags')->nullable()->after('towing_weight');
            }
            if (! Schema::hasColumn('vehicles', 'wheels')) {
                $table->integer('wheels')->nullable()->after('airbags');
            }
            if (! Schema::hasColumn('vehicles', 'drive_axles')) {
                $table->json('drive_axles')->nullable()->after('wheels');
            }
            if (! Schema::hasColumn('vehicles', 'is_import')) {
                $table->boolean('is_import')->nullable()->after('drive_axles');
            }
            if (! Schema::hasColumn('vehicles', 'is_factory_new')) {
                $table->boolean('is_factory_new')->nullable()->after('is_import');
            }
            if (! Schema::hasColumn('vehicles', 'fuel_efficiency')) {
                $table->decimal('fuel_efficiency', 8, 2)->nullable()->after('gear_type_id');
            }
        });

        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasTable('listing_types') && Schema::hasColumn('vehicles', 'listing_type_id')) {
                try {
                    $table->foreign('listing_type_id')->references('id')->on('listing_types')->nullOnDelete();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasTable('sales_types') && Schema::hasColumn('vehicles', 'sales_type_id')) {
                try {
                    $table->foreign('sales_type_id')->references('id')->on('sales_types')->nullOnDelete();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasTable('price_types') && Schema::hasColumn('vehicles', 'price_type_id')) {
                try {
                    $table->foreign('price_type_id')->references('id')->on('price_types')->nullOnDelete();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasTable('categories') && Schema::hasColumn('vehicles', 'category_id')) {
                try {
                    $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasTable('types') && Schema::hasColumn('vehicles', 'type_id')) {
                try {
                    $table->foreign('type_id')->references('id')->on('types')->nullOnDelete();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasTable('transmissions') && Schema::hasColumn('vehicles', 'transmission_id')) {
                try {
                    $table->foreign('transmission_id')->references('id')->on('transmissions')->nullOnDelete();
                } catch (\Throwable) {
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            $fkCols = [
                'listing_type_id', 'sales_type_id', 'price_type_id',
                'category_id', 'type_id', 'transmission_id',
            ];
            foreach ($fkCols as $col) {
                if (Schema::hasColumn('vehicles', $col)) {
                    try {
                        $table->dropForeign([$col]);
                    } catch (\Throwable) {
                    }
                }
            }
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $cols = [
                'listing_type_id', 'sales_type_id', 'price_type_id', 'category_id', 'type_id',
                'transmission_id', 'towing_weight', 'airbags', 'wheels', 'drive_axles',
                'is_import', 'is_factory_new', 'fuel_efficiency',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('vehicles', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
