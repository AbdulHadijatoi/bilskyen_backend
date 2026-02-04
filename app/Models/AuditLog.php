<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'actor_id',
        'audit_actor_type_id',
        'dealer_id',
        'action',
        'status',
        'error_message',
        'duration_ms',
        'request_method',
        'request_url',
        'target_type',
        'target_id',
        'related_target_type',
        'related_target_id',
        'description',
        'tags',
        'severity',
        'metadata',
        'ip_address',
        'user_agent',
        'session_id',
        'request_id',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'tags' => 'array',
        'duration_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Get audit actor type for this log
     */
    public function auditActorType(): BelongsTo
    {
        return $this->belongsTo(AuditActorType::class);
    }

    /**
     * Get dealer for this log (if applicable)
     */
    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }
}
