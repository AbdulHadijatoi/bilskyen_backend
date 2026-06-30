<?php

namespace App\Constants;

class DealerInvoiceStatus
{
    public const DRAFT = 'draft';

    public const SENT = 'sent';

    public const PAID = 'paid';

    public const OVERDUE = 'overdue';

    public static function values(): array
    {
        return [
            self::DRAFT,
            self::SENT,
            self::PAID,
            self::OVERDUE,
        ];
    }
}
