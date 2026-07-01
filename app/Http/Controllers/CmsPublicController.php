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

        $seo = [
            'title' => $post->meta_title ?: $post->title,
            'meta_title' => $post->meta_title ?: $post->title,
            'meta_description' => $post->meta_description ?: $post->excerpt,
            'og_image' => $post->og_image ?: $post->featuredMedia?->url(),
            'canonical_url' => $post->canonical_url,
            'robots' => $post->robots,
        ];

        return view('cms.blog-show', compact('post', 'seo'));
    }

    public function landingShow(string $slug): View
    {
        $page = LandingPage::where('slug', $slug)
            ->where('status', CmsPostStatus::PUBLISHED)
            ->where('published_at', '<=', now())
            ->firstOrFail();

        $seo = [
            'title' => $page->meta_title ?: $page->title,
            'meta_title' => $page->meta_title ?: $page->title,
            'meta_description' => $page->meta_description,
            'og_title' => $page->og_title,
            'og_description' => $page->og_description,
            'og_image' => $page->og_image,
            'canonical_url' => $page->canonical_url,
            'robots' => $page->robots,
        ];

        $vehicleGridLimit = 6;
        foreach ($page->blocks ?? [] as $block) {
            if (($block['type'] ?? '') === 'vehicle_grid') {
                $vehicleGridLimit = (int) ($block['limit'] ?? 6);
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
