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

        $this->dropForeignKeysOnTable('vehicles');
        $this->renameListStatusColumn();
        $this->addMissingTargetColumns();
        $this->backfillFromDmrFacts();
        $this->dropColumnsOutsideTarget();
        $this->addVehiclesForeignKeys();
        $this->addVehiclesIndexes();
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        $this->dropForeignKeysOnTable('vehicles');

        if (Schema::hasColumn('vehicles', 'list_status_id') && ! Schema::hasColumn('vehicles', 'vehicle_list_status_id')) {
            DB::statement('ALTER TABLE `vehicles` CHANGE `list_status_id` `vehicle_list_status_id` INT UNSIGNED NULL');
        }
    }

    private function renameListStatusColumn(): void
    {
        if (Schema::hasColumn('vehicles', 'vehicle_list_status_id') && ! Schema::hasColumn('vehicles', 'list_status_id')) {
            DB::statement('ALTER TABLE `vehicles` CHANGE `vehicle_list_status_id` `list_status_id` INT UNSIGNED NULL');
        }
    }

    private function addMissingTargetColumns(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'vin')) {
                $table->string('vin', 32)->nullable()->after('dmr_fact_vehicle_id');
            }
            if (! Schema::hasColumn('vehicles', 'km_per_liter')) {
                $table->decimal('km_per_liter', 10, 3)->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('vehicles', 'co2_emission')) {
                $table->decimal('co2_emission', 10, 3)->nullable()->after('km_per_liter');
            }
            if (! Schema::hasColumn('vehicles', 'electrical_consumption')) {
                $table->decimal('electrical_consumption', 10, 3)->nullable()->after('co2_emission');
            }
            if (! Schema::hasColumn('vehicles', 'engine_power_kw')) {
                $table->decimal('engine_power_kw', 10, 3)->nullable()->after('electrical_consumption');
            }
            if (! Schema::hasColumn('vehicles', 'engine_power_hp')) {
                $table->decimal('engine_power_hp', 10, 3)->nullable()->after('engine_power_kw');
            }
            if (! Schema::hasColumn('vehicles', 'engine_size_cc')) {
                $table->integer('engine_size_cc')->nullable()->after('engine_power_hp');
            }
            if (! Schema::hasColumn('vehicles', 'engine_displacement_litres')) {
                $table->decimal('engine_displacement_litres', 6, 2)->nullable()->after('engine_size_cc');
            }
            if (! Schema::hasColumn('vehicles', 'first_registration_date')) {
                $table->date('first_registration_date')->nullable()->after('engine_displacement_litres');
            }
            if (! Schema::hasColumn('vehicles', 'first_registration_year')) {
                $table->unsignedSmallInteger('first_registration_year')->nullable()->after('first_registration_date');
            }
            if (! Schema::hasColumn('vehicles', 'nox_emission')) {
                $table->decimal('nox_emission', 10, 3)->nullable()->after('first_registration_year');
            }
            if (! Schema::hasColumn('vehicles', 'particle_filter')) {
                $table->boolean('particle_filter')->nullable()->after('nox_emission');
            }
            if (! Schema::hasColumn('vehicles', 'axle_count')) {
                $table->unsignedTinyInteger('axle_count')->nullable()->after('particle_filter');
            }
            if (! Schema::hasColumn('vehicles', 'door_count')) {
                $table->unsignedTinyInteger('door_count')->nullable()->after('axle_count');
            }
            if (! Schema::hasColumn('vehicles', 'gear_count')) {
                $table->unsignedTinyInteger('gear_count')->nullable()->after('door_count');
            }
            if (! Schema::hasColumn('vehicles', 'max_speed')) {
                $table->unsignedSmallInteger('max_speed')->nullable()->after('gear_count');
            }
            if (! Schema::hasColumn('vehicles', 'model_year')) {
                $table->unsignedSmallInteger('model_year')->nullable()->after('max_speed');
            }
            if (! Schema::hasColumn('vehicles', 'ncap_test')) {
                $table->boolean('ncap_test')->nullable()->after('model_year');
            }
            if (! Schema::hasColumn('vehicles', 'seats_min')) {
                $table->unsignedTinyInteger('seats_min')->nullable()->after('ncap_test');
            }
            if (! Schema::hasColumn('vehicles', 'seats_max')) {
                $table->unsignedTinyInteger('seats_max')->nullable()->after('seats_min');
            }
            if (! Schema::hasColumn('vehicles', 'maximum_weight_kg')) {
                $table->integer('maximum_weight_kg')->nullable()->after('seats_max');
            }
            if (! Schema::hasColumn('vehicles', 'registration_status')) {
                $table->string('registration_status', 80)->nullable()->after('maximum_weight_kg');
            }
            if (! Schema::hasColumn('vehicles', 'last_registration_change')) {
                $table->date('last_registration_change')->nullable()->after('registration_status');
            }
            if (! Schema::hasColumn('vehicles', 'measurement_norm_id')) {
                $table->unsignedBigInteger('measurement_norm_id')->nullable()->after('last_registration_change');
            }
            if (! Schema::hasColumn('vehicles', 'list_status_id')) {
                $table->unsignedInteger('list_status_id')->nullable()->after('gear_type_id');
            }
        });
    }

    private function backfillFromDmrFacts(): void
    {
        if (! Schema::hasTable('dmr_fact_vehicles')) {
            return;
        }

        $hasRegistrationStatuses = Schema::hasTable('dmr_registration_statuses');
        $registrationStatusSelect = $hasRegistrationStatuses ? 'rs.name' : 'NULL';
        $registrationStatusJoin = $hasRegistrationStatuses
            ? 'LEFT JOIN dmr_registration_statuses rs ON rs.id = d.registration_status_id'
            : '';

        DB::statement("
            UPDATE vehicles v
            INNER JOIN dmr_fact_vehicles d ON d.id = v.dmr_fact_vehicle_id
            LEFT JOIN (
                SELECT b1.vehicle_id,
                       b1.measurement_norm_id,
                       b1.motor_km_per_liter,
                       b1.miljoe_co2_udslip,
                       b1.motor_elektrisk_forbrug
                FROM dmr_bridge_vehicle_drivmiddel b1
                INNER JOIN (
                    SELECT vehicle_id,
                           COALESCE(
                               MIN(CASE WHEN drivmiddel_primaer = 1 THEN line_order END),
                               MIN(line_order)
                           ) AS selected_line_order
                    FROM dmr_bridge_vehicle_drivmiddel
                    GROUP BY vehicle_id
                ) b2 ON b2.vehicle_id = b1.vehicle_id AND b2.selected_line_order = b1.line_order
            ) p ON p.vehicle_id = d.id
            {$registrationStatusJoin}
            SET
                v.registration = COALESCE(v.registration, d.registrering_nummer),
                v.vin = COALESCE(v.vin, d.stel_nummer),
                v.km_per_liter = COALESCE(v.km_per_liter, p.motor_km_per_liter),
                v.co2_emission = COALESCE(v.co2_emission, p.miljoe_co2_udslip),
                v.electrical_consumption = COALESCE(v.electrical_consumption, p.motor_elektrisk_forbrug),
                v.engine_power_kw = COALESCE(v.engine_power_kw, d.motor_stoerste_effekt),
                v.engine_power_hp = COALESCE(v.engine_power_hp, ROUND(d.motor_stoerste_effekt * 1.36, 3)),
                v.engine_size_cc = COALESCE(v.engine_size_cc, ROUND(d.motor_slag_volumen)),
                v.engine_displacement_litres = COALESCE(v.engine_displacement_litres, ROUND((ROUND(d.motor_slag_volumen / 100) * 100) / 1000, 2)),
                v.first_registration_date = COALESCE(v.first_registration_date, DATE(d.foerste_registrering_dato)),
                v.first_registration_year = COALESCE(v.first_registration_year, YEAR(d.foerste_registrering_dato)),
                v.nox_emission = COALESCE(v.nox_emission, d.emission_nox),
                v.particle_filter = COALESCE(v.particle_filter, d.partikel_filter),
                v.axle_count = COALESCE(v.axle_count, d.aksel_antal),
                v.door_count = COALESCE(v.door_count, d.antal_doere),
                v.gear_count = COALESCE(v.gear_count, d.antal_gear),
                v.max_speed = COALESCE(v.max_speed, d.maksimum_hastighed),
                v.model_year = COALESCE(v.model_year, d.model_aar),
                v.ncap_test = COALESCE(v.ncap_test, d.ncap_test),
                v.seats_min = COALESCE(v.seats_min, d.siddepladser_minimum),
                v.seats_max = COALESCE(v.seats_max, d.siddepladser_maksimum),
                v.maximum_weight_kg = COALESCE(v.maximum_weight_kg, d.teknisk_total_vaegt),
                v.registration_status = COALESCE(v.registration_status, {$registrationStatusSelect}),
                v.last_registration_change = COALESCE(v.last_registration_change, DATE(d.registrering_status_dato)),
                v.measurement_norm_id = COALESCE(v.measurement_norm_id, p.measurement_norm_id)
        ");
    }

    private function dropColumnsOutsideTarget(): void
    {
        $target = [
            'id',
            'registration',
            'dmr_fact_vehicle_id',
            'vin',
            'title',
            'slug',
            'dealer_id',
            'user_id',
            'km_per_liter',
            'co2_emission',
            'electrical_consumption',
            'engine_power_kw',
            'engine_power_hp',
            'engine_size_cc',
            'engine_displacement_litres',
            'first_registration_date',
            'first_registration_year',
            'nox_emission',
            'particle_filter',
            'axle_count',
            'door_count',
            'gear_count',
            'max_speed',
            'model_year',
            'ncap_test',
            'seats_min',
            'seats_max',
            'maximum_weight_kg',
            'registration_status',
            'last_registration_change',
            'measurement_norm_id',
            'listing_type_id',
            'sales_type_id',
            'price_type_id',
            'category_id',
            'price',
            'calculated_ownership_tax',
            'km_driven',
            'towing_weight',
            'is_import',
            'is_factory_new',
            'charging_type',
            'gear_type_id',
            'list_status_id',
            'published_at',
            'address',
            'postcode',
            'created_at',
            'updated_at',
            'deleted_at',
            'description',
            'condition_id',
            'servicebog',
        ];

        $columns = Schema::getColumnListing('vehicles');
        foreach ($columns as $column) {
            if (! in_array($column, $target, true)) {
                Schema::table('vehicles', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    private function addVehiclesForeignKeys(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'dmr_fact_vehicle_id') && Schema::hasTable('dmr_fact_vehicles')) {
                try {
                    $table->foreign('dmr_fact_vehicle_id')->references('id')->on('dmr_fact_vehicles')->restrictOnDelete();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasColumn('vehicles', 'dealer_id') && Schema::hasTable('dealers')) {
                try {
                    $table->foreign('dealer_id')->references('id')->on('dealers')->nullOnDelete();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasColumn('vehicles', 'user_id') && Schema::hasTable('users')) {
                try {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasColumn('vehicles', 'gear_type_id') && Schema::hasTable('gear_types')) {
                try {
                    $table->foreign('gear_type_id')->references('id')->on('gear_types')->nullOnDelete();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasColumn('vehicles', 'condition_id') && Schema::hasTable('conditions')) {
                try {
                    $table->foreign('condition_id')->references('id')->on('conditions')->nullOnDelete();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasColumn('vehicles', 'list_status_id') && Schema::hasTable('vehicle_list_statuses')) {
                try {
                    $table->foreign('list_status_id')->references('id')->on('vehicle_list_statuses')->cascadeOnDelete();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasColumn('vehicles', 'listing_type_id') && Schema::hasTable('listing_types')) {
                try {
                    $table->foreign('listing_type_id')->references('id')->on('listing_types')->nullOnDelete();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasColumn('vehicles', 'sales_type_id') && Schema::hasTable('sales_types')) {
                try {
                    $table->foreign('sales_type_id')->references('id')->on('sales_types')->nullOnDelete();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasColumn('vehicles', 'price_type_id') && Schema::hasTable('price_types')) {
                try {
                    $table->foreign('price_type_id')->references('id')->on('price_types')->nullOnDelete();
                } catch (\Throwable) {
                }
            }
            if (Schema::hasColumn('vehicles', 'category_id') && Schema::hasTable('categories')) {
                try {
                    $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
                } catch (\Throwable) {
                }
            }
        });
    }

    private function addVehiclesIndexes(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'registration') && ! $this->indexExists('vehicles', 'vehicles_registration_index')) {
                $table->index('registration');
            }
            if (Schema::hasColumn('vehicles', 'slug') && ! $this->indexExists('vehicles', 'vehicles_slug_unique')) {
                $table->unique('slug');
            }
            if (Schema::hasColumn('vehicles', 'dmr_fact_vehicle_id') && ! $this->indexExists('vehicles', 'vehicles_dmr_fact_vehicle_id_index')) {
                $table->index('dmr_fact_vehicle_id');
            }
            if (Schema::hasColumn('vehicles', 'list_status_id') && ! $this->indexExists('vehicles', 'vehicles_list_status_id_index')) {
                $table->index('list_status_id');
            }
            if (Schema::hasColumn('vehicles', 'price') && ! $this->indexExists('vehicles', 'vehicles_price_index')) {
                $table->index('price');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $dbName = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$dbName, $table, $indexName]
        );

        return ! empty($rows);
    }

    private function dropForeignKeysOnTable(string $table): void
    {
        $dbName = DB::getDatabaseName();
        $fks = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$dbName, $table, 'FOREIGN KEY']
        );

        foreach ($fks as $fk) {
            try {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            } catch (\Throwable) {
            }
        }
    }
};
