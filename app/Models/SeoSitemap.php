<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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
            Cache::forget('sitemap_xml');
        });

        static::updated(function () {
            Cache::forget('sitemap_xml');
        });

        static::deleted(function () {
            Cache::forget('sitemap_xml');
        });
    }
}
