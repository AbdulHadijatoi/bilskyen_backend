<?php

namespace App\Services\Seo;

use App\Constants\CmsPostStatus;
use App\Models\CmsMedia;
use App\Models\CmsPost;
use App\Models\LandingPage;
use App\Models\SeoPage;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SeoAuditService
{
    /**
     * @return array{issues: list<array<string, mixed>>, summary: array<string, int>}
     */
    public function run(): array
    {
        $issues = [];

        $this->auditSeoPages($issues);
        $this->auditPosts($issues);
        $this->auditLandingPages($issues);
        $this->auditMediaAltText($issues);
        $this->auditDuplicateMetaTitles($issues);
        $this->auditInternalLinks($issues);

        $summary = [
            'total' => count($issues),
            'error' => count(array_filter($issues, fn ($i) => $i['severity'] === 'error')),
            'warning' => count(array_filter($issues, fn ($i) => $i['severity'] === 'warning')),
            'info' => count(array_filter($issues, fn ($i) => $i['severity'] === 'info')),
        ];

        return compact('issues', 'summary');
    }

    /**
     * @param  list<array<string, mixed>>  $issues
     */
    private function auditSeoPages(array &$issues): void
    {
        foreach (SeoPage::all() as $page) {
            $ref = "{$page->page_type}/{$page->page_key}";
            if (empty($page->meta_title)) {
                $issues[] = $this->issue('error', 'missing_meta_title', "SEO page {$ref} is missing meta title", $ref);
            }
            if (empty($page->meta_description)) {
                $issues[] = $this->issue('warning', 'missing_meta_description', "SEO page {$ref} is missing meta description", $ref);
            }
            if (empty($page->canonical_url)) {
                $issues[] = $this->issue('info', 'missing_canonical', "SEO page {$ref} has no canonical override", $ref);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $issues
     */
    private function auditPosts(array &$issues): void
    {
        foreach (CmsPost::where('status', CmsPostStatus::PUBLISHED)->get() as $post) {
            $ref = "post/{$post->slug}";
            if (empty($post->meta_title) && empty($post->title)) {
                $issues[] = $this->issue('error', 'missing_meta_title', "Blog post {$post->slug} has no title/meta title", $ref);
            }
            if (empty($post->meta_description) && empty($post->excerpt)) {
                $issues[] = $this->issue('warning', 'missing_meta_description', "Blog post {$post->slug} has no meta description or excerpt", $ref);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $issues
     */
    private function auditLandingPages(array &$issues): void
    {
        foreach (LandingPage::where('status', CmsPostStatus::PUBLISHED)->get() as $page) {
            $ref = "landing/{$page->slug}";
            if (empty($page->meta_title) && empty($page->title)) {
                $issues[] = $this->issue('error', 'missing_meta_title', "Landing page {$page->slug} has no title/meta title", $ref);
            }
            if (empty($page->meta_description)) {
                $issues[] = $this->issue('warning', 'missing_meta_description', "Landing page {$page->slug} is missing meta description", $ref);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $issues
     */
    private function auditMediaAltText(array &$issues): void
    {
        foreach (CmsMedia::whereNull('alt_text')->orWhere('alt_text', '')->get() as $media) {
            $issues[] = $this->issue('warning', 'missing_alt_text', "Media #{$media->id} ({$media->filename}) is missing alt text", "media/{$media->id}");
        }
    }

    /**
     * @param  list<array<string, mixed>>  $issues
     */
    private function auditDuplicateMetaTitles(array &$issues): void
    {
        $titles = [];
        foreach (SeoPage::whereNotNull('meta_title')->get() as $page) {
            $key = Str::lower(trim($page->meta_title));
            if ($key === '') {
                continue;
            }
            $titles[$key][] = "{$page->page_type}/{$page->page_key}";
        }
        foreach (CmsPost::whereNotNull('meta_title')->get() as $post) {
            $key = Str::lower(trim($post->meta_title));
            $titles[$key][] = "post/{$post->slug}";
        }

        foreach ($titles as $title => $refs) {
            if (count($refs) > 1) {
                $issues[] = $this->issue('warning', 'duplicate_meta_title', "Duplicate meta title \"{$title}\" on: ".implode(', ', $refs), $title);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $issues
     */
    private function auditInternalLinks(array &$issues): void
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $checked = [];

        $htmlSources = SeoPage::whereNotNull('content_html')->pluck('content_html', 'page_key');
        foreach (CmsPost::whereNotNull('content_html')->get() as $post) {
            $htmlSources["post:{$post->slug}"] = $post->content_html;
        }

        foreach ($htmlSources as $source => $html) {
            preg_match_all('/href=["\']([^"\']+)["\']/i', $html ?? '', $matches);
            foreach ($matches[1] ?? [] as $href) {
                if (! str_starts_with($href, '/') && ! str_starts_with($href, $baseUrl)) {
                    continue;
                }
                $path = str_starts_with($href, 'http') ? parse_url($href, PHP_URL_PATH) : $href;
                $path = $path ?: '/';
                if (isset($checked[$path])) {
                    continue;
                }
                $checked[$path] = true;

                try {
                    $response = Http::timeout(5)->get($baseUrl.$path);
                    if ($response->status() >= 400) {
                        $issues[] = $this->issue('error', 'broken_internal_link', "Broken link {$path} (HTTP {$response->status()}) referenced from {$source}", $path);
                    }
                } catch (\Throwable) {
                    $issues[] = $this->issue('warning', 'link_check_failed', "Could not verify link {$path} from {$source}", $path);
                }
            }
        }

        $withoutLazy = Vehicle::whereNotNull('published_at')->limit(50)->get();
        if ($withoutLazy->isNotEmpty()) {
            $issues[] = $this->issue('info', 'lazy_load_audit', 'Review vehicle listing images for loading="lazy" on high-traffic pages', 'vehicles');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function issue(string $severity, string $type, string $message, string $reference): array
    {
        return compact('severity', 'type', 'message', 'reference');
    }
}
