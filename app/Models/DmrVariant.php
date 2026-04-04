<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DmrVariant extends Model
{
    use SoftDeletes;

    protected $table = 'dmr_variants';

    public $timestamps = false;

    protected $fillable = [
        'model_id',
        'name',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(DmrModel::class, 'model_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(DmrFactVehicle::class, 'variant_id');
    }
}
