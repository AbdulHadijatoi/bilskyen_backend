<?php

namespace Tests\Unit;

use App\Models\Vehicle;
use App\Services\Feeds\VehicleFeedBuilderService;
use App\Services\Media\VehicleMediaPolicyService;
use Tests\TestCase;

class VehicleFeedR6Test extends TestCase
{
    public function test_video_provider_detection(): void
    {
        $this->assertSame('youtube', VehicleMediaPolicyService::detectVideoProvider('https://www.youtube.com/watch?v=abc'));
        $this->assertSame('vimeo', VehicleMediaPolicyService::detectVideoProvider('https://vimeo.com/123'));
        $this->assertNull(VehicleMediaPolicyService::detectVideoProvider(null));
    }

    public function test_feed_builder_maps_vehicle_fields(): void
    {
        $service = app(VehicleFeedBuilderService::class);
        $vehicle = new Vehicle([
            'title' => 'Test Car',
            'price' => 150000,
            'slug' => 'test-car',
            'video_url' => 'https://www.youtube.com/watch?v=abc',
        ]);
        $vehicle->id = 42;
        $vehicle->setRelation('images', collect());
        $vehicle->setRelation('brand', null);
        $vehicle->setRelation('fuelType', null);
        $vehicle->setRelation('gearType', null);

        $mapped = $service->mapVehicle($vehicle);

        $this->assertSame(42, $mapped['id']);
        $this->assertSame('test-car', $mapped['slug']);
        $this->assertSame('https://www.youtube.com/watch?v=abc', $mapped['video_url']);
        $this->assertIsArray($mapped['images']);
    }
}
