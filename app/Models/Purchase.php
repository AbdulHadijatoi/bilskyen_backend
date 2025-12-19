<?php

namespace App\Models;

use App\Traits\HasSerialNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    use HasFactory, HasSerialNumber;

    protected $fillable = [
        'serial_no',
        'purchase_date',
        'purchase_type',
        'payment_mode',
        'images',
        'vehicle_id',
        'contact_id',
        'paid_from_financial_account_id',
        'transaction_id',
    ];

    protected $casts = [
        'serial_no' => 'integer',
        'purchase_date' => 'date',
        'images' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get vehicle for this purchase
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get contact for this purchase
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get financial account paid from
     */
    public function paidFromFinancialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'paid_from_financial_account_id');
    }

    /**
     * Get transaction for this purchase
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Calculate purchase price from transaction entries
     */
    public function getPurchasePriceAttribute(): float
    {
        if (!$this->transaction) {
            return 0;
        }

        return $this->transaction->entries()
            ->where('type', 'debit')
            ->whereHas('financialAccount', function ($query) {
                $query->where('name', 'Vehicle Inventory');
            })
            ->sum('amount');
    }

    /**
     * Calculate paid amount from transaction entries
     * Excludes Accounts Payable entries
     */
    public function getPaidAmountAttribute(): float
    {
        if (!$this->transaction) {
            return 0;
        }

        return $this->transaction->entries()
            ->where('type', 'credit')
            ->where('financial_account_id', $this->paid_from_financial_account_id)
            ->whereDoesntHave('financialAccount', function ($query) {
                $query->where('name', 'Accounts Payable');
            })
            ->sum('amount');
    }

    /**
     * Calculate payment status
     */
    public function getPaymentStatusAttribute(): string
    {
        $purchasePrice = $this->purchase_price;
        $paidAmount = $this->paid_amount;

        if ($paidAmount >= $purchasePrice) {
            return 'Paid';
        }

        if ($paidAmount > 0) {
            return 'Partial';
        }

        return 'Unpaid';
    }
}

