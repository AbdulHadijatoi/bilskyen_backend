<?php

namespace App\Models;

use App\Traits\HasSerialNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use HasFactory, HasSerialNumber;

    protected $fillable = [
        'serial_no',
        'sale_date',
        'sale_type',
        'payment_mode',
        'images',
        'vehicle_id',
        'contact_id',
        'received_to_financial_account_id',
        'transaction_id',
    ];

    protected $casts = [
        'serial_no' => 'integer',
        'sale_date' => 'date',
        'images' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get vehicle for this sale
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get contact for this sale
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get financial account received to
     */
    public function receivedToFinancialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'received_to_financial_account_id');
    }

    /**
     * Get transaction for this sale
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Calculate sale price from transaction entries
     */
    public function getSalePriceAttribute(): float
    {
        if (!$this->transaction) {
            return 0;
        }

        return $this->transaction->entries()
            ->where('type', 'credit')
            ->whereHas('financialAccount', function ($query) {
                $query->where('name', 'Sales Revenue');
            })
            ->sum('amount');
    }

    /**
     * Calculate received amount from transaction entries
     * Excludes Accounts Receivable and Cost of Goods Sold entries
     */
    public function getReceivedAmountAttribute(): float
    {
        if (!$this->transaction) {
            return 0;
        }

        return $this->transaction->entries()
            ->where('type', 'debit')
            ->where('financial_account_id', $this->received_to_financial_account_id)
            ->whereDoesntHave('financialAccount', function ($query) {
                $query->whereIn('name', ['Accounts Receivable', 'Cost of Goods Sold']);
            })
            ->sum('amount');
    }

    /**
     * Calculate outstanding amount
     */
    public function getOutstandingAmountAttribute(): float
    {
        return $this->sale_price - $this->received_amount;
    }

    /**
     * Calculate payment status
     */
    public function getPaymentStatusAttribute(): string
    {
        $salePrice = $this->sale_price;
        $receivedAmount = $this->received_amount;

        if ($receivedAmount >= $salePrice) {
            return 'Received';
        }

        if ($receivedAmount > 0) {
            return 'Partial';
        }

        return 'Due';
    }
}

