<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GdprDataRequest extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'email',
        'type',
        'status',
        'download_path',
        'requested_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
