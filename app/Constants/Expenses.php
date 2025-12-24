<?php

namespace App\Constants;

class Expenses
{
    public const OPERATING_EXPENSE_ACTIVITIES = [
        'Payment',
        'Expense',
        'Parts Purchase',
        'Service Job',
        'Customer Refund',
        'Refund',
        'Bank Payment',
        'Salary Payment',
        'Petty Cash',
        'Warranty Claim',
        'Free Service',
        'Parts Issue',
        'Parts Return',
        'Stock Adjustment',
        'Adjustment',
        'Provision',
        'Write-off',
        'Interest Expense',
    ];

    public const INVESTING_EXPENSE_ACTIVITIES = [
        'Vehicle Purchase',
        'Vehicle Parts Purchase',
        'Vehicle Insurance',
        'Vehicle Registration',
        'Vehicle Parts Issue',
        'Depreciation',
    ];

    public const FINANCING_EXPENSE_ACTIVITIES = [
        'Loan Repayment',
        'Business Loan to Owner',
        'Capital Withdrawal',
        'Drawings',
    ];

    /**
     * Combined expenses activities
     */
    public static function getAllActivities(): array
    {
        return array_merge(
            self::OPERATING_EXPENSE_ACTIVITIES,
            self::INVESTING_EXPENSE_ACTIVITIES,
            self::FINANCING_EXPENSE_ACTIVITIES
        );
    }
}


