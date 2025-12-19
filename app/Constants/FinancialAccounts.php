<?php

namespace App\Constants;

class FinancialAccounts
{
    public const VEHICLE_INVENTORY_ACCOUNT = [
        'name' => 'Vehicle Inventory',
        'type' => 'asset',
        'category' => 'Current Asset',
        'is_cash_account' => false,
        'is_system_generated' => true,
    ];

    public const ACCOUNTS_PAYABLE_ACCOUNT = [
        'name' => 'Accounts Payable',
        'type' => 'liability',
        'category' => 'Current Liability',
        'is_cash_account' => false,
        'is_system_generated' => true,
    ];

    public const ACCOUNTS_RECEIVABLE_ACCOUNT = [
        'name' => 'Accounts Receivable',
        'type' => 'asset',
        'category' => 'Current Asset',
        'is_cash_account' => false,
        'is_system_generated' => true,
    ];

    public const SALES_REVENUE_ACCOUNT = [
        'name' => 'Sales Revenue',
        'type' => 'revenue',
        'category' => 'Sales Revenue',
        'is_cash_account' => false,
        'is_system_generated' => true,
    ];

    public const COST_OF_GOODS_SOLD_ACCOUNT = [
        'name' => 'Cost of Goods Sold',
        'type' => 'expense',
        'category' => 'Cost of Sales',
        'is_cash_account' => false,
        'is_system_generated' => true,
    ];

    public const OPERATING_EXPENSE_ACCOUNT = [
        'name' => 'Operating Expense',
        'type' => 'expense',
        'category' => 'Operating Expense',
        'is_cash_account' => false,
        'is_system_generated' => true,
    ];

    public const ACCUMULATED_DEPRECIATION_ACCOUNT = [
        'name' => 'Accumulated Depreciation',
        'type' => 'asset',
        'category' => 'Fixed Asset',
        'is_cash_account' => false,
        'is_system_generated' => true,
    ];

    public const LOAN_PAYABLE_ACCOUNT = [
        'name' => 'Loan Payable',
        'type' => 'liability',
        'category' => 'Long-Term Liability',
        'is_cash_account' => false,
        'is_system_generated' => true,
    ];

    public const OWNER_EQUITY_ACCOUNT = [
        'name' => "Owner's Equity",
        'type' => 'equity',
        'category' => "Owner's Equity",
        'is_cash_account' => false,
        'is_system_generated' => true,
    ];
}

