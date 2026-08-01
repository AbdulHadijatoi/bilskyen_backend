<?php

namespace App\Services\Cms;

use App\Models\CmsMedia;
use App\Models\CmsPost;
use App\Models\CmsPostCategory;
use App\Models\LandingPage;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\HtmlSanitizer;
use Illuminate\Support\Collection;

class CmsPreviewRenderer
{
    public function __construct(
        private HtmlSanitizer $sanitizer
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function renderLanding(array $payload): string
    {
        $blocks = [];
        foreach ($payload['blocks'] ?? [] as $block) {
            if (! is_array($block)) {
                continue;
            }
            $normalized = CmsTemplateCatalog::normalizeLandingBlock($block);
            if (($normalized['type'] ?? '') === 'richtext' && isset($normalized['content']['html'])) {
                $normalized['content']['html'] = $this->sanitizer->purify((string) $normalized['content']['html']);
                if (isset($normalized['content']['html_secondary'])) {
                    $normalized['content']['html_secondary'] = $this->sanitizer->purify((string) $normalized['content']['html_secondary']);
                }
            }
            $blocks[] = $normalized;
        }

        $layout = (string) ($payload['layout'] ?? 'guide');
        $style = (string) ($payload['style'] ?? 'brand');
        if (! CmsTemplateCatalog::isValidLandingLayout($layout)) {
            $layout = 'guide';
        }
        if (! CmsTemplateCatalog::isValidStyle($style)) {
            $style = 'brand';
        }

        $page = new LandingPage([
            'title' => (string) ($payload['title'] ?? 'Untitled'),
            'slug' => (string) ($payload['slug'] ?? 'preview'),
            'layout' => $layout,
            'style' => $style,
            'blocks' => $blocks,
            'status' => 'draft',
        ]);

        $limit = 6;
        foreach ($blocks as $block) {
            if (($block['type'] ?? '') === 'vehicle_grid') {
                $limit = (int) ($block['content']['limit'] ?? 6);
                break;
            }
        }

        $vehicles = Vehicle::whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->limit(max(1, min($limit, 12)))
            ->get();

        $seo = [
            'meta_title' => $payload['meta_title'] ?? $page->title,
            'meta_description' => $payload['meta_description'] ?? '',
        ];

        return view('cms.preview-landing', [
            'page' => $page,
            'seo' => $seo,
            'vehicles' => $vehicles,
        ])->render();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function renderBlog(array $payload): string
    {
        $sections = [];
        foreach ($payload['sections'] ?? [] as $section) {
            if (is_array($section)) {
                $sections[] = CmsTemplateCatalog::normalizeBlogSection($section);
            }
        }

        $layout = (string) ($payload['layout'] ?? 'classic');
        $style = (string) ($payload['style'] ?? 'brand');
        if (! CmsTemplateCatalog::isValidBlogLayout($layout)) {
            $layout = 'classic';
        }
        if (! CmsTemplateCatalog::isValidStyle($style)) {
            $style = 'brand';
        }

        $contentHtml = $this->sanitizer->purify((string) ($payload['content_html'] ?? ''));

        $post = new CmsPost([
            'title' => (string) ($payload['title'] ?? 'Untitled'),
            'slug' => (string) ($payload['slug'] ?? 'preview'),
            'excerpt' => (string) ($payload['excerpt'] ?? ''),
            'content_html' => $contentHtml,
            'layout' => $layout,
            'style' => $style,
            'sections' => $sections,
            'status' => 'draft',
            'category_id' => $payload['category_id'] ?? null,
            'featured_media_id' => $payload['featured_media_id'] ?? null,
            'published_at' => now(),
        ]);

        if (! empty($payload['category_id'])) {
            $category = CmsPostCategory::find($payload['category_id']);
            if ($category) {
                $post->setRelation('category', $category);
            }
        }

        if (! empty($payload['featured_media_id'])) {
            $media = CmsMedia::find($payload['featured_media_id']);
            if ($media) {
                $post->setRelation('featuredMedia', $media);
            }
        }

        $author = auth()->user();
        if ($author instanceof User) {
            $post->setRelation('author', $author);
        }

        $relatedLimit = 3;
        foreach ($sections as $section) {
            if (($section['type'] ?? '') === 'related_posts') {
                $relatedLimit = (int) ($section['content']['limit'] ?? 3);
                break;
            }
        }

        /** @var Collection<int, CmsPost> $relatedPosts */
        $relatedPosts = CmsPost::where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->limit($relatedLimit)
            ->get(['id', 'slug', 'title']);

        $seo = [
            'meta_title' => $payload['meta_title'] ?? $post->title,
            'meta_description' => $payload['meta_description'] ?? $post->excerpt,
        ];

        return view('cms.preview-blog', [
            'post' => $post,
            'seo' => $seo,
            'relatedPosts' => $relatedPosts,
            'previewMode' => true,
        ])->render();
    }
}
