<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleListStatus extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const DRAFT = 1;
    public const PUBLISHED = 2;
    public const SOLD = 3;
    public const ARCHIVED = 4;
    public const PENDING_REVIEW = 5;

    public $timestamps = false;

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
    ];

    /**
     * Get vehicles with this status
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'list_status_id');
    }
}
