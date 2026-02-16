<?php

namespace App\Http\Controllers;

use App\Services\PageContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public page content API (privacy, terms).
 * Read-only; uses cached content from PageContentService.
 */
class PageContentController extends Controller
{
    public function __construct(
        private PageContentService $pageContentService
    ) {}

    /**
     * Get privacy page content (public, cached).
     */
    public function getPrivacyContent(Request $request): JsonResponse
    {
        $pageName = $request->get('page_name', 'privacy');
        $content = $this->pageContentService->getHomePageContent($pageName);

        return $this->success($content);
    }

    /**
     * Get terms page content (public, cached).
     */
    public function getTermsContent(Request $request): JsonResponse
    {
        $pageName = $request->get('page_name', 'terms');
        $content = $this->pageContentService->getHomePageContent($pageName);

        return $this->success($content);
    }
}
