<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class PlanAvailability extends Model
{
    use HasFactory;

    protected $table = 'plan_availability';

    public $timestamps = false;

    protected $fillable = [
        'plan_id',
        'allowed_role_id',
        'is_enabled',
        'created_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Get plan for this availability rule
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get allowed role for this availability rule
     */
    public function allowedRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'allowed_role_id');
    }
}
