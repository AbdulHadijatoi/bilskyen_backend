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
            if (! Schema::hasColumn('vehicles', 'internal_cost_price')) {
                $table->decimal('internal_cost_price', 12, 2)->nullable()->after('price');
            }
            if (! Schema::hasColumn('vehicles', 'wholesale_price_includes_delivery')) {
                $table->boolean('wholesale_price_includes_delivery')->default(false)->after('internal_cost_price');
            }
            if (! Schema::hasColumn('vehicles', 'leasing_enabled')) {
                $table->boolean('leasing_enabled')->default(false)->after('wholesale_price_includes_delivery');
            }
            if (! Schema::hasColumn('vehicles', 'leasing_type')) {
                $table->string('leasing_type', 100)->nullable()->after('leasing_enabled');
            }
            if (! Schema::hasColumn('vehicles', 'leasing_customer_type')) {
                $table->string('leasing_customer_type', 100)->nullable()->after('leasing_type');
            }
            if (! Schema::hasColumn('vehicles', 'leasing_first_payment')) {
                $table->decimal('leasing_first_payment', 12, 2)->nullable()->after('leasing_customer_type');
            }
            if (! Schema::hasColumn('vehicles', 'leasing_residual_value')) {
                $table->decimal('leasing_residual_value', 12, 2)->nullable()->after('leasing_first_payment');
            }
            if (! Schema::hasColumn('vehicles', 'leasing_duration')) {
                $table->unsignedInteger('leasing_duration')->nullable()->after('leasing_residual_value');
            }
            if (! Schema::hasColumn('vehicles', 'leasing_annual_mileage')) {
                $table->unsignedInteger('leasing_annual_mileage')->nullable()->after('leasing_duration');
            }
            if (! Schema::hasColumn('vehicles', 'leasing_total_cost')) {
                $table->decimal('leasing_total_cost', 14, 2)->nullable()->after('leasing_annual_mileage');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        $columns = [
            'internal_cost_price',
            'wholesale_price_includes_delivery',
            'leasing_enabled',
            'leasing_type',
            'leasing_customer_type',
            'leasing_first_payment',
            'leasing_residual_value',
            'leasing_duration',
            'leasing_annual_mileage',
            'leasing_total_cost',
        ];

        $toDrop = array_values(array_filter($columns, fn (string $c) => Schema::hasColumn('vehicles', $c)));

        if ($toDrop !== []) {
            Schema::table('vehicles', function (Blueprint $table) use ($toDrop) {
                $table->dropColumn($toDrop);
            });
        }
    }
};
