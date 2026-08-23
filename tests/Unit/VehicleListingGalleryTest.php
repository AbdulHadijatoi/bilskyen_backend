<?php

namespace Tests\Unit;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Services\VehicleListingPresentationService;
use Tests\TestCase;

class VehicleListingGalleryTest extends TestCase
{
    public function test_gallery_urls_skip_placeholders_and_cap_slides(): void
    {
        $vehicle = new Vehicle();
        $images = collect();
        for ($i = 0; $i < 14; $i++) {
            $images->push(new VehicleImage([
                'image_path' => $i === 1 ? 'placeholder-vehicle.jpg' : "vehicles/{$i}.jpg",
                'thumbnail_path' => $i === 1 ? null : "vehicles/{$i}-t.jpg",
                'sort_order' => $i,
            ]));
        }
        $vehicle->setRelation('images', $images);

        $urls = VehicleListingPresentationService::galleryUrlsFor($vehicle);

        $this->assertCount(12, $urls);
        $this->assertFalse(
            collect($urls)->contains(fn (string $url) => str_contains($url, 'placeholder-vehicle'))
        );
        $this->assertStringContainsString('vehicles/0-t.jpg', $urls[0]);
        $this->assertStringContainsString('vehicles/12-t.jpg', $urls[11]);
        $this->assertStringNotContainsString('vehicles/13-t.jpg', implode(' ', $urls));
        $this->assertStringNotContainsString('vehicles/1-t.jpg', implode(' ', $urls));
        $this->assertStringNotContainsString('placeholder-vehicle', implode(' ', $urls));
    }

    public function test_gallery_urls_empty_when_images_not_loaded(): void
    {
        $this->assertSame([], VehicleListingPresentationService::galleryUrlsFor(new Vehicle()));
        $this->assertSame([], VehicleListingPresentationService::galleryUrlsFor(null));
    }
}
