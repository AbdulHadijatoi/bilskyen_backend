<?php

namespace App\Contracts;

interface PaymentProviderInterface
{
    public function getName(): string;

    public function isEnabled(): bool;

    /**
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array;

    /**
     * @param  array<string, mixed>  $params
     * @return array{checkout_url: string, session_id: string}
     */
    public function createCheckoutSession(array $params): array;

    public function retrieveCheckoutSession(string $sessionId): object;

    /**
     * @return object Stripe Event
     */
    public function constructWebhookEvent(string $payload, string $signature): object;

    public function getPublishableKey(): ?string;
}
