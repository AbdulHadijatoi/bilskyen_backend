<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PageImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_name',
        'section_key',
        'image_path',
        'alt_text',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Append URLs to JSON output
     */
    protected $appends = [
        'image_url',
    ];

    /**
     * Get image URL attribute
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

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
        return "{$pageName}_page_images";
    }
}
