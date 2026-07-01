<?php

namespace Tests\Unit;

use App\Constants\PaymentPurpose;
use App\Constants\PaymentStatus;
use Tests\TestCase;

class PaymentConstantsTest extends TestCase
{
    public function test_payment_status_values(): void
    {
        $this->assertContains(PaymentStatus::PENDING, PaymentStatus::values());
        $this->assertContains(PaymentStatus::SUCCEEDED, PaymentStatus::values());
    }

    public function test_payment_purpose_constants(): void
    {
        $this->assertSame('invoice', PaymentPurpose::INVOICE);
        $this->assertSame('subscription', PaymentPurpose::SUBSCRIPTION);
    }

    public function test_payments_config_has_panel_url_and_webhook_path(): void
    {
        $this->assertNotEmpty(config('payments.panel_url'));
        $this->assertSame('/api/v1/webhooks/stripe', config('payments.stripe_webhook_path'));
    }
}
