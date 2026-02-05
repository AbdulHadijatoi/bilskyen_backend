<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TranslationKey extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'key',
        'default_value',
    ];

    /**
     * Get all translation values for this key
     */
    public function values(): HasMany
    {
        return $this->hasMany(TranslationValue::class);
    }

    /**
     * Get translation value for a specific locale
     */
    public function value(string $locale): ?TranslationValue
    {
        return $this->values()->where('locale', $locale)->first();
    }

    /**
     * Scope to search by key
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('key', 'like', "%{$search}%")
            ->orWhere('default_value', 'like', "%{$search}%");
    }
}
