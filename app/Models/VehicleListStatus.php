<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleListStatus extends Model
{
    use HasFactory;

    public const DRAFT = 1;
    public const PUBLISHED = 2;
    public const SOLD = 3;
    public const ARCHIVED = 4;

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /**
     * Get vehicles with this status
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'vehicle_list_status_id');
    }
}
