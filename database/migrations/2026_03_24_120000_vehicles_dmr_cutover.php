<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int,int> */
    private array $vehicleListingIdToVehicleId = [];

    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        $this->dropForeignKeysOnTable('vehicles');

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'dmr_fact_vehicle_id')) {
                // Match dmr_fact_vehicles.id (signed BIGINT)
                $table->bigInteger('dmr_fact_vehicle_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('vehicles', 'description')) {
                $table->longText('description')->nullable();
            }
            if (Schema::hasColumn('vehicles', 'seller_address') && ! Schema::hasColumn('vehicles', 'address')) {
                DB::statement('ALTER TABLE `vehicles` CHANGE `seller_address` `address` TEXT NULL');
            }
            if (Schema::hasColumn('vehicles', 'seller_postcode') && ! Schema::hasColumn('vehicles', 'postcode')) {
                DB::statement('ALTER TABLE `vehicles` CHANGE `seller_postcode` `postcode` VARCHAR(20) NULL');
            }
            if (! Schema::hasColumn('vehicles', 'address') && ! Schema::hasColumn('vehicles', 'seller_address')) {
                $table->text('address')->nullable();
            }
            if (! Schema::hasColumn('vehicles', 'postcode') && ! Schema::hasColumn('vehicles', 'seller_postcode')) {
                $table->string('postcode', 20)->nullable();
            }
            if (! Schema::hasColumn('vehicles', 'condition_id')) {
                $table->unsignedInteger('condition_id')->nullable();
            }
            if (! Schema::hasColumn('vehicles', 'servicebog')) {
                $table->string('servicebog', 50)->nullable();
            }
        });

        $this->migrateVehicleListingsIntoVehicles();
        $this->mergeFeaturedFromVehicleListings();

        $this->dropVehicleListingsTables();

        if (Schema::hasTable('vehicle_details')) {
            DB::table('vehicle_details')->whereIn('vehicle_id', function ($q) {
                $q->select('id')->from('vehicles')->whereNull('dmr_fact_vehicle_id');
            })->delete();
        }

        foreach (['favorites', 'vehicle_equipment', 'vehicle_images', 'listing_views_log', 'price_history', 'leads', 'enquiries'] as $tbl) {
            if (Schema::hasTable($tbl)) {
                DB::table($tbl)->whereIn('vehicle_id', function ($q) {
                    $q->select('id')->from('vehicles')->whereNull('dmr_fact_vehicle_id');
                })->delete();
            }
        }

        if (Schema::hasTable('featured_listings')) {
            DB::table('featured_listings')->whereIn('vehicle_id', function ($q) {
                $q->select('id')->from('vehicles')->whereNull('dmr_fact_vehicle_id');
            })->delete();
        }

        DB::table('vehicles')->whereNull('dmr_fact_vehicle_id')->delete();

        if (Schema::hasTable('vehicle_details')) {
            DB::statement('
                UPDATE vehicles v
                INNER JOIN vehicle_details vd ON v.id = vd.vehicle_id
                SET v.description = COALESCE(v.description, vd.description)
                WHERE (v.description IS NULL OR v.description = \'\') AND vd.description IS NOT NULL
            ');
        }

        $this->dropLegacyVehicleColumns();

        if (DB::table('vehicles')->exists()) {
            DB::statement('ALTER TABLE `vehicles` MODIFY `dmr_fact_vehicle_id` BIGINT NOT NULL');
        }

        $this->addVehiclesForeignKeys();

        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'dmr_fact_vehicle_id')) {
                try {
                    $table->index('dmr_fact_vehicle_id');
                } catch (\Throwable) {
                }
            }
        });

    }

    public function down(): void
    {
    }

    /**
     * While legacy columns still exist, satisfy NOT NULL constraints for rows copied from vehicle_listings.
     *
     * @param  array<string,mixed>  $payload
     */
    private function fillLegacyNotNullColumns(array &$payload): void
    {
        $cols = Schema::getColumnListing('vehicles');
        if (in_array('fuel_type_id', $cols, true) && ! array_key_exists('fuel_type_id', $payload)) {
            $id = DB::table('fuel_types')->orderBy('id')->value('id');
            if ($id !== null) {
                $payload['fuel_type_id'] = $id;
            }
        }
        if (in_array('listing_type_id', $cols, true) && ! array_key_exists('listing_type_id', $payload)) {
            $id = DB::table('listing_types')->orderBy('id')->value('id');
            if ($id !== null) {
                $payload['listing_type_id'] = $id;
            }
        }
    }

    private function migrateVehicleListingsIntoVehicles(): void
    {
        if (! Schema::hasTable('vehicle_listings')) {
            return;
        }

        $rows = DB::table('vehicle_listings')->orderBy('id')->get();
        foreach ($rows as $row) {
            $existingVid = DB::table('vehicles')
                ->where('dmr_fact_vehicle_id', $row->dmr_fact_vehicle_id)
                ->where('user_id', $row->user_id)
                ->value('id');
            if ($existingVid !== null) {
                $this->vehicleListingIdToVehicleId[(int) $row->id] = (int) $existingVid;

                continue;
            }

            $payload = [
                'dmr_fact_vehicle_id' => $row->dmr_fact_vehicle_id,
                'user_id' => $row->user_id,
                'dealer_id' => $row->dealer_id,
                'title' => $row->title,
                'slug' => $row->slug,
                'registration' => $row->registration,
                'price' => $row->price,
                'vehicle_list_status_id' => $row->vehicle_list_status_id,
                'published_at' => $row->published_at,
                'description' => $row->description,
                'gear_type_id' => $row->gear_type_id,
                'km_driven' => $row->km_driven,
                'battery_capacity' => $row->battery_capacity,
                'range_km' => $row->range_km,
                'charging_type' => $row->charging_type,
                'condition_id' => $row->condition_id ?? null,
                'servicebog' => $row->servicebog ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
                'deleted_at' => $row->deleted_at ?? null,
            ];
            if (Schema::hasColumn('vehicles', 'address')) {
                $payload['address'] = $row->address ?? null;
            }
            if (Schema::hasColumn('vehicles', 'postcode')) {
                $payload['postcode'] = $row->postcode ?? null;
            }

            $this->fillLegacyNotNullColumns($payload);

            $newId = DB::table('vehicles')->insertGetId($payload);
            $this->vehicleListingIdToVehicleId[(int) $row->id] = $newId;

            if (Schema::hasTable('vehicle_listing_images')) {
                $imgs = DB::table('vehicle_listing_images')->where('vehicle_list_id', $row->id)->orderBy('sort_order')->get();
                foreach ($imgs as $img) {
                    DB::table('vehicle_images')->insert([
                        'vehicle_id' => $newId,
                        'image_path' => $img->image_path,
                        'thumbnail_path' => $img->thumbnail_path ?? null,
                        'sort_order' => $img->sort_order ?? 0,
                    ]);
                }
            }

            if (Schema::hasTable('vehicle_listing_equipments')) {
                $eq = DB::table('vehicle_listing_equipments')->where('vehicle_list_id', $row->id)->get();
                foreach ($eq as $e) {
                    try {
                        DB::table('vehicle_equipment')->insert([
                            'vehicle_id' => $newId,
                            'equipment_id' => $e->equipment_id,
                        ]);
                    } catch (\Throwable) {
                    }
                }
            }
        }
    }

    private function mergeFeaturedFromVehicleListings(): void
    {
        if (! Schema::hasTable('featured_listings') || ! Schema::hasColumn('featured_listings', 'vehicle_listing_id')) {
            return;
        }

        $rows = DB::table('featured_listings')->whereNotNull('vehicle_listing_id')->get();
        foreach ($rows as $fl) {
            $vid = $this->vehicleListingIdToVehicleId[(int) $fl->vehicle_listing_id] ?? null;
            if ($vid !== null) {
                DB::table('featured_listings')->where('id', $fl->id)->update(['vehicle_id' => $vid]);
            }
        }
    }

    private function dropVehicleListingsTables(): void
    {
        if (Schema::hasTable('featured_listings') && Schema::hasColumn('featured_listings', 'vehicle_listing_id')) {
            $this->dropForeignKeysOnTable('featured_listings');
            Schema::table('featured_listings', function (Blueprint $table) {
                $table->dropColumn('vehicle_listing_id');
            });
        }
        Schema::dropIfExists('vehicle_listing_equipments');
        Schema::dropIfExists('vehicle_listing_images');
        Schema::dropIfExists('vehicle_listings');
    }

    private function dropForeignKeysOnTable(string $table): void
    {
        $db = DB::getDatabaseName();
        $fks = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$db, $table, 'FOREIGN KEY']
        );
        foreach ($fks as $fk) {
            try {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            } catch (\Throwable) {
            }
        }
    }

    private function dropLegacyVehicleColumns(): void
    {
        $keep = [
            'id',
            'dmr_fact_vehicle_id',
            'user_id',
            'dealer_id',
            'title',
            'slug',
            'registration',
            'price',
            'vehicle_list_status_id',
            'published_at',
            'description',
            'address',
            'postcode',
            'gear_type_id',
            'km_driven',
            'battery_capacity',
            'range_km',
            'charging_type',
            'condition_id',
            'servicebog',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        $columns = Schema::getColumnListing('vehicles');
        foreach ($columns as $col) {
            if (! in_array($col, $keep, true)) {
                Schema::table('vehicles', function (Blueprint $table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }
    }

    private function addVehiclesForeignKeys(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'dmr_fact_vehicle_id')) {
                $table->foreign('dmr_fact_vehicle_id')->references('id')->on('dmr_fact_vehicles')->restrictOnDelete();
            }
            if (Schema::hasColumn('vehicles', 'user_id')) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            }
            if (Schema::hasColumn('vehicles', 'dealer_id')) {
                $table->foreign('dealer_id')->references('id')->on('dealers')->nullOnDelete();
            }
            if (Schema::hasColumn('vehicles', 'gear_type_id')) {
                $table->foreign('gear_type_id')->references('id')->on('gear_types')->nullOnDelete();
            }
            if (Schema::hasColumn('vehicles', 'condition_id')) {
                $table->foreign('condition_id')->references('id')->on('conditions')->nullOnDelete();
            }
            if (Schema::hasColumn('vehicles', 'vehicle_list_status_id')) {
                $table->foreign('vehicle_list_status_id')->references('id')->on('vehicle_list_statuses')->cascadeOnDelete();
            }
        });
    }
};
