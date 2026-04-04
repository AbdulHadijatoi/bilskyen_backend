<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DmrBrand extends Model
{
    use SoftDeletes;

    protected $table = 'dmr_brands';

    public $timestamps = false;

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
    ];

    public function models(): HasMany
    {
        return $this->hasMany(DmrModel::class, 'brand_id');
    }
}
