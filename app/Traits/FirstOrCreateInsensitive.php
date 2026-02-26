<?php

namespace App\Traits;

trait FirstOrCreateInsensitive
{
    /**
     * Find the first record matching the attributes (with case-insensitive comparison on "name"),
     * or create it if none exists.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     * @return static
     */
    public static function firstOrCreateInsensitive(array $attributes, array $values = []): static
    {
        $query = static::query();

        foreach ($attributes as $key => $value) {
            if ($key === 'name') {
                $query->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $value)]);
            } else {
                $query->where($key, $value);
            }
        }

        $instance = $query->first();

        if ($instance !== null) {
            return $instance;
        }

        return static::create($attributes + $values);
    }
}
