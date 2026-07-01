<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsDailyPlatform extends Model
{
    protected $table = 'analytics_daily_platform';

    protected $fillable = [
        'date',
        'views_count',
        'enquiries_count',
        'leads_count',
        'leads_won_count',
        'vehicles_published',
        'vehicles_sold',
        'active_dealers',
        'new_dealers',
        'payments_succeeded',
        'payments_failed',
        'payment_volume_cents',
        'ai_requests',
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
            'active_dealers' => 'integer',
            'new_dealers' => 'integer',
            'payments_succeeded' => 'integer',
            'payments_failed' => 'integer',
            'payment_volume_cents' => 'integer',
            'ai_requests' => 'integer',
        ];
    }
}
