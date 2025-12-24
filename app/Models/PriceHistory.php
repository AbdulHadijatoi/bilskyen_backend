<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceHistory extends Model
{
    use HasFactory;

    protected $table = 'price_history';

    public $timestamps = false;

    protected $fillable = [
        'vehicle_id',
        'old_price',
        'new_price',
        'changed_by_user_id',
        'changed_at',
    ];

    protected $casts = [
        'old_price' => 'integer',
        'new_price' => 'integer',
        'changed_at' => 'datetime',
    ];

    /**
     * Get vehicle for this price history
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get user who changed the price
     */
    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
