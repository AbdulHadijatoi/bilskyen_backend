<?php

namespace Tests\Unit;

use App\Constants\VehicleListStatus;
use App\Services\VehicleService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VehicleListingFacetsTest extends TestCase
{
    private function seedListingVehicles(): void
    {
        Schema::dropIfExists('vehicles');
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('list_status_id');
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('fuel_type_id')->nullable();
            $table->string('postcode')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();
        DB::table('vehicles')->insert([
            [
                'list_status_id' => VehicleListStatus::PUBLISHED,
                'brand_id' => 1,
                'fuel_type_id' => 10,
                'postcode' => '2100',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'list_status_id' => VehicleListStatus::PUBLISHED,
                'brand_id' => 1,
                'fuel_type_id' => 10,
                'postcode' => '2100',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'list_status_id' => VehicleListStatus::PUBLISHED,
                'brand_id' => 2,
                'fuel_type_id' => 20,
                'postcode' => '9000',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'list_status_id' => VehicleListStatus::DRAFT,
                'brand_id' => 1,
                'fuel_type_id' => 10,
                'postcode' => '2100',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function test_radius_filter_facets_do_not_select_vehicle_star(): void
    {
        $this->registerSqliteMathFunctions();
        $this->seedListingVehicles();

        Schema::dropIfExists('locations');
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('postcode');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });
        DB::table('locations')->insert([
            ['postcode' => '2100', 'latitude' => 55.6761, 'longitude' => 12.5683],
            ['postcode' => '9000', 'latitude' => 57.048, 'longitude' => 9.919],
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $facets = $this->app->make(VehicleService::class)->getPublicListingFacets([
            'radius_km' => 25,
            'viewer_latitude' => 55.6761,
            'viewer_longitude' => 12.5683,
        ]);

        foreach (DB::getQueryLog() as $entry) {
            $sql = strtolower($entry['query']);
            if (! str_contains($sql, 'group by')) {
                continue;
            }
            $this->assertStringNotContainsString(
                'vehicles.*',
                $sql,
                'Facet aggregates must not select vehicles.* under ONLY_FULL_GROUP_BY.'
            );
        }

        $this->assertSame(2, $facets['fuel_type_id']['10'] ?? 0);
        $this->assertSame(0, $facets['fuel_type_id']['20'] ?? 0);
        $this->assertSame(2, $facets['brand_id']['1'] ?? 0);
        $this->assertSame(0, $facets['brand_id']['2'] ?? 0);
    }

    private function registerSqliteMathFunctions(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        $pdo = DB::connection()->getPdo();
        $pdo->sqliteCreateFunction('ACOS', acos(...), 1);
        $pdo->sqliteCreateFunction('COS', cos(...), 1);
        $pdo->sqliteCreateFunction('SIN', sin(...), 1);
        $pdo->sqliteCreateFunction('RADIANS', deg2rad(...), 1);
        $pdo->sqliteCreateFunction('LEAST', min(...));
        $pdo->sqliteCreateFunction('GREATEST', max(...));
    }

    public function test_unfiltered_facets_count_published_vehicles_only(): void
    {
        $this->seedListingVehicles();

        $facets = $this->app->make(VehicleService::class)->getPublicListingFacets([]);

        $this->assertSame(2, $facets['fuel_type_id']['10'] ?? 0);
        $this->assertSame(1, $facets['fuel_type_id']['20'] ?? 0);
        $this->assertSame(2, $facets['brand_id']['1'] ?? 0);
        $this->assertSame(1, $facets['brand_id']['2'] ?? 0);
        $this->assertArrayHasKey('color_id', $facets);
        $this->assertArrayHasKey('use_id', $facets);
    }

    public function test_brand_filter_narrows_fuel_counts_but_not_brand_facet(): void
    {
        $this->seedListingVehicles();

        $facets = $this->app->make(VehicleService::class)->getPublicListingFacets([
            'brand_id' => [1],
        ]);

        $this->assertSame(2, $facets['fuel_type_id']['10'] ?? 0);
        $this->assertSame(0, $facets['fuel_type_id']['20'] ?? 0);
        $this->assertSame(2, $facets['brand_id']['1'] ?? 0);
        $this->assertSame(1, $facets['brand_id']['2'] ?? 0);
    }
}
