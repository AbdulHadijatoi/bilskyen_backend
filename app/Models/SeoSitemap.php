<?php

namespace App\Models;

use App\Services\SeoService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoSitemap extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_type',
        'url',
        'priority',
        'changefreq',
        'lastmod',
    ];

    protected $casts = [
        'lastmod' => 'datetime',
        'priority' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::created(function () {
            SeoService::forgetPublicCaches();
        });

        static::updated(function () {
            SeoService::forgetPublicCaches();
        });

        static::deleted(function () {
            SeoService::forgetPublicCaches();
        });
    }
}
