<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerStaff extends Model
{
    use HasFactory;

    protected $fillable = [
        'dealer_id',
        'user_id',
        'username',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get dealer for this dealer staff
     */
    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    /**
     * Get user for this dealer staff
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
