<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /**
     * Source name constants
     */
    public const WEBSITE = 'Website';
    public const MOBILE_APP = 'Mobile App';
    public const PHONE = 'Phone';
    public const EMAIL = 'Email';
    public const REFERRAL = 'Referral';
    public const SOCIAL_MEDIA = 'Social Media';
    public const WALK_IN = 'Walk-in';

    /**
     * Get leads from this source
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'source_id');
    }
}
