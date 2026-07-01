<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleImportBatch extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'dealer_id',
        'user_id',
        'original_filename',
        'file_path',
        'file_extension',
        'dry_run',
        'status',
        'summary',
        'rows',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'dry_run' => 'boolean',
        'summary' => 'array',
        'rows' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }
}
