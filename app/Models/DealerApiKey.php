<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DealerApiKey extends Model
{
    protected $fillable = [
        'dealer_id',
        'name',
        'key_prefix',
        'key_hash',
        'permissions',
        'last_used_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'last_used_at' => 'datetime',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public static function generatePlainKey(): string
    {
        return 'bsk_'.Str::random(40);
    }

    public static function hashKey(string $plain): string
    {
        return hash('sha256', $plain);
    }
}
