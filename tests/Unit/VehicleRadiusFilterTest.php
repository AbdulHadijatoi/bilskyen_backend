<?php

namespace Tests\Unit;

use App\Constants\VehicleListStatus;
use App\Models\Vehicle;
use App\Services\VehicleService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class VehicleRadiusFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->registerSqliteMathFunctions();
        $this->createSchema();
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

    public function test_radius_excludes_listings_outside_the_km_cap(): void
    {
        $nearId = $this->insertVehicle(['postcode' => '2100']);
        $farId = $this->insertVehicle(['postcode' => '9000']);
        DB::table('locations')->insert([
            ['postcode' => '2100', 'latitude' => 55.6761, 'longitude' => 12.5683],
            ['postcode' => '9000', 'latitude' => 57.048, 'longitude' => 9.919],
        ]);

        $ids = $this->filteredIds([
            'radius_km' => 25,
            'viewer_latitude' => 55.6761,
            'viewer_longitude' => 12.5683,
        ]);

        $this->assertSame([$nearId], $ids);
        $this->assertNotContains($farId, $ids);
    }

    public function test_radius_without_coordinates_is_a_noop(): void
    {
        $a = $this->insertVehicle(['postcode' => '2100']);
        $b = $this->insertVehicle(['postcode' => '9000']);
        DB::table('locations')->insert([
            ['postcode' => '2100', 'latitude' => 55.6761, 'longitude' => 12.5683],
            ['postcode' => '9000', 'latitude' => 57.048, 'longitude' => 9.919],
        ]);

        $ids = $this->filteredIds(['radius_km' => 25]);

        $this->assertEqualsCanonicalizing([$a, $b], $ids);
    }

    public function test_street_coordinates_beat_postcode_centroid(): void
    {
        $id = $this->insertVehicle([
            'postcode' => '9000',
            'latitude' => 55.6761,
            'longitude' => 12.5683,
        ]);
        DB::table('locations')->insert([
            ['postcode' => '9000', 'latitude' => 57.048, 'longitude' => 9.919],
        ]);

        $ids = $this->filteredIds([
            'radius_km' => 25,
            'viewer_latitude' => 55.6761,
            'viewer_longitude' => 12.5683,
        ]);

        $this->assertSame([$id], $ids);
    }

    public function test_null_street_coordinates_still_use_postcode_locations(): void
    {
        $nearId = $this->insertVehicle([
            'postcode' => '2100',
            'latitude' => null,
            'longitude' => null,
        ]);
        $farId = $this->insertVehicle(['postcode' => '9000']);
        DB::table('locations')->insert([
            ['postcode' => '2100', 'latitude' => 55.6761, 'longitude' => 12.5683],
            ['postcode' => '9000', 'latitude' => 57.048, 'longitude' => 9.919],
        ]);

        $ids = $this->filteredIds([
            'radius_km' => 25,
            'viewer_latitude' => 55.6761,
            'viewer_longitude' => 12.5683,
        ]);

        $this->assertSame([$nearId], $ids);
        $this->assertNotContains($farId, $ids);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<int>
     */
    private function filteredIds(array $filters): array
    {
        $query = Vehicle::query()->withoutGlobalScope('defaultOrder');
        $method = new ReflectionMethod(VehicleService::class, 'applyPublicListingFilters');
        $method->setAccessible(true);
        $method->invoke($this->app->make(VehicleService::class), $query, $filters);

        return $query->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function insertVehicle(array $attrs = []): int
    {
        $now = now();

        return (int) DB::table('vehicles')->insertGetId(array_merge([
            'list_status_id' => VehicleListStatus::PUBLISHED,
            'title' => 'Radius car',
            'price' => 100000,
            'created_at' => $now,
            'updated_at' => $now,
        ], $attrs));
    }

    private function createSchema(): void
    {
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('locations');

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('list_status_id');
            $table->string('postcode')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('postcode');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });
    }
}
