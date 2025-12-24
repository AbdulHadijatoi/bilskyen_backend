<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditActorType extends Model
{
    use HasFactory;

    public const ADMIN = 1;
    public const DEALER = 2;
    public const SYSTEM = 3;

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /**
     * Get audit logs with this actor type
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'audit_actor_type_id');
    }
}
