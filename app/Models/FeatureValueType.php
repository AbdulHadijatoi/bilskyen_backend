<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureValueType extends Model
{
    use HasFactory;

    public const BOOLEAN = 1;
    public const NUMBER = 2;
    public const TEXT = 3;

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /**
     * Get features with this value type
     */
    public function features(): HasMany
    {
        return $this->hasMany(Feature::class, 'feature_value_type_id');
    }
}
