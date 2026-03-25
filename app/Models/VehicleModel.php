<?php

namespace App\Models;

use App\Traits\FirstOrCreateInsensitive;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleModel extends Model
{
    use FirstOrCreateInsensitive;
    public $timestamps = false;
    protected $table = 'models';
    
    protected $fillable = [
        'brand_id',
        'name',
    ];

    /**
     * Get brand for this model
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}

