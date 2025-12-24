<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageStatus extends Model
{
    use HasFactory;

    public const DRAFT = 1;
    public const PUBLISHED = 2;

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /**
     * Get pages with this status
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class, 'page_status_id');
    }
}
