<?php

namespace App\Http\Controllers;

use App\Models\SeoPage;
use App\Models\Vehicle;
use App\Models\Dealer;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminSeoPageController extends Controller
{
    public function __construct(
        private SeoService $seoService
    ) {}

    /**
     * Get page_key options for dropdown (for vehicle/dealer types).
     * GET ?page_type=vehicle|dealer
     */
    public function pageKeyOptions(Request $request): JsonResponse
    {
        $pageType = $request->get('page_type');
        $options = [];

        if ($pageType === 'vehicle') {
            $vehicles = Vehicle::select('id', 'slug', 'title')
                ->whereNotNull('published_at')
                ->orderBy('title')
                ->limit(2000)
                ->get();
            foreach ($vehicles as $v) {
                $options[] = [
                    'value' => $v->slug,
                    'label' => $v->title ?: $v->slug ?: 'Vehicle #' . $v->id,
                ];
            }
        } elseif ($pageType === 'dealer') {
            $dealers = Dealer::with('owner:id,name')->select('id', 'slug', 'user_id')->orderBy('id')->get();
            foreach ($dealers as $d) {
                $options[] = [
                    'value' => $d->slug,
                    'label' => $d->owner?->name ?? $d->slug ?? 'Dealer #' . $d->id,
                ];
            }
        }

        return $this->success($options);
    }

    /**
     * List SEO pages (optionally filter by page_type).
     */
    public function index(Request $request): JsonResponse
    {
        $pages = $this->seoService->getAllForAdmin();

        $pageType = $request->get('page_type');
        if ($pageType !== null && $pageType !== '') {
            $pages = $pages->where('page_type', $pageType);
        }

        return $this->success($pages->values()->all());
    }

    /**
     * Get one SEO page by id.
     */
    public function show(int $id): JsonResponse
    {
        $page = SeoPage::find($id);
        if (!$page) {
            return $this->notFound(__('messages.api.resource_not_found'));
        }
        return $this->success($page);
    }

    /**
     * Create or update an SEO page (by page_type + page_key).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page_type' => 'required|string|max:50',
            'page_key' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:65535',
            'meta_keywords' => 'nullable|string|max:65535',
            'canonical_url' => 'nullable|string|max:2048',
            'robots' => 'nullable|string|max:100',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:65535',
            'og_image' => 'nullable|string|max:2048',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string|max:65535',
            'twitter_image' => 'nullable|string|max:2048',
            'schema_type' => 'nullable|string|max:100',
            'schema_json' => 'nullable|array',
            'content_html' => 'nullable|string',
            'faq_json' => 'nullable|array',
            'breadcrumbs_json' => 'nullable|array',
        ]);

        if (array_key_exists('content_html', $validated) && is_string($validated['content_html'])) {
            $validated['content_html'] = app(\App\Services\HtmlSanitizer::class)->purify($validated['content_html']);
        }

        $page = $this->seoService->updateOrCreate($validated);
        return $this->created($page);
    }

    /**
     * Update an existing SEO page by id.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $page = SeoPage::find($id);
        if (!$page) {
            return $this->notFound(__('messages.api.resource_not_found'));
        }

        $validated = $request->validate([
            'page_type' => 'sometimes|string|max:50',
            'page_key' => 'sometimes|string|max:255',
            'title' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:65535',
            'meta_keywords' => 'nullable|string|max:65535',
            'canonical_url' => 'nullable|string|max:2048',
            'robots' => 'nullable|string|max:100',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:65535',
            'og_image' => 'nullable|string|max:2048',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string|max:65535',
            'twitter_image' => 'nullable|string|max:2048',
            'schema_type' => 'nullable|string|max:100',
            'schema_json' => 'nullable|array',
            'content_html' => 'nullable|string',
            'faq_json' => 'nullable|array',
            'breadcrumbs_json' => 'nullable|array',
        ]);

        if (array_key_exists('content_html', $validated) && is_string($validated['content_html'])) {
            $validated['content_html'] = app(\App\Services\HtmlSanitizer::class)->purify($validated['content_html']);
        }

        $page->fill($validated);
        $page->save();
        SeoPage::clearPageCache($page->page_type, $page->page_key);
        SeoPage::clearSitemapAndRobotsCache();

        return $this->success($page);
    }

    /**
     * Delete an SEO page.
     */
    public function destroy(int $id): JsonResponse
    {
        $page = SeoPage::find($id);
        if (!$page) {
            return $this->notFound(__('messages.api.resource_not_found'));
        }
        $this->seoService->delete($page);
        return $this->success(null);
    }
}
