<?php

namespace App\Models;

use App\Traits\FirstOrCreateInsensitive;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentType extends Model
{
    use FirstOrCreateInsensitive;
    use SoftDeletes;

    public $timestamps = false;

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
    ];

    /**
     * Get equipments for this equipment type
     */
    public function equipments(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }
}
