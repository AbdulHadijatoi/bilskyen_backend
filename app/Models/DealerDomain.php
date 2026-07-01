<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DealerDomain extends Model
{
    protected $fillable = [
        'dealer_id',
        'domain',
        'verification_token',
        'verified_at',
        'is_primary',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'is_primary' => 'boolean',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public static function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');

        return $domain;
    }

    public static function generateVerificationToken(): string
    {
        return Str::random(48);
    }
}
