<?php

namespace Tests\Unit;

use App\Models\Dealer;
use App\Services\DealerInvoiceService;
use Tests\TestCase;

class MarketplaceIntegrationTest extends TestCase
{
    public function test_blocking_invoice_check_respects_config_disable(): void
    {
        config(['marketplace.block_publish_on_overdue_invoice' => false]);

        $dealer = new Dealer;
        $dealer->id = 1;

        $this->assertFalse(app(DealerInvoiceService::class)->dealerHasBlockingInvoice($dealer));
    }
}
