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
            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();
        DB::table('vehicles')->insert([
            [
                'list_status_id' => VehicleListStatus::PUBLISHED,
                'brand_id' => 1,
                'fuel_type_id' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'list_status_id' => VehicleListStatus::PUBLISHED,
                'brand_id' => 1,
                'fuel_type_id' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'list_status_id' => VehicleListStatus::PUBLISHED,
                'brand_id' => 2,
                'fuel_type_id' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'list_status_id' => VehicleListStatus::DRAFT,
                'brand_id' => 1,
                'fuel_type_id' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
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
