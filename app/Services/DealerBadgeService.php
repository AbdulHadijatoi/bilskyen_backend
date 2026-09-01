<?php

namespace App\Services;

use App\Models\Dealer;

class DealerBadgeService
{
    public function __construct(
        private SubscriptionFeatureService $subscriptionFeatureService,
    ) {}

    public function hasPremiumBadge(?Dealer $dealer): bool
    {
        if (! $dealer) {
            return false;
        }

        return $this->subscriptionFeatureService->hasFeature($dealer, 'premium_dealer_badge');
    }

    public function hasTrustBadge(?Dealer $dealer): bool
    {
        if (! $dealer) {
            return false;
        }

        return $this->subscriptionFeatureService->hasFeature($dealer, 'dealer_trust_badge');
    }

    public function listingBadgeLabel(?Dealer $dealer): string
    {
        $custom = trim((string) ($dealer?->listing_badge_label ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        return (string) __('messages.pages.vehicles.dealer');
    }

    /**
     * @return array<string, bool>
     */
    public function badgesForDealer(?Dealer $dealer): array
    {
        return [
            'premium_dealer_badge' => $this->hasPremiumBadge($dealer),
            'dealer_trust_badge' => $this->hasTrustBadge($dealer),
        ];
    }
}
