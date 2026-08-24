<?php

namespace Tests\Unit;

use App\Constants\VehicleListStatus;
use App\Models\ListingViewsLog;
use App\Models\Vehicle;
use App\Services\VehicleViewService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VehicleViewServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
    }

    public function test_recent_by_ids_preserves_order_and_skips_unpublished(): void
    {
        $first = $this->insertVehicle(['list_status_id' => VehicleListStatus::PUBLISHED]);
        $draft = $this->insertVehicle(['list_status_id' => VehicleListStatus::DRAFT]);
        $second = $this->insertVehicle(['list_status_id' => VehicleListStatus::PUBLISHED]);

        $recent = $this->service()->recentByIds([$second, $draft, $first, $second], 4);

        $this->assertSame([$second, $first], $recent->pluck('id')->all());
    }

    public function test_recent_by_ids_excludes_current_vehicle(): void
    {
        $open = $this->insertVehicle();
        $other = $this->insertVehicle();

        $recent = $this->service()->recentByIds([$open, $other], 4, $open);

        $this->assertSame([$other], $recent->pluck('id')->all());
    }

    public function test_recent_for_user_uses_latest_view_and_ignores_drafts(): void
    {
        $older = $this->insertVehicle();
        $newer = $this->insertVehicle();
        $draft = $this->insertVehicle(['list_status_id' => VehicleListStatus::DRAFT]);

        ListingViewsLog::create([
            'vehicle_id' => $older,
            'user_id' => 7,
            'ip_address' => '127.0.0.1',
            'viewed_at' => now()->subHour(),
        ]);
        ListingViewsLog::create([
            'vehicle_id' => $newer,
            'user_id' => 7,
            'ip_address' => '127.0.0.1',
            'viewed_at' => now(),
        ]);
        ListingViewsLog::create([
            'vehicle_id' => $draft,
            'user_id' => 7,
            'ip_address' => '127.0.0.1',
            'viewed_at' => now()->addMinute(),
        ]);

        $recent = $this->service()->recentForUser(7);

        $this->assertSame([$newer, $older], $recent->pluck('id')->all());
    }

    private function service(): VehicleViewService
    {
        return $this->app->make(VehicleViewService::class);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function insertVehicle(array $attrs = []): int
    {
        $now = now();

        return (int) DB::table('vehicles')->insertGetId(array_merge([
            'list_status_id' => VehicleListStatus::PUBLISHED,
            'title' => 'Recent car',
            'price' => 100000,
            'created_at' => $now,
            'updated_at' => $now,
        ], $attrs));
    }

    private function createSchema(): void
    {
        Schema::dropIfExists('listing_funnel_events');
        Schema::dropIfExists('listing_views_log');
        Schema::dropIfExists('vehicle_images');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('sales_types');
        Schema::dropIfExists('gear_types');
        Schema::dropIfExists('dealers');
        Schema::dropIfExists('dmr_variants');
        Schema::dropIfExists('dmr_models');
        Schema::dropIfExists('dmr_brands');
        Schema::dropIfExists('dmr_drive_energies');

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('list_status_id');
            $table->unsignedBigInteger('dealer_id')->nullable();
            $table->unsignedBigInteger('sales_type_id')->nullable();
            $table->unsignedBigInteger('fuel_type_id')->nullable();
            $table->unsignedBigInteger('gear_type_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vehicle_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('listing_views_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id', 64)->nullable();
            $table->string('traffic_source', 32)->nullable();
            $table->string('utm_source', 191)->nullable();
            $table->string('utm_campaign', 191)->nullable();
            $table->timestamp('viewed_at');
        });

        Schema::create('listing_funnel_events', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64);
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->string('traffic_source', 32)->nullable();
            $table->string('event_name', 32);
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('sales_types', function (Blueprint $table) {
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

        Schema::create('dmr_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->softDeletes();
        });

        Schema::create('dmr_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->string('name')->nullable();
            $table->softDeletes();
        });

        Schema::create('dmr_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('name')->nullable();
            $table->softDeletes();
        });

        Schema::create('dmr_drive_energies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->softDeletes();
        });
    }
}
