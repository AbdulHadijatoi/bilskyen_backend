<?php

namespace App\Support;

use App\Models\User;
use App\Models\Vehicle;
use App\Services\DealerContextService;

final class VehicleListingAccess
{
    /**
     * Public PDP for published/sold; unpublished only for the owner or matching dealer staff.
     */
    public static function canViewWebPdp(?User $user, Vehicle $vehicle, DealerContextService $dealers): bool
    {
        if ($vehicle->isPubliclyViewable()) {
            return true;
        }
        if ($user === null) {
            return false;
        }
        if ((int) $user->id === (int) $vehicle->user_id) {
            return true;
        }
        if (! $vehicle->dealer_id) {
            return false;
        }
        $dealer = $dealers->getCurrentDealer($user);

        return $dealer !== null && (int) $dealer->id === (int) $vehicle->dealer_id;
    }
}
