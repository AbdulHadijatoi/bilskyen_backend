<?php

namespace App\Constants;

/**
 * Shared bounds for public vehicle search price / mileage range controls.
 */
class VehicleSearchFilters
{
    public const PRICE_MIN = 0;

    public const PRICE_MAX = 1_000_000;

    public const KM_MIN = 0;

    public const KM_MAX = 500_000;
}
