<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SeoRedirect extends Model
{
    public const MATCH_EXACT = 'exact';

    public const MATCH_PREFIX = 'prefix';

    protected $fillable = [
        'from_path',
        'to_path',
        'match_type',
        'redirect_type',
        'is_active',
        'hit_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'redirect_type' => 'integer',
        'hit_count' => 'integer',
    ];

    protected $attributes = [
        'match_type' => self::MATCH_EXACT,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saved(fn () => Cache::forget('seo_redirects_map'));
        static::deleted(fn () => Cache::forget('seo_redirects_map'));
    }

    public static function normalizePath(string $path): string
    {
        $path = '/' . ltrim(parse_url($path, PHP_URL_PATH) ?? $path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
