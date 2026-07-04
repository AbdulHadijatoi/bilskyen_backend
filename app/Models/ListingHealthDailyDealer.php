<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingHealthDailyDealer extends Model
{
    protected $table = 'listing_health_daily_dealer';

    protected $fillable = [
        'dealer_id',
        'date',
        'avg_score',
        'platform_avg_score',
        'attention_count',
        'published_count',
    ];

    protected $casts = [
        'date' => 'date',
        'avg_score' => 'integer',
        'platform_avg_score' => 'integer',
        'attention_count' => 'integer',
        'published_count' => 'integer',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }
}
