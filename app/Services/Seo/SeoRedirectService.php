<?php

namespace App\Services\Seo;

use App\Models\SeoRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SeoRedirectService
{
    /**
     * @return array<string, array{to: string, type: int}>
     */
    public function activeMap(): array
    {
        return Cache::remember('seo_redirects_map', 3600, function () {
            $map = [];
            foreach (SeoRedirect::where('is_active', true)->get() as $redirect) {
                $map[$redirect->from_path] = [
                    'to' => $redirect->to_path,
                    'type' => (int) $redirect->redirect_type,
                    'id' => $redirect->id,
                ];
            }

            return $map;
        });
    }

    public function resolve(Request $request): ?SeoRedirect
    {
        $path = SeoRedirect::normalizePath($request->getPathInfo());
        $map = $this->activeMap();

        if (! isset($map[$path])) {
            return null;
        }

        return SeoRedirect::find($map[$path]['id']);
    }

    public function recordHit(SeoRedirect $redirect): void
    {
        $redirect->increment('hit_count');
    }
}
