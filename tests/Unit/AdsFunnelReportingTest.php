<?php

namespace Tests\Unit;

use App\Constants\VehicleListStatus;
use App\Models\ListingFunnelEvent;
use App\Models\ListingViewsLog;
use App\Services\Analytics\AnalyticsReportingService;
use App\Services\Marketing\ListingFunnelService;
use App\Services\Marketing\TrafficAttributionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdsFunnelReportingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
    }

    public function test_ads_funnel_separates_meta_from_other_and_lists_vehicles(): void
    {
        $metaCar = $this->insertVehicle('Meta car', 'meta-car');
        $otherCar = $this->insertVehicle('Organic car', 'organic-car');

        ListingViewsLog::create([
            'vehicle_id' => $metaCar,
            'session_id' => 's-meta',
            'traffic_source' => TrafficAttributionService::SOURCE_META,
            'utm_source' => 'facebook',
            'utm_campaign' => 'spring',
            'viewed_at' => now(),
        ]);
        ListingViewsLog::create([
            'vehicle_id' => $otherCar,
            'session_id' => 's-other',
            'traffic_source' => TrafficAttributionService::SOURCE_OTHER,
            'viewed_at' => now(),
        ]);

        foreach ([ListingFunnelService::ENGAGED, ListingFunnelService::CTA_CLICK, ListingFunnelService::FORM_OPEN, ListingFunnelService::CONVERT] as $event) {
            ListingFunnelEvent::create([
                'session_id' => 's-meta',
                'vehicle_id' => $metaCar,
                'traffic_source' => TrafficAttributionService::SOURCE_META,
                'event_name' => $event,
                'created_at' => now(),
            ]);
        }

        $report = $this->app->make(AnalyticsReportingService::class)->adsFunnel(now()->subDay(), now()->addDay(), 'meta');

        $this->assertSame('meta', $report['source']);
        $this->assertSame(1, $report['steps']['landed']);
        $this->assertSame(1, $report['steps']['engaged']);
        $this->assertSame(1, $report['steps']['converted']);
        $this->assertSame(100.0, $report['rates']['landed_to_converted']);
        $this->assertSame(1, $report['compare']['meta_landed']);
        $this->assertSame(1, $report['compare']['other_landed']);
        $this->assertCount(1, $report['vehicles']);
        $this->assertSame($metaCar, $report['vehicles'][0]['vehicle_id']);
        $this->assertSame(100.0, $report['vehicles'][0]['conversion_rate']);
    }

    private function insertVehicle(string $title, string $slug): int
    {
        return (int) DB::table('vehicles')->insertGetId([
            'list_status_id' => VehicleListStatus::PUBLISHED,
            'title' => $title,
            'slug' => $slug,
            'price' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSchema(): void
    {
        Schema::dropIfExists('listing_funnel_events');
        Schema::dropIfExists('listing_views_log');
        Schema::dropIfExists('vehicles');

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->unsignedBigInteger('list_status_id');
            $table->decimal('price', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
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
    }
}
