<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PageContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_name',
        'section_key',
        'content',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Clear cache when model is created, updated, or deleted
        static::created(function ($model) {
            static::clearCache($model->page_name);
        });

        static::updated(function ($model) {
            static::clearCache($model->page_name);
        });

        static::deleted(function ($model) {
            static::clearCache($model->page_name);
        });
    }

    /**
     * Clear cache for a specific page
     */
    public static function clearCache(string $pageName = 'home'): void
    {
        $cacheKey = static::getCacheKey($pageName);
        Cache::forget($cacheKey);
    }

    /**
     * Get cache key for a page
     */
    public static function getCacheKey(string $pageName = 'home'): string
    {
        return "{$pageName}_page_content";
    }
}
