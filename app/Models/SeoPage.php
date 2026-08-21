<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SeoService;
use Illuminate\Support\Facades\Cache;

class SeoPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_type',
        'page_key',
        'title',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'robots',
        'og_title',
        'og_description',
        'og_image',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'schema_type',
        'schema_json',
        'content_html',
        'faq_json',
        'breadcrumbs_json',
    ];

    protected $casts = [
        'schema_json' => 'array',
        'faq_json' => 'array',
        'breadcrumbs_json' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (SeoPage $model) {
            static::clearPageCache($model->page_type, $model->page_key);
            static::clearSitemapAndRobotsCache();
        });

        static::updated(function (SeoPage $model) {
            static::clearPageCache($model->page_type, $model->page_key);
            static::clearSitemapAndRobotsCache();
        });

        static::deleted(function (SeoPage $model) {
            static::clearPageCache($model->page_type, $model->page_key);
            static::clearSitemapAndRobotsCache();
        });
    }

    public static function getCacheKey(string $pageType, string $pageKey): string
    {
        return "seo:{$pageType}:{$pageKey}";
    }

    public static function clearPageCache(string $pageType, string $pageKey): void
    {
        Cache::forget(static::getCacheKey($pageType, $pageKey));
    }

    public static function clearSitemapAndRobotsCache(): void
    {
        SeoService::forgetPublicCaches();
    }
}
