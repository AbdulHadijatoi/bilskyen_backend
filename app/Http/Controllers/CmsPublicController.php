<?php

namespace App\Http\Controllers;

use App\Constants\CmsPostStatus;
use App\Models\CmsPost;
use App\Models\CmsPostCategory;
use App\Models\LandingPage;
use App\Models\Vehicle;
use App\Services\SeoService;
use Illuminate\View\View;

class CmsPublicController extends Controller
{
    public function __construct(
        private SeoService $seoService
    ) {}

    public function blogIndex(): View
    {
        $posts = CmsPost::with(['category', 'featuredMedia', 'author'])
            ->where('status', CmsPostStatus::PUBLISHED)
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->paginate(12);

        $seo = $this->seoService->getForPage('static', 'blog') ?? [
            'title' => __('messages.cms.blog_title'),
            'meta_title' => __('messages.cms.blog_title'),
            'meta_description' => __('messages.cms.blog_description'),
        ];

        return view('cms.blog-index', compact('posts', 'seo'));
    }

    public function blogShow(string $slug): View
    {
        $post = CmsPost::with(['category', 'featuredMedia', 'author'])
            ->where('slug', $slug)
            ->where('status', CmsPostStatus::PUBLISHED)
            ->where('published_at', '<=', now())
            ->firstOrFail();

        $seo = $this->seoService->resolveForCmsPost($post);

        $relatedLimit = 3;
        foreach ($post->sections ?? [] as $section) {
            if (($section['type'] ?? '') === 'related_posts') {
                $relatedLimit = (int) (($section['content']['limit'] ?? 3));
                break;
            }
        }

        $relatedPosts = CmsPost::where('status', CmsPostStatus::PUBLISHED)
            ->where('published_at', '<=', now())
            ->where('id', '!=', $post->id)
            ->when($post->category_id, fn ($q) => $q->where('category_id', $post->category_id))
            ->orderByDesc('published_at')
            ->limit($relatedLimit)
            ->get(['id', 'slug', 'title']);

        return view('cms.blog-show', compact('post', 'seo', 'relatedPosts'));
    }

    public function landingShow(string $slug): View
    {
        $page = LandingPage::where('slug', $slug)
            ->where('status', CmsPostStatus::PUBLISHED)
            ->where('published_at', '<=', now())
            ->firstOrFail();

        $seo = $this->seoService->resolveForLandingPage($page);

        $vehicleGridLimit = 6;
        foreach ($page->blocks ?? [] as $block) {
            if (($block['type'] ?? '') === 'vehicle_grid') {
                $content = $block['content'] ?? $block;
                $vehicleGridLimit = (int) ($content['limit'] ?? 6);
                break;
            }
        }

        $vehicles = Vehicle::whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->limit($vehicleGridLimit)
            ->get();

        return view('cms.landing-page', compact('page', 'seo', 'vehicles'));
    }
}
