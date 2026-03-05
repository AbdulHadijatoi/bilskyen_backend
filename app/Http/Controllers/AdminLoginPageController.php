<?php

namespace App\Http\Controllers;

use App\Services\PageContentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin Login Page Content Controller
 * Manages content for the login (auth) page sidebar testimonial.
 */
class AdminLoginPageController extends Controller
{
    public function __construct(
        private PageContentService $pageContentService
    ) {}

    /**
     * Get all login page sections
     */
    public function index(Request $request): JsonResponse
    {
        $pageName = $request->get('page_name', 'login');
        $sections = $this->pageContentService->getAllSections($pageName);

        return $this->success($sections);
    }

    /**
     * Update a single section
     */
    public function update(Request $request, string $sectionKey): JsonResponse
    {
        $request->validate([
            'content' => 'nullable|string|max:65535',
            'page_name' => 'sometimes|string|max:100',
        ]);

        $pageName = $request->get('page_name', 'login');
        $content = $request->get('content');

        $pageContent = $this->pageContentService->updateSection(
            $pageName,
            $sectionKey,
            $content
        );

        return $this->success($pageContent);
    }

    /**
     * Update multiple sections at once
     */
    public function updateBulk(Request $request): JsonResponse
    {
        $request->validate([
            'sections' => 'required|array',
            'sections.*' => 'nullable|string|max:65535',
            'page_name' => 'sometimes|string|max:100',
        ]);

        $pageName = $request->get('page_name', 'login');
        $sections = $request->get('sections', []);

        $updated = $this->pageContentService->updateBulk($pageName, $sections);

        return $this->success($updated);
    }
}
