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
            if (! Schema::hasColumn('vehicles', 'annual_tax')) {
                $table->decimal('annual_tax', 10, 2)->nullable()->after('servicebog');
            }
            if (! Schema::hasColumn('vehicles', 'seller_phone')) {
                $table->string('seller_phone', 50)->nullable()->after('postcode');
            }
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
            if (! Schema::hasColumn('vehicles', 'fuel_consumption_wltp')) {
                $table->decimal('fuel_consumption_wltp', 8, 2)->nullable()->after('km_per_liter');
            }
            if (! Schema::hasColumn('vehicles', 'fuel_consumption_nedc')) {
                $table->decimal('fuel_consumption_nedc', 8, 2)->nullable()->after('fuel_consumption_wltp');
            }
            if (! Schema::hasColumn('vehicles', 'production_date')) {
                $table->date('production_date')->nullable()->after('first_registration_year');
            }
            if (! Schema::hasColumn('vehicles', 'cover_image_index')) {
                $table->unsignedInteger('cover_image_index')->nullable()->after('production_date');
            }
            if (! Schema::hasColumn('vehicles', 'engine_type')) {
                $table->string('engine_type', 255)->nullable()->after('engine_displacement_litres');
            }
            if (! Schema::hasColumn('vehicles', 'views_count')) {
                $table->unsignedInteger('views_count')->default(0)->after('description');
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

        if (Schema::hasTable('vehicle_details')) {
            $this->backfillFromVehicleDetails();
        }

        if (Schema::hasColumn('vehicles', 'version')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropColumn('version');
            });
        }

        if (Schema::hasTable('vehicle_details')) {
            Schema::drop('vehicle_details');
        }
    }

    private function backfillFromVehicleDetails(): void
    {
        $vd = 'vehicle_details';
        $v = 'vehicles';

        $set = [];

        if (Schema::hasColumn($vd, 'wholesale_price') && Schema::hasColumn($v, 'wholesale_price')) {
            $set[] = 'v.wholesale_price = COALESCE(v.wholesale_price, ROUND(d.wholesale_price))';
        }
        if (Schema::hasColumn($vd, 'internal_cost_price') && Schema::hasColumn($v, 'internal_cost_price')) {
            $set[] = 'v.internal_cost_price = COALESCE(v.internal_cost_price, ROUND(d.internal_cost_price))';
        }
        if (Schema::hasColumn($vd, 'seller_phone') && Schema::hasColumn($v, 'seller_phone')) {
            $set[] = 'v.seller_phone = COALESCE(v.seller_phone, d.seller_phone)';
        }
        if (Schema::hasColumn($vd, 'annual_tax') && Schema::hasColumn($v, 'annual_tax')) {
            $set[] = 'v.annual_tax = COALESCE(v.annual_tax, d.annual_tax)';
        }
        if (Schema::hasColumn($vd, 'fuel_consumption_wltp') && Schema::hasColumn($v, 'fuel_consumption_wltp')) {
            $set[] = 'v.fuel_consumption_wltp = COALESCE(v.fuel_consumption_wltp, d.fuel_consumption_wltp)';
        }
        if (Schema::hasColumn($vd, 'fuel_consumption_nedc') && Schema::hasColumn($v, 'fuel_consumption_nedc')) {
            $set[] = 'v.fuel_consumption_nedc = COALESCE(v.fuel_consumption_nedc, d.fuel_consumption_nedc)';
        }
        if (Schema::hasColumn($vd, 'production_date') && Schema::hasColumn($v, 'production_date')) {
            $set[] = 'v.production_date = COALESCE(v.production_date, d.production_date)';
        }
        if (Schema::hasColumn($vd, 'cover_image_index') && Schema::hasColumn($v, 'cover_image_index')) {
            $set[] = 'v.cover_image_index = COALESCE(v.cover_image_index, d.cover_image_index)';
        }
        if (Schema::hasColumn($vd, 'engine_type') && Schema::hasColumn($v, 'engine_type')) {
            $set[] = 'v.engine_type = COALESCE(v.engine_type, d.engine_type)';
        }
        if (Schema::hasColumn($vd, 'views_count') && Schema::hasColumn($v, 'views_count')) {
            $set[] = 'v.views_count = COALESCE(d.views_count, v.views_count)';
        }
        if (Schema::hasColumn($vd, 'registration_status') && Schema::hasColumn($v, 'registration_status')) {
            $set[] = 'v.registration_status = COALESCE(v.registration_status, d.registration_status)';
        }
        if (Schema::hasColumn($vd, 'description') && Schema::hasColumn($v, 'description')) {
            $set[] = "v.description = CASE WHEN (v.description IS NULL OR v.description = '') AND d.description IS NOT NULL THEN d.description ELSE v.description END";
        }
        if (Schema::hasColumn($vd, 'servicebog') && Schema::hasColumn($v, 'servicebog')) {
            $set[] = 'v.servicebog = COALESCE(v.servicebog, d.servicebog)';
        }
        if (Schema::hasColumn($vd, 'last_inspection_date') && Schema::hasColumn($v, 'last_inspection_date')) {
            $set[] = 'v.last_inspection_date = COALESCE(v.last_inspection_date, d.last_inspection_date)';
        }
        if (Schema::hasColumn($vd, 'is_import') && Schema::hasColumn($v, 'is_import')) {
            $set[] = 'v.is_import = COALESCE(v.is_import, d.is_import)';
        }
        if (Schema::hasColumn($vd, 'is_factory_new') && Schema::hasColumn($v, 'is_factory_new')) {
            $set[] = 'v.is_factory_new = COALESCE(v.is_factory_new, d.is_factory_new)';
        }
        if (Schema::hasColumn($vd, 'co2_emissions') && Schema::hasColumn($v, 'co2_emission')) {
            $set[] = 'v.co2_emission = COALESCE(v.co2_emission, d.co2_emissions)';
        }
        if (Schema::hasColumn($vd, 'total_weight') && Schema::hasColumn($v, 'maximum_weight_kg')) {
            $set[] = 'v.maximum_weight_kg = COALESCE(v.maximum_weight_kg, d.total_weight, d.technical_total_weight)';
        }
        if (Schema::hasColumn($vd, 'doors') && Schema::hasColumn($v, 'door_count')) {
            $set[] = 'v.door_count = COALESCE(v.door_count, d.doors)';
        }
        if (Schema::hasColumn($vd, 'minimum_seats') && Schema::hasColumn($v, 'seats_min')) {
            $set[] = 'v.seats_min = COALESCE(v.seats_min, d.minimum_seats)';
        }
        if (Schema::hasColumn($vd, 'maximum_seats') && Schema::hasColumn($v, 'seats_max')) {
            $set[] = 'v.seats_max = COALESCE(v.seats_max, d.maximum_seats)';
        }
        if (Schema::hasColumn($vd, 'top_speed') && Schema::hasColumn($v, 'max_speed')) {
            $set[] = 'v.max_speed = COALESCE(v.max_speed, d.top_speed)';
        }
        if (Schema::hasColumn($vd, 'axles') && Schema::hasColumn($v, 'axle_count')) {
            $set[] = 'v.axle_count = COALESCE(v.axle_count, d.axles)';
        }
        if (Schema::hasColumn($vd, 'engine_displacement') && Schema::hasColumn($v, 'engine_displacement_litres')) {
            $set[] = 'v.engine_displacement_litres = COALESCE(v.engine_displacement_litres, d.engine_displacement / 1000)';
        }
        if ($set === []) {
            return;
        }

        $sql = 'UPDATE vehicles v INNER JOIN vehicle_details d ON d.vehicle_id = v.id SET '.implode(', ', $set);
        DB::statement($sql);
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::create('vehicle_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->unique()->constrained('vehicles')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $cols = [
                'annual_tax', 'seller_phone', 'wholesale_price', 'internal_cost_price', 'price_without_tax',
                'wholesale_price_includes_delivery', 'fuel_consumption_wltp', 'fuel_consumption_nedc',
                'production_date', 'cover_image_index', 'engine_type', 'views_count',
                'leasing_enabled', 'leasing_type', 'leasing_customer_type', 'leasing_monthly_payment',
                'leasing_first_payment', 'leasing_residual_value', 'leasing_duration', 'leasing_annual_mileage',
                'leasing_total_cost',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('vehicles', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        if (! Schema::hasColumn('vehicles', 'version')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->string('version', 100)->nullable()->after('first_registration_date');
            });
        }
    }
};
