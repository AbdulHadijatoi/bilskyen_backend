<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicles') && Schema::hasColumn('vehicles', 'price')) {
            DB::statement('ALTER TABLE `vehicles` MODIFY `price` DECIMAL(12,2) NOT NULL');
        }

        if (Schema::hasTable('price_history')) {
            if (Schema::hasColumn('price_history', 'old_price')) {
                DB::statement('ALTER TABLE `price_history` MODIFY `old_price` DECIMAL(12,2) NOT NULL');
            }
            if (Schema::hasColumn('price_history', 'new_price')) {
                DB::statement('ALTER TABLE `price_history` MODIFY `new_price` DECIMAL(12,2) NOT NULL');
            }
        }

        if (! Schema::hasTable('vehicles')) {
            return;
        }

        $columnsToDrop = array_values(array_filter([
            Schema::hasColumn('vehicles', 'wholesale_price') ? 'wholesale_price' : null,
            Schema::hasColumn('vehicles', 'internal_cost_price') ? 'internal_cost_price' : null,
            Schema::hasColumn('vehicles', 'price_without_tax') ? 'price_without_tax' : null,
            Schema::hasColumn('vehicles', 'wholesale_price_includes_delivery') ? 'wholesale_price_includes_delivery' : null,
            Schema::hasColumn('vehicles', 'leasing_enabled') ? 'leasing_enabled' : null,
            Schema::hasColumn('vehicles', 'leasing_type') ? 'leasing_type' : null,
            Schema::hasColumn('vehicles', 'leasing_customer_type') ? 'leasing_customer_type' : null,
            Schema::hasColumn('vehicles', 'leasing_monthly_payment') ? 'leasing_monthly_payment' : null,
            Schema::hasColumn('vehicles', 'leasing_first_payment') ? 'leasing_first_payment' : null,
            Schema::hasColumn('vehicles', 'leasing_residual_value') ? 'leasing_residual_value' : null,
            Schema::hasColumn('vehicles', 'leasing_duration') ? 'leasing_duration' : null,
            Schema::hasColumn('vehicles', 'leasing_annual_mileage') ? 'leasing_annual_mileage' : null,
            Schema::hasColumn('vehicles', 'leasing_total_cost') ? 'leasing_total_cost' : null,
        ]));

        if ($columnsToDrop !== []) {
            Schema::table('vehicles', function ($table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vehicles') && Schema::hasColumn('vehicles', 'price')) {
            DB::statement('ALTER TABLE `vehicles` MODIFY `price` INT NOT NULL');
        }

        if (Schema::hasTable('price_history')) {
            if (Schema::hasColumn('price_history', 'old_price')) {
                DB::statement('ALTER TABLE `price_history` MODIFY `old_price` INT NOT NULL');
            }
            if (Schema::hasColumn('price_history', 'new_price')) {
                DB::statement('ALTER TABLE `price_history` MODIFY `new_price` INT NOT NULL');
            }
        }

        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function ($table) {
            if (! Schema::hasColumn('vehicles', 'wholesale_price')) {
                $table->unsignedInteger('wholesale_price')->nullable()->after('price');
            }
            if (! Schema::hasColumn('vehicles', 'internal_cost_price')) {
                $table->unsignedInteger('internal_cost_price')->nullable()->after('wholesale_price');
            }
            if (! Schema::hasColumn('vehicles', 'price_without_tax')) {
                $table->unsignedInteger('price_without_tax')->nullable()->after('internal_cost_price');
            }
            if (! Schema::hasColumn('vehicles', 'wholesale_price_includes_delivery')) {
                $table->boolean('wholesale_price_includes_delivery')->default(false)->after('price_without_tax');
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
            if (! Schema::hasColumn('vehicles', 'leasing_monthly_payment')) {
                $table->decimal('leasing_monthly_payment', 12, 2)->nullable()->after('leasing_customer_type');
            }
            if (! Schema::hasColumn('vehicles', 'leasing_first_payment')) {
                $table->decimal('leasing_first_payment', 12, 2)->nullable()->after('leasing_monthly_payment');
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
};
