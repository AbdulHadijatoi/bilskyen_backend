<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait ConstantsCacheTrait
{
    /**
     * Clear constants cache for a given constant type
     *
     * @param string $name The constant type name (e.g., 'types', 'brands', 'body_types')
     * @return void
     */
    protected function clearConstantsCache(string $name): void
    {
        Cache::forget("constants_{$name}");
    }
}
