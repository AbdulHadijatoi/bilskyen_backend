<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleSpecDefinition extends Model
{
    protected $fillable = [
        'brand_id',
        'model_id',
        'variant_id',
        'model_year',
        'name',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'brand_id' => 'integer',
            'model_id' => 'integer',
            'variant_id' => 'integer',
            'model_year' => 'integer',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(DmrBrand::class);
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(DmrModel::class, 'model_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(DmrVariant::class);
    }
}
