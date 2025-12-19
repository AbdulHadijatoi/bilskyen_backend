<?php

namespace App\Models;

use App\Traits\HasSerialNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory, HasSerialNumber;

    protected $fillable = [
        'serial_no',
        'type',
        'date',
        'narration',
        'remarks',
        'images',
    ];

    protected $casts = [
        'serial_no' => 'integer',
        'date' => 'date',
        'images' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get transaction entries
     */
    public function entries(): HasMany
    {
        return $this->hasMany(TransactionEntry::class);
    }

    /**
     * Get purchase linked to this transaction
     */
    public function purchase(): HasOne
    {
        return $this->hasOne(Purchase::class);
    }

    /**
     * Get sale linked to this transaction
     */
    public function sale(): HasOne
    {
        return $this->hasOne(Sale::class);
    }

    /**
     * Get expense linked to this transaction
     */
    public function expense(): HasOne
    {
        return $this->hasOne(Expense::class);
    }
}

