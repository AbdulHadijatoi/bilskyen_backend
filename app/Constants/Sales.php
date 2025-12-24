<?php

namespace App\Constants;

class Sales
{
    public const TYPES = [
        'Retail Customer',
        'Wholesale',
        'Dealer Trade',
        'Auction Sale',
        'Employee Purchase',
        'Lease Out',
        'Fleet Sale',
        'Export Sale',
        'Online Sale',
    ];

    public const STATUSES = [
        'Completed',
        'Pending',
        'Cancelled',
        'Returned',
        'Refunded',
        'Confirmed',
    ];
}


