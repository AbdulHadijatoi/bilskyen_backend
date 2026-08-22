<?php

namespace Tests\Unit;

use App\Constants\VehicleListStatus;
use App\Models\Vehicle;
use App\Services\RelatedVehiclesService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RelatedVehiclesServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->createSchema();
    }

    public function test_excludes_the_open_vehicle(): void
    {
        $openId = $this->insertVehicle([
            'brand_id' => 1,
            'model_id' => 10,
            'price' => 200000,
            'fuel_type_id' => 3,
        ]);
        $otherId = $this->insertVehicle([
            'brand_id' => 1,
            'model_id' => 10,
            'price' => 210000,
            'fuel_type_id' => 3,
        ]);

        $related = $this->service()->forVehicle(Vehicle::findOrFail($openId));

        $this->assertSame([$otherId], $related->pluck('id')->all());
    }

    public function test_prefers_same_brand_and_model_over_same_brand_only(): void
    {
        $openId = $this->insertVehicle([
            'brand_id' => 1,
            'model_id' => 10,
            'price' => 200000,
            'model_year' => 2020,
            'fuel_type_id' => 3,
            'body_type_id' => 7,
        ]);
        $sameModelId = $this->insertVehicle([
            'brand_id' => 1,
            'model_id' => 10,
            'price' => 250000,
            'model_year' => 2016,
            'fuel_type_id' => 3,
            'body_type_id' => 7,
        ]);
        $sameBrandId = $this->insertVehicle([
            'brand_id' => 1,
            'model_id' => 11,
            'price' => 200000,
            'model_year' => 2020,
            'fuel_type_id' => 3,
            'body_type_id' => 7,
        ]);

        $related = $this->service()->forVehicle(Vehicle::findOrFail($openId));

        $this->assertSame([$sameModelId, $sameBrandId], $related->pluck('id')->all());
    }

    public function test_scores_older_candidate_years_without_underflow(): void
    {
        $openId = $this->insertVehicle([
            'brand_id' => 1,
            'model_id' => 10,
            'price' => 200000,
            'model_year' => 2018,
            'fuel_type_id' => 3,
        ]);
        $olderId = $this->insertVehicle([
            'brand_id' => 1,
            'model_id' => 10,
            'price' => 190000,
            'model_year' => 1995,
            'fuel_type_id' => 3,
        ]);

        $related = $this->service()->forVehicle(Vehicle::findOrFail($openId));

        $this->assertSame([$olderId], $related->pluck('id')->all());
    }

    public function test_fills_from_brand_fallback_when_same_model_is_missing(): void
    {
        $openId = $this->insertVehicle([
            'brand_id' => 1,
            'model_id' => 99,
            'price' => 180000,
            'fuel_type_id' => 3,
        ]);
        $fallbackId = $this->insertVehicle([
            'brand_id' => 1,
            'model_id' => 12,
            'price' => 185000,
            'fuel_type_id' => 3,
        ]);

        $related = $this->service()->forVehicle(Vehicle::findOrFail($openId));

        $this->assertSame([$fallbackId], $related->pluck('id')->all());
    }

    public function test_fills_from_body_type_fallback_when_brand_matches_are_missing(): void
    {
        $openId = $this->insertVehicle([
            'brand_id' => 1,
            'model_id' => 10,
            'price' => 180000,
            'fuel_type_id' => 3,
            'body_type_id' => 7,
        ]);
        $bodyMatchId = $this->insertVehicle([
            'brand_id' => 2,
            'model_id' => 20,
            'price' => 175000,
            'fuel_type_id' => 3,
            'body_type_id' => 7,
        ]);

        $related = $this->service()->forVehicle(Vehicle::findOrFail($openId));

        $this->assertSame([$bodyMatchId], $related->pluck('id')->all());
    }

    public function test_ignores_unpublished_and_sold_listings(): void
    {
        $openId = $this->insertVehicle([
            'brand_id' => 1,
            'model_id' => 10,
            'price' => 200000,
            'fuel_type_id' => 3,
        ]);
        $this->insertVehicle([
            'brand_id' => 1,
            'model_id' => 10,
            'price' => 200000,
            'fuel_type_id' => 3,
            'list_status_id' => VehicleListStatus::DRAFT,
        ]);
        $this->insertVehicle([
            'brand_id' => 1,
            'model_id' => 10,
            'price' => 200000,
            'fuel_type_id' => 3,
            'list_status_id' => VehicleListStatus::SOLD,
        ]);
        $publishedId = $this->insertVehicle([
            'brand_id' => 1,
            'model_id' => 10,
            'price' => 205000,
            'fuel_type_id' => 3,
        ]);

        $related = $this->service()->forVehicle(Vehicle::findOrFail($openId));

        $this->assertSame([$publishedId], $related->pluck('id')->all());
    }

    public function test_returns_at_most_eight_related_vehicles(): void
    {
        $openId = $this->insertVehicle([
            'brand_id' => 1,
            'model_id' => 10,
            'price' => 200000,
            'fuel_type_id' => 3,
        ]);
        for ($i = 0; $i < 10; $i++) {
            $this->insertVehicle([
                'brand_id' => 1,
                'model_id' => 10,
                'price' => 200000 + ($i * 1000),
                'fuel_type_id' => 3,
            ]);
        }

        $related = $this->service()->forVehicle(Vehicle::findOrFail($openId));

        $this->assertCount(8, $related);
    }

    private function service(): RelatedVehiclesService
    {
        return $this->app->make(RelatedVehiclesService::class);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function insertVehicle(array $attrs): int
    {
        $now = now();

        return (int) DB::table('vehicles')->insertGetId(array_merge([
            'list_status_id' => VehicleListStatus::PUBLISHED,
            'title' => 'Test car',
            'price' => 200000,
            'created_at' => $now,
            'updated_at' => $now,
        ], $attrs));
    }

    private function createSchema(): void
    {
        Schema::dropIfExists('vehicle_images');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('sales_types');
        Schema::dropIfExists('dmr_drive_energies');
        Schema::dropIfExists('gear_types');
        Schema::dropIfExists('dealers');
        Schema::dropIfExists('dmr_variants');
        Schema::dropIfExists('dmr_models');
        Schema::dropIfExists('dmr_brands');

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('list_status_id');
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('fuel_type_id')->nullable();
            $table->unsignedBigInteger('body_type_id')->nullable();
            $table->unsignedBigInteger('gear_type_id')->nullable();
            $table->unsignedBigInteger('dealer_id')->nullable();
            $table->unsignedBigInteger('sales_type_id')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->unsignedSmallInteger('model_year')->nullable();
            $table->unsignedSmallInteger('first_registration_year')->nullable();
            $table->string('postcode')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vehicle_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->string('image_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('sales_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->softDeletes();
        });

        Schema::create('dmr_drive_energies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->softDeletes();
        });

        Schema::create('gear_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->softDeletes();
        });

        Schema::create('dealers', function (Blueprint $table) {
            $table->id();
            $table->string('cvr')->nullable();
            $table->softDeletes();
        });

        Schema::create('dmr_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('name')->nullable();
            $table->softDeletes();
        });

        Schema::create('dmr_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->string('name')->nullable();
            $table->softDeletes();
        });

        Schema::create('dmr_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->softDeletes();
        });
    }
}
