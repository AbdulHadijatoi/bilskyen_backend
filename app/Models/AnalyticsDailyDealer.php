<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsDailyDealer extends Model
{
    protected $table = 'analytics_daily_dealer';

    protected $fillable = [
        'dealer_id',
        'date',
        'views_count',
        'enquiries_count',
        'leads_count',
        'leads_won_count',
        'vehicles_published',
        'vehicles_sold',
        'payg_cents',
        'payment_cents',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'views_count' => 'integer',
            'enquiries_count' => 'integer',
            'leads_count' => 'integer',
            'leads_won_count' => 'integer',
            'vehicles_published' => 'integer',
            'vehicles_sold' => 'integer',
            'payg_cents' => 'integer',
            'payment_cents' => 'integer',
        ];
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }
}
