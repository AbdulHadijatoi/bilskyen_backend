<?php

namespace Tests\Unit;

use App\Models\Dealer;
use App\Services\DealerBadgeService;
use App\Services\SubscriptionFeatureService;
use Mockery;
use Tests\TestCase;

class DealerBadgeServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_listing_badge_label_uses_custom_dealer_text(): void
    {
        $service = new DealerBadgeService(Mockery::mock(SubscriptionFeatureService::class));
        $dealer = new Dealer(['listing_badge_label' => 'Partner']);

        $this->assertSame('Partner', $service->listingBadgeLabel($dealer));
    }

    public function test_listing_badge_label_falls_back_to_default_translation(): void
    {
        $service = new DealerBadgeService(Mockery::mock(SubscriptionFeatureService::class));

        $this->assertSame(
            (string) __('messages.pages.vehicles.dealer'),
            $service->listingBadgeLabel(new Dealer())
        );
        $this->assertSame(
            (string) __('messages.pages.vehicles.dealer'),
            $service->listingBadgeLabel(null)
        );
    }

    public function test_listing_badge_label_treats_blank_custom_as_default(): void
    {
        $service = new DealerBadgeService(Mockery::mock(SubscriptionFeatureService::class));
        $dealer = new Dealer(['listing_badge_label' => '   ']);

        $this->assertSame(
            (string) __('messages.pages.vehicles.dealer'),
            $service->listingBadgeLabel($dealer)
        );
    }

    public function test_should_show_listing_badge_defaults_to_true(): void
    {
        $service = new DealerBadgeService(Mockery::mock(SubscriptionFeatureService::class));

        $this->assertTrue($service->shouldShowListingBadge(null));
        $this->assertTrue($service->shouldShowListingBadge(new Dealer()));
    }

    public function test_should_show_listing_badge_respects_dealer_setting(): void
    {
        $service = new DealerBadgeService(Mockery::mock(SubscriptionFeatureService::class));

        $this->assertFalse($service->shouldShowListingBadge(new Dealer(['show_listing_badge' => false])));
        $this->assertTrue($service->shouldShowListingBadge(new Dealer(['show_listing_badge' => true])));
    }
}
