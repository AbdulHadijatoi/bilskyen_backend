<?php

namespace Tests\Unit;

use App\Services\ListingHealthService;
use Tests\TestCase;

class ListingHealthServiceTest extends TestCase
{
    public function test_score_snapshot_for_prospect_audit(): void
    {
        $service = app(ListingHealthService::class);

        $health = $service->scoreSnapshot([
            'title' => 'Test Car',
            'description' => 'Too short',
            'image_count' => 1,
            'equipment_count' => 0,
            'price' => 250000,
            'market_median' => 200000,
        ]);

        $this->assertLessThan(80, $health['score']);
        $this->assertNotEmpty($health['issues']);
        $this->assertSame('needs_attention', $health['grade']);
    }

    public function test_score_snapshot_healthy_listing(): void
    {
        $service = app(ListingHealthService::class);

        $health = $service->scoreSnapshot([
            'title' => 'VW Golf',
            'description' => str_repeat('Well maintained vehicle with full service history. ', 8),
            'image_count' => 12,
            'equipment_count' => 8,
            'price' => 189000,
            'market_median' => 190000,
        ]);

        $this->assertGreaterThanOrEqual(85, $health['score']);
        $this->assertSame('excellent', $health['grade']);
    }
}
