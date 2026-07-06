<?php

namespace App\Services\Branding;

use App\Models\Dealer;

class BrandedInventoryAuditService
{
    public function shareUrl(Dealer $dealer): string
    {
        $slug = trim((string) $dealer->slug);
        if ($slug === '') {
            throw new \RuntimeException(__('messages.errors.dealer_slug_required_audit'));
        }

        return url('/inventory-audit/'.$slug);
    }

    public function brandingPayload(Dealer $dealer): array
    {
        $dealer->loadMissing('owner');

        return [
            'dealer_name' => $dealer->owner?->name ?? $dealer->slug,
            'logo_url' => $dealer->logo_url,
            'primary_color' => $dealer->theme_primary_color ?: '#2563eb',
            'secondary_color' => $dealer->theme_secondary_color ?: '#1e40af',
            'share_url' => $this->shareUrl($dealer),
        ];
    }
}
