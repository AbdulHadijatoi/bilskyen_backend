<?php

namespace App\Models;

use App\Constants\CmsPostStatus;
use App\Models\Concerns\HasCmsVersions;
use Illuminate\Database\Eloquent\Model;

class LandingPage extends Model
{
    use HasCmsVersions;

    protected $fillable = [
        'slug',
        'title',
        'blocks',
        'status',
        'published_at',
        'scheduled_at',
        'meta_title',
        'meta_description',
        'canonical_url',
        'robots',
        'og_title',
        'og_description',
        'og_image',
    ];

    protected $casts = [
        'blocks' => 'array',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

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
            'slug', 'title', 'blocks', 'status', 'published_at', 'scheduled_at',
            'meta_title', 'meta_description', 'canonical_url', 'robots',
            'og_title', 'og_description', 'og_image',
        ]);
    }
}
