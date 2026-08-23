<?php

namespace Tests\Unit;

use App\Constants\VehicleListStatus;
use App\Models\Vehicle;
use Tests\TestCase;

class VehiclePublicVisibilityTest extends TestCase
{
    public function test_published_is_publicly_viewable(): void
    {
        $vehicle = new Vehicle(['list_status_id' => VehicleListStatus::PUBLISHED]);

        $this->assertTrue($vehicle->isPublished());
        $this->assertFalse($vehicle->isSold());
        $this->assertTrue($vehicle->isPubliclyViewable());
    }

    public function test_sold_is_publicly_viewable_but_not_published(): void
    {
        $vehicle = new Vehicle(['list_status_id' => VehicleListStatus::SOLD]);

        $this->assertFalse($vehicle->isPublished());
        $this->assertTrue($vehicle->isSold());
        $this->assertTrue($vehicle->isPubliclyViewable());
    }

    public function test_draft_and_archived_are_not_publicly_viewable(): void
    {
        $draft = new Vehicle(['list_status_id' => VehicleListStatus::DRAFT]);
        $archived = new Vehicle(['list_status_id' => VehicleListStatus::ARCHIVED]);
        $pending = new Vehicle(['list_status_id' => VehicleListStatus::PENDING_REVIEW]);

        $this->assertFalse($draft->isPubliclyViewable());
        $this->assertFalse($archived->isPubliclyViewable());
        $this->assertFalse($pending->isPubliclyViewable());
    }
}
