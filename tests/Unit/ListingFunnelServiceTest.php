<?php

namespace Tests\Unit;

use App\Constants\VehicleListStatus;
use App\Models\ListingFunnelEvent;
use App\Services\Marketing\ListingFunnelService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ListingFunnelServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
    }

    public function test_records_client_event_once_per_session_and_vehicle(): void
    {
        $vehicleId = $this->insertVehicle();
        $request = $this->requestWithSession();
        $service = $this->app->make(ListingFunnelService::class);

        $service->record($request, $vehicleId, ListingFunnelService::ENGAGED);
        $service->record($request, $vehicleId, ListingFunnelService::ENGAGED);

        $this->assertSame(1, ListingFunnelEvent::query()->where('event_name', ListingFunnelService::ENGAGED)->count());
    }

    public function test_form_error_is_not_deduped(): void
    {
        $vehicleId = $this->insertVehicle();
        $request = $this->requestWithSession();
        $service = $this->app->make(ListingFunnelService::class);

        $service->record($request, $vehicleId, ListingFunnelService::FORM_ERROR, ['form' => 'enquiry']);
        $service->record($request, $vehicleId, ListingFunnelService::FORM_ERROR, ['form' => 'enquiry']);

        $this->assertSame(2, ListingFunnelEvent::query()->where('event_name', ListingFunnelService::FORM_ERROR)->count());
    }

    public function test_ignores_unknown_vehicle(): void
    {
        $request = $this->requestWithSession();
        $service = $this->app->make(ListingFunnelService::class);

        $service->record($request, 999999, ListingFunnelService::CTA_CLICK);

        $this->assertSame(0, ListingFunnelEvent::query()->count());
    }

    private function requestWithSession(): Request
    {
        $request = Request::create('https://example.test/biler/foo', 'GET', [
            'fbclid' => 'click-1',
            'utm_source' => 'facebook',
        ]);
        $request->setLaravelSession($this->app['session']->driver());
        $this->app->instance('request', $request);
        app(\App\Services\Marketing\TrafficAttributionService::class)->capture($request);

        return $request;
    }

    private function insertVehicle(): int
    {
        return (int) DB::table('vehicles')->insertGetId([
            'list_status_id' => VehicleListStatus::PUBLISHED,
            'title' => 'Funnel car',
            'price' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSchema(): void
    {
        Schema::dropIfExists('listing_funnel_events');
        Schema::dropIfExists('vehicles');

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('list_status_id');
            $table->decimal('price', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
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
    }
}
