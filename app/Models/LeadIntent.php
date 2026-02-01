<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadIntent extends Model
{
    use HasFactory;

    public const LOW = 1;
    public const MEDIUM = 2;
    public const HIGH = 3;
    public const VERY_HIGH = 4;

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /**
     * Get leads with this intent
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'lead_intent_id');
    }
}
