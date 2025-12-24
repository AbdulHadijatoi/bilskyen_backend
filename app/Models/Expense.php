<?php

namespace App\Models;

use App\Traits\HasSerialNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory, HasSerialNumber;

    protected $fillable = [
        'serial_no',
        'date',
        'narration',
        'category',
        'payment_mode',
        'paid_amount',
        'total_amount',
        'paid_from_financial_account_id',
        'vehicle_id',
        'contact_id',
        'transaction_id',
        'remarks',
        'images',
    ];

    protected $casts = [
        'serial_no' => 'integer',
        'date' => 'date',
        'paid_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'images' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get vehicle for this expense
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get contact for this expense
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
     * Get transaction for this expense
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Calculate payment status
     */
    public function getPaymentStatusAttribute(): string
    {
        if ($this->paid_amount >= $this->total_amount) {
            return 'Paid';
        }

        if ($this->paid_amount > 0) {
            return 'Partial';
        }

        return 'Unpaid';
    }
}


