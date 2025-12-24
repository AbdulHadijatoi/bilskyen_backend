<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'page_status_id',
    ];

    /**
     * Get page status for this page
     */
    public function pageStatus(): BelongsTo
    {
        return $this->belongsTo(PageStatus::class);
    }
}
