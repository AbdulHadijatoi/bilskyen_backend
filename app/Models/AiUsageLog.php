<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'dealer_id',
        'provider',
        'model',
        'task',
        'prompt_tokens',
        'completion_tokens',
        'context_type',
        'context_id',
        'status',
        'error_message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }
}
