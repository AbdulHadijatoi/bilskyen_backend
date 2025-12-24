<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadStage extends Model
{
    use HasFactory;

    public const NEW = 1;
    public const CONTACTED = 2;
    public const QUALIFIED = 3;
    public const QUOTED = 4;
    public const NEGOTIATING = 5;
    public const WON = 6;
    public const LOST = 7;

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /**
     * Get leads in this stage
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'lead_stage_id');
    }
}
