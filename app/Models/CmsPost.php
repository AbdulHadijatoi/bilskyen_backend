<?php

namespace App\Models;

use App\Constants\CmsPostStatus;
use App\Models\Concerns\HasCmsVersions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsPost extends Model
{
    use HasCmsVersions;

    protected $fillable = [
        'category_id',
        'author_user_id',
        'featured_media_id',
        'slug',
        'title',
        'excerpt',
        'content_html',
        'status',
        'published_at',
        'scheduled_at',
        'meta_title',
        'meta_description',
        'og_image',
        'robots',
        'canonical_url',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CmsPostCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(CmsMedia::class, 'featured_media_id');
    }

    public function isPublished(): bool
    {
        return $this->status === CmsPostStatus::PUBLISHED
            && $this->published_at
            && $this->published_at->isPast();
    }

    /**
     * @return array<string, mixed>
     */
    public function versionSnapshot(): array
    {
        return $this->only([
            'category_id', 'author_user_id', 'featured_media_id', 'slug', 'title',
            'excerpt', 'content_html', 'status', 'published_at', 'scheduled_at',
            'meta_title', 'meta_description', 'og_image', 'robots', 'canonical_url',
        ]);
    }
}
