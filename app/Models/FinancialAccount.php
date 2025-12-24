<?php

namespace App\Models;

use App\Traits\HasSerialNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialAccount extends Model
{
    use HasFactory, HasSerialNumber;

    protected $fillable = [
        'serial_no',
        'name',
        'type',
        'category',
        'is_cash_account',
        'is_system_generated',
    ];

    protected $casts = [
        'serial_no' => 'integer',
        'is_cash_account' => 'boolean',
        'is_system_generated' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get transaction entries for this account
     */
    public function transactionEntries(): HasMany
    {
        return $this->hasMany(TransactionEntry::class);
    }

    /**
     * Get purchases paid from this account
     */
    public function purchasesPaidFrom(): HasMany
    {
        return $this->hasMany(Purchase::class, 'paid_from_financial_account_id');
    }

    /**
     * Get sales received to this account
     */
    public function salesReceivedTo(): HasMany
    {
        return $this->hasMany(Sale::class, 'received_to_financial_account_id');
    }

    /**
     * Get expenses paid from this account
     */
    public function expensesPaidFrom(): HasMany
    {
        return $this->hasMany(Expense::class, 'paid_from_financial_account_id');
    }

    /**
     * Calculate balance for this account
     */
    public function getBalanceAttribute(): float
    {
        $debits = $this->transactionEntries()
            ->where('type', 'debit')
            ->sum('amount');

        $credits = $this->transactionEntries()
            ->where('type', 'credit')
            ->sum('amount');

        // Assets and Expenses: Debits - Credits
        // Liabilities, Equity, Revenue: Credits - Debits
        if (in_array($this->type, ['asset', 'expense'])) {
            return $debits - $credits;
        }

        return $credits - $debits;
    }

    /**
     * Get transactions count attribute
     */
    public function getTransactionsCountAttribute(): int
    {
        return $this->transactionEntries()->count();
    }
}


