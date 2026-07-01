<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Legacy `variants` table (admin). Public API / listings use {@see DmrVariant}.
 */
class Variant extends Model
{
    protected $fillable = [
        'name',
        'model_id',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }
}
