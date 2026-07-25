<?php

namespace App\Services;

use App\Helpers\DealerDisplayHelper;
use App\Models\Dealer;

class DealerVehicleAddressService
{
    /**
     * Force a dealer vehicle to use the address stored on the dealer profile.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function applyToPayload(array $payload, Dealer $dealer): array
    {
        $postcode = trim((string) ($dealer->postcode ?? ''));

        $payload['address'] = DealerDisplayHelper::formatDealerAddressLine($dealer);
        $payload['postcode'] = $postcode !== '' ? $postcode : null;

        return $payload;
    }
}
