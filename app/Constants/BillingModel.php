<?php

namespace App\Constants;

class BillingModel
{
    public const SUBSCRIPTION = 'subscription';

    public const USAGE_DAILY = 'usage_daily';

    public static function values(): array
    {
        return [
            self::SUBSCRIPTION,
            self::USAGE_DAILY,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }
}
