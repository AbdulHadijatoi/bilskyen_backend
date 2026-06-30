<?php

namespace App\Constants;

class ListingBillingPeriodStatus
{
    public const PENDING = 'pending';

    public const INVOICED = 'invoiced';

    public const WAIVED = 'waived';

    public static function values(): array
    {
        return [
            self::PENDING,
            self::INVOICED,
            self::WAIVED,
        ];
    }
}
