<?php

namespace App\Http\Controllers;

use App\Services\PageContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public Home Page Content Controller
 */
class HomePageContentController extends Controller
{
    public function __construct(
        private PageContentService $pageContentService
    ) {}

    /**
     * Get home page content (public endpoint, uses cache)
     */
    public function getHomePageContent(Request $request): JsonResponse
    {
        $pageName = $request->get('page_name', 'home');
        $content = $this->pageContentService->getHomePageContent($pageName);
        
        return $this->success($content);
    }
}
