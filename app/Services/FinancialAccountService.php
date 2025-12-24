<?php

namespace App\Services;

use App\Models\FinancialAccount;
use Illuminate\Support\Facades\DB;

class FinancialAccountService
{
    /**
     * System-generated financial accounts
     */
    private const SYSTEM_ACCOUNTS = [
        [
            'name' => 'Vehicle Inventory',
            'type' => 'asset',
            'category' => 'Current Asset',
            'is_cash_account' => false,
            'is_system_generated' => true,
        ],
        [
            'name' => 'Accounts Receivable',
            'type' => 'asset',
            'category' => 'Current Asset',
            'is_cash_account' => false,
            'is_system_generated' => true,
        ],
        [
            'name' => 'Sales Revenue',
            'type' => 'revenue',
            'category' => 'Sales Revenue',
            'is_cash_account' => false,
            'is_system_generated' => true,
        ],
        [
            'name' => 'Cost of Goods Sold',
            'type' => 'expense',
            'category' => 'Cost of Sales',
            'is_cash_account' => false,
            'is_system_generated' => true,
        ],
        [
            'name' => 'Accounts Payable',
            'type' => 'liability',
            'category' => 'Current Liability',
            'is_cash_account' => false,
            'is_system_generated' => true,
        ],
        [
            'name' => 'Accumulated Depreciation',
            'type' => 'asset',
            'category' => 'Fixed Asset',
            'is_cash_account' => false,
            'is_system_generated' => true,
        ],
        [
            'name' => 'Loan Payable',
            'type' => 'liability',
            'category' => 'Long-Term Liability',
            'is_cash_account' => false,
            'is_system_generated' => true,
        ],
        [
            'name' => "Owner's Equity",
            'type' => 'equity',
            'category' => "Owner's Equity",
            'is_cash_account' => false,
            'is_system_generated' => true,
        ],
        [
            'name' => 'Operating Expense',
            'type' => 'expense',
            'category' => 'Operating Expense',
            'is_cash_account' => false,
            'is_system_generated' => true,
        ],
    ];

    /**
     * Get or create a system account by name
     */
    public function getOrCreateSystemAccount(string $name): FinancialAccount
    {
        $accountData = collect(self::SYSTEM_ACCOUNTS)->firstWhere('name', $name);

        if (!$accountData) {
            throw new \Exception("System account '{$name}' not found");
        }

        return FinancialAccount::firstOrCreate(
            ['name' => $name],
            $accountData
        );
    }

    /**
     * Get or create vehicle inventory account
     */
    public function getOrCreateVehicleInventoryAccount(): FinancialAccount
    {
        return $this->getOrCreateSystemAccount('Vehicle Inventory');
    }

    /**
     * Get or create accounts payable account
     */
    public function getOrCreateAccountsPayableAccount(): FinancialAccount
    {
        return $this->getOrCreateSystemAccount('Accounts Payable');
    }

    /**
     * Get or create accounts receivable account
     */
    public function getOrCreateAccountsReceivableAccount(): FinancialAccount
    {
        return $this->getOrCreateSystemAccount('Accounts Receivable');
    }

    /**
     * Get or create sales revenue account
     */
    public function getOrCreateSalesRevenueAccount(): FinancialAccount
    {
        return $this->getOrCreateSystemAccount('Sales Revenue');
    }

    /**
     * Get or create cost of goods sold account
     */
    public function getOrCreateCostOfGoodsSoldAccount(): FinancialAccount
    {
        return $this->getOrCreateSystemAccount('Cost of Goods Sold');
    }

    /**
     * Get or create accumulated depreciation account
     */
    public function getOrCreateAccumulatedDepreciationAccount(): FinancialAccount
    {
        return $this->getOrCreateSystemAccount('Accumulated Depreciation');
    }

    /**
     * Get or create loan payable account
     */
    public function getOrCreateLoanPayableAccount(): FinancialAccount
    {
        return $this->getOrCreateSystemAccount('Loan Payable');
    }

    /**
     * Get or create owner equity account
     */
    public function getOrCreateOwnerEquityAccount(): FinancialAccount
    {
        return $this->getOrCreateSystemAccount("Owner's Equity");
    }

    /**
     * Get or create operating expense account
     */
    public function getOrCreateOperatingExpenseAccount(): FinancialAccount
    {
        return $this->getOrCreateSystemAccount('Operating Expense');
    }

    /**
     * Validate financial account exists
     */
    public function validateAccount(int $accountId): FinancialAccount
    {
        $account = FinancialAccount::find($accountId);

        if (!$account) {
            throw new \Exception('Financial account not found');
        }

        return $account;
    }

    /**
     * Check if account can be deleted (no transactions)
     */
    public function canDeleteAccount(FinancialAccount $account): bool
    {
        return $account->transactionEntries()->count() === 0;
    }
}


