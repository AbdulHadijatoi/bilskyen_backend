<?php

namespace App\Helpers;

use App\Models\Dealer;

class DealerDisplayHelper
{
    /**
     * Determine whether a dealer should display a "Verified" badge.
     *
     * The dealers table currently has no verification field. This checks
     * defensively for common column names so a badge appears automatically
     * if verification is added later, without ever inventing false trust
     * signals in the meantime.
     *
     * @param Dealer|null $dealer
     * @return bool
     */
    public static function isDealerVerified(?Dealer $dealer): bool
    {
        if (!$dealer) {
            return false;
        }

        $attributes = $dealer->getAttributes();

        foreach (['is_verified', 'verified', 'verified_at'] as $field) {
            if (array_key_exists($field, $attributes) && !empty($attributes[$field])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a clean, comma-separated address line from a dealer's address
     * fields, omitting any parts that are empty instead of leaving stray
     * commas/placeholders (e.g. "Main St, " when city/postcode are missing).
     *
     * @param Dealer|null $dealer
     * @return string|null
     */
    public static function formatDealerAddressLine(?Dealer $dealer): ?string
    {
        if (!$dealer) {
            return null;
        }

        $address = trim((string) ($dealer->address ?? ''));
        $postcode = trim((string) ($dealer->postcode ?? ''));
        $city = trim((string) ($dealer->city ?? ''));

        $locality = trim($postcode . ' ' . $city);

        $segments = array_filter([$address, $locality], static fn (string $segment) => $segment !== '');

        if (empty($segments)) {
            return null;
        }

        return implode(', ', $segments);
    }
}
