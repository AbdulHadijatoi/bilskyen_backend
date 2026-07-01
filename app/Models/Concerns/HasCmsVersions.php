<?php

namespace App\Models\Concerns;

use App\Models\CmsContentVersion;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasCmsVersions
{
    public static function bootHasCmsVersions(): void
    {
        static::updated(function ($model) {
            if (method_exists($model, 'versionSnapshot')) {
                $model->recordVersion();
            }
        });
    }

    public function versions(): MorphMany
    {
        return $this->morphMany(CmsContentVersion::class, 'versionable')->orderByDesc('version_number');
    }

    public function recordVersion(?int $userId = null): CmsContentVersion
    {
        $next = (int) $this->versions()->max('version_number') + 1;

        return $this->versions()->create([
            'version_number' => $next,
            'snapshot' => $this->versionSnapshot(),
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    public function restoreVersion(int $versionId, ?int $userId = null): self
    {
        $version = $this->versions()->where('id', $versionId)->firstOrFail();
        $this->fill($version->snapshot);
        $this->save();

        return $this->fresh();
    }
}
