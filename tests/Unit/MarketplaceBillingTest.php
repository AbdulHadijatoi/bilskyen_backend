<?php

namespace Tests\Unit;

use App\Constants\BillingModel;
use App\Constants\DealerInvoiceStatus;
use App\Constants\ListingBillingPeriodStatus;
use App\Models\Dealer;
use App\Models\Vehicle;
use App\Services\ListingBillingService;
use App\Services\SubscriptionFeatureService;
use Mockery;
use Tests\TestCase;

class MarketplaceBillingTest extends TestCase
{
    public function test_billing_model_constants(): void
    {
        $this->assertContains(BillingModel::SUBSCRIPTION, BillingModel::values());
        $this->assertContains(BillingModel::USAGE_DAILY, BillingModel::values());
        $this->assertTrue(BillingModel::isValid('usage_daily'));
    }

    public function test_invoice_and_billing_period_status_constants(): void
    {
        $this->assertContains(DealerInvoiceStatus::DRAFT, DealerInvoiceStatus::values());
        $this->assertContains(ListingBillingPeriodStatus::PENDING, ListingBillingPeriodStatus::values());
    }

    public function test_vehicle_fillable_includes_billing_and_expiry_columns(): void
    {
        $fillable = (new Vehicle)->getFillable();

        foreach ([
            'listing_billing_started_at',
            'listing_billing_paused_at',
            'expires_at',
            'view_3d_url',
        ] as $column) {
            $this->assertContains($column, $fillable, 'Missing fillable: '.$column);
        }
    }

    public function test_marketplace_config_defaults(): void
    {
        $this->assertSame('Europe/Copenhagen', config('marketplace.timezone'));
        $this->assertGreaterThan(0, config('marketplace.listing_expiry_days.seller'));
    }

    public function test_billing_model_helper_methods(): void
    {
        $this->assertFalse(BillingModel::isValid('invalid'));
        $this->assertSame(BillingModel::USAGE_DAILY, 'usage_daily');
    }

    public function test_dealer_invoice_status_includes_overdue(): void
    {
        $this->assertContains(DealerInvoiceStatus::OVERDUE, DealerInvoiceStatus::values());
    }

    public function test_start_billing_for_published_vehicles_skips_non_usage_plan(): void
    {
        $featureService = Mockery::mock(SubscriptionFeatureService::class);
        $featureService->shouldReceive('isUsageDailyPlan')->once()->andReturn(false);

        $service = new ListingBillingService($featureService);
        $dealer = new Dealer(['id' => 1]);

        $this->assertSame(0, $service->startBillingForPublishedVehicles($dealer));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
