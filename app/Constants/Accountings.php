<?php

namespace App\Constants;

class Accountings
{
    public const PAYMENT_MODES = [
        'Cash',
        'Bank Transfer',
        'Cheque',
        'Financing',
        'Credit',
        'MobilePay / Digital Wallet',
    ];

    public const ENTRY_TYPES = [
        'debit',
        'credit',
    ];

    public const FINANCIAL_ACCOUNT_TYPES = [
        'asset' => [
            'Current Asset',
            'Non-Current Asset',
            'Fixed Asset',
            'Tangible Asset',
            'Intangible Asset',
            'Financial Asset',
            'Investment Asset',
            'Other Asset',
        ],
        'liability' => [
            'Current Liability',
            'Non-Current Liability',
            'Long-Term Liability',
            'Short-Term Liability',
            'Contingent Liability',
            'Other Liability',
        ],
        'equity' => [
            "Owner's Equity",
            'Retained Earnings',
            'Share Capital',
            'Reserves',
            'Drawings',
            'Other Equity',
        ],
        'revenue' => [
            'Operating Revenue',
            'Non-Operating Revenue',
            'Sales Revenue',
            'Service Revenue',
            'Interest Income',
            'Investment Income',
            'Other Income',
        ],
        'expense' => [
            'Operating Expense',
            'Non-Operating Expense',
            'Cost of Sales',
            'Cost of Goods Sold',
            'Administrative Expense',
            'Selling Expense',
            'Depreciation Expense',
            'Finance Expense',
            'Other Expense',
        ],
    ];

    public const OPERATING_ACTIVITIES = [
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
        'Receipt',
        'Income',
        'Parts Sale',
        'Service Invoice',
        'Bank Receipt',
        'Service Estimate',
        'Interest Income',
        'Other',
    ];

    public const INVESTING_ACTIVITIES = [
        'Vehicle Purchase',
        'Vehicle Parts Purchase',
        'Vehicle Insurance',
        'Vehicle Registration',
        'Vehicle Parts Issue',
        'Depreciation',
        'Vehicle Sale',
        'Vehicle Booking',
        'Vehicle Delivery',
        'Vehicle Return',
        'Vehicle Parts Sale',
        'Trade-in Receipt',
        'Vehicle Service',
        'Vehicle Repair',
        'Vehicle Maintenance',
        'Vehicle Inspection',
        'Vehicle Parts Return',
    ];

    public const FINANCING_ACTIVITIES = [
        'Loan Repayment',
        'Business Loan to Owner',
        'Capital Withdrawal',
        'Drawings',
        'Capital Introduction',
        'Investment',
        'Opening Balance',
        'Loan Disbursement',
        'Owner Loan to Business',
        'Equity Adjustment',
        'Vehicle Loan',
        'Vehicle Lease',
        'Inter-branch Transfer',
        'Vehicle Warranty',
    ];

    /**
     * Combined transaction types
     */
    public static function getTransactionTypes(): array
    {
        return array_merge(
            self::INVESTING_ACTIVITIES,
            self::OPERATING_ACTIVITIES,
            self::FINANCING_ACTIVITIES,
            ['Transfer']
        );
    }

    public const FINANCIAL_OVERVIEW_PERIODS = [
        'week',
        'month',
        'quarter',
        'year',
    ];

    public const FINANCIAL_OVERVIEW_TYPES = [
        'Revenue',
        'Expense',
        'Net Profit',
        'Profit Margin',
    ];

    public const INVOICE_PAYMENT_STATUSES = [
        'Paid',
        'Partial',
        'Unpaid',
    ];

    public const RECEIPT_PAYMENT_STATUSES = [
        'Received',
        'Partial',
        'Due',
    ];
}

