<?php

namespace App\Models;

use App\Traits\CachedLookup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Equipment extends Model
{
    // use CachedLookup;

    public $timestamps = false;

    protected $table = 'equipments';
    
    protected $fillable = [
        'name',
        'equipment_type_id',
    ];

    /**
     * Get equipment type for this equipment
     */
    public function equipmentType(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class);
    }

    /**
     * Get vehicles with this equipment
     */
    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, 'vehicle_equipment');
    }
}
