<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DmrModel extends Model
{
    use SoftDeletes;

    protected $table = 'dmr_models';

    public $timestamps = false;

    protected $fillable = [
        'brand_id',
        'name',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(DmrBrand::class, 'brand_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(DmrVariant::class, 'model_id');
    }
}
