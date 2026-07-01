<?php

namespace App\Constants;

class PaymentStatus
{
    public const PENDING = 'pending';

    public const SUCCEEDED = 'succeeded';

    public const FAILED = 'failed';

    public const CANCELLED = 'cancelled';

    public const REFUNDED = 'refunded';

    public static function values(): array
    {
        return [
            self::PENDING,
            self::SUCCEEDED,
            self::FAILED,
            self::CANCELLED,
            self::REFUNDED,
        ];
    }
}
