<?php

namespace Tests\Unit;

use Tests\TestCase;

class VehicleDetailUxTest extends TestCase
{
    public function test_detail_page_has_share_dialog_and_location_map(): void
    {
        $source = file_get_contents(resource_path('views/vehicle-detail.blade.php'));

        $this->assertStringContainsString('<x-vehicle-share-dialog', $source);
        $this->assertStringContainsString('data-vehicle-map', $source);
        $this->assertStringContainsString('data-address="{{ $vehicleMapAddress }}"', $source);
        $this->assertStringContainsString('<x-vehicle-map-helpers', $source);
        $this->assertStringNotContainsString('<x-listing-compare-tray', $source);
        $this->assertStringNotContainsString('<x-compare-helpers', $source);

        $share = file_get_contents(resource_path('views/components/vehicle-share-dialog.blade.php'));
        $this->assertStringContainsString('id="vehicle-share-dialog"', $share);
        $this->assertStringContainsString('popover="auto"', $share);
        $this->assertStringContainsString('popovertarget="vehicle-share-dialog"', $share);
        $this->assertStringContainsString('aria-label="{{ __(\'messages.pages.vehicles.detail.share\') }}"', $share);
        $this->assertStringNotContainsString('<span>{{ __(\'messages.pages.vehicles.detail.share\') }}</span>', $share);
        $this->assertStringContainsString('data-share-copy', $share);
        $this->assertStringContainsString('facebook.com/sharer', $share);
        $this->assertStringContainsString('wa.me/', $share);

        $en = include resource_path('lang/en/messages.php');
        $da = include resource_path('lang/da/messages.php');
        foreach (['share', 'share_title', 'share_copy', 'share_copied', 'location_map_title'] as $key) {
            $this->assertNotEmpty($en['pages']['vehicles']['detail'][$key] ?? null, "Missing EN {$key}");
            $this->assertNotEmpty($da['pages']['vehicles']['detail'][$key] ?? null, "Missing DA {$key}");
        }
    }
}
