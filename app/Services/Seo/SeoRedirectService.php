<?php

namespace App\Services\Seo;

use App\Models\SeoRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SeoRedirectService
{
    /**
     * @return array{
     *     exact: array<string, array{to: string, type: int, id: int}>,
     *     prefix: list<array{from: string, to: string, type: int, id: int}>
     * }
     */
    public function activeMaps(): array
    {
        return Cache::remember('seo_redirects_map', 3600, function () {
            $exact = [];
            $prefix = [];

            foreach (SeoRedirect::where('is_active', true)->get() as $redirect) {
                $entry = [
                    'to' => $redirect->to_path,
                    'type' => (int) $redirect->redirect_type,
                    'id' => $redirect->id,
                ];

                if ($redirect->match_type === SeoRedirect::MATCH_PREFIX) {
                    $prefix[] = [
                        'from' => $redirect->from_path,
                        ...$entry,
                    ];
                } else {
                    $exact[$redirect->from_path] = $entry;
                }
            }

            usort($prefix, fn (array $a, array $b) => strlen($b['from']) <=> strlen($a['from']));

            return [
                'exact' => $exact,
                'prefix' => $prefix,
            ];
        });
    }

    /**
     * @deprecated Use activeMaps()
     * @return array<string, array{to: string, type: int, id: int}>
     */
    public function activeMap(): array
    {
        return $this->activeMaps()['exact'];
    }

    public function resolve(Request $request): ?SeoRedirect
    {
        $path = SeoRedirect::normalizePath($request->getPathInfo());
        $maps = $this->activeMaps();

        if (isset($maps['exact'][$path])) {
            return SeoRedirect::find($maps['exact'][$path]['id']);
        }

        foreach ($maps['prefix'] as $entry) {
            $from = $entry['from'];
            if ($path === $from || str_starts_with($path, $from.'/')) {
                return SeoRedirect::find($entry['id']);
            }
        }

        return null;
    }

    /**
     * Build the destination URL path for a matched redirect (path only, no host).
     */
    public function destinationPath(SeoRedirect $redirect, string $requestPath): string
    {
        $from = SeoRedirect::normalizePath($redirect->from_path);
        $to = $redirect->to_path;

        if (str_starts_with($to, 'http://') || str_starts_with($to, 'https://')) {
            if ($redirect->match_type !== SeoRedirect::MATCH_PREFIX) {
                return $to;
            }

            $parsed = parse_url($to);
            $toPath = SeoRedirect::normalizePath($parsed['path'] ?? '/');
            $suffix = $this->suffixAfterPrefix($requestPath, $from);
            $newPath = $suffix === '' ? $toPath : rtrim($toPath, '/').$suffix;
            $rebuilt = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '');
            if (! empty($parsed['port'])) {
                $rebuilt .= ':'.$parsed['port'];
            }
            $rebuilt .= $newPath;
            if (! empty($parsed['query'])) {
                $rebuilt .= '?'.$parsed['query'];
            }

            return $rebuilt;
        }

        $toPath = SeoRedirect::normalizePath($to);

        if ($redirect->match_type !== SeoRedirect::MATCH_PREFIX) {
            return $toPath;
        }

        $suffix = $this->suffixAfterPrefix($requestPath, $from);

        return $suffix === '' ? $toPath : rtrim($toPath, '/').$suffix;
    }

    private function suffixAfterPrefix(string $requestPath, string $from): string
    {
        $path = SeoRedirect::normalizePath($requestPath);
        if ($path === $from) {
            return '';
        }

        return substr($path, strlen($from)) ?: '';
    }

    public function recordHit(SeoRedirect $redirect): void
    {
        $redirect->increment('hit_count');
    }
}
