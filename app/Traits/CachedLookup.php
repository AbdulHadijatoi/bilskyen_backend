<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;

trait CachedLookup
{
    /**
     * Cache TTL in seconds (24 hours)
     */
    protected static int $cacheTtl = 86400;

    /**
     * Boot the trait
     */
    protected static function bootCachedLookup(): void
    {
        // Clear cache when model is created, updated, or deleted
        static::created(function ($model) {
            static::clearCache();
        });

        static::updated(function ($model) {
            static::clearCache();
        });

        static::deleted(function ($model) {
            static::clearCache();
        });
    }

    /**
     * Get the cache key prefix for this model
     */
    protected static function getCachePrefix(): string
    {
        $table = (new static)->getTable();
        return "lookup_{$table}";
    }

    /**
     * Clear all cache for this model
     */
    public static function clearCache(): void
    {
        $prefix = static::getCachePrefix();
        
        // If using cache tags (Redis/Memcached)
        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags([$prefix])->flush();
        } else {
            // For non-tagged cache stores, clear common patterns
            // Note: This won't clear all possible cache keys, but covers most cases
            // For production, consider using Redis with tags for better cache management
            Cache::forget($prefix . '_all');
        }
    }

    /**
     * Create a new Eloquent query builder for the model
     * This allows us to intercept get() calls and cache them
     */
    public function newEloquentBuilder($query)
    {
        return new class($query, static::getCachePrefix(), static::$cacheTtl) extends Builder {
            protected string $cachePrefix;
            protected int $cacheTtl;

            public function __construct($query, string $cachePrefix, int $cacheTtl)
            {
                parent::__construct($query);
                $this->cachePrefix = $cachePrefix;
                $this->cacheTtl = $cacheTtl;
            }

            /**
             * Execute the query as a "select" statement and cache the results
             */
            public function get($columns = ['*'])
            {
                // Only cache simple queries
                if (!$this->isCacheableQuery()) {
                    return parent::get($columns);
                }

                $cacheKey = $this->getCacheKey($columns);
                
                return Cache::remember($cacheKey, $this->cacheTtl, function () use ($columns) {
                    return parent::get($columns);
                });
            }

            /**
             * Determine if this query is cacheable
             */
            protected function isCacheableQuery(): bool
            {
                $query = $this->getQuery();
                
                // Don't cache if there are joins
                if (!empty($query->joins)) {
                    return false;
                }

                // Don't cache LIKE queries with wildcards (search queries)
                foreach ($query->wheres ?? [] as $where) {
                    if (isset($where['type']) && $where['type'] === 'Basic' && 
                        isset($where['operator']) && $where['operator'] === 'like' &&
                        isset($where['value']) && str_contains($where['value'], '%')) {
                        return false;
                    }
                }

                return true;
            }

            /**
             * Generate a cache key based on the query
             */
            protected function getCacheKey($columns): string
            {
                $query = $this->getQuery();
                
                $key = md5(serialize([
                    'table' => $query->from,
                    'wheres' => $query->wheres ?? [],
                    'orders' => $query->orders ?? [],
                    'columns' => $columns,
                    'limit' => $query->limit,
                    'offset' => $query->offset,
                ]));

                return "{$this->cachePrefix}_query_{$key}";
            }
        };
    }

    /**
     * Get all records (cached)
     */
    public static function all($columns = ['*'])
    {
        $cacheKey = static::getCachePrefix() . '_all';
        
        return Cache::remember($cacheKey, static::$cacheTtl, function () use ($columns) {
            return parent::all($columns);
        });
    }

    /**
     * Find a model by its primary key (cached)
     */
    public static function find($id, $columns = ['*'])
    {
        if ($id === null) {
            return null;
        }

        $cacheKey = static::getCachePrefix() . "_find_{$id}";
        
        return Cache::remember($cacheKey, static::$cacheTtl, function () use ($id, $columns) {
            return parent::find($id, $columns);
        });
    }
}

