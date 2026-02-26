<?php

namespace App\Models;

use App\Traits\FirstOrCreateInsensitive;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentType extends Model
{
    use FirstOrCreateInsensitive;
    public $timestamps = false;

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
