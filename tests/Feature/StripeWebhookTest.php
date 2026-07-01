<?php

namespace Tests\Feature;

use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    public function test_webhook_rejects_missing_signature(): void
    {
        $response = $this->postJson('/api/v1/webhooks/stripe', [
            'type' => 'checkout.session.completed',
        ]);

        $response->assertStatus(400);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $response = $this->postJson(
            '/api/v1/webhooks/stripe',
            ['type' => 'checkout.session.completed'],
            ['Stripe-Signature' => 'invalid']
        );

        $response->assertStatus(400);
    }
}
