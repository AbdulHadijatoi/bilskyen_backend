<?php

namespace App\Http\Controllers;

use App\Services\PageContentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Admin Home Page Content Controller
 */
class AdminHomePageController extends Controller
{
    public function __construct(
        private PageContentService $pageContentService
    ) {}

    /**
     * Get all home page sections
     */
    public function index(Request $request): JsonResponse
    {
        $pageName = $request->get('page_name', 'home');
        $sections = $this->pageContentService->getAllSections($pageName);
        
        return $this->success($sections);
    }

    /**
     * Update a single section
     */
    public function update(Request $request, string $sectionKey): JsonResponse
    {
        $request->validate([
            'content' => 'nullable|string',
            'page_name' => 'sometimes|string|max:100',
        ]);

        $pageName = $request->get('page_name', 'home');
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
            'sections.*' => 'nullable|string',
            'page_name' => 'sometimes|string|max:100',
        ]);

        $pageName = $request->get('page_name', 'home');
        $sections = $request->get('sections', []);
        
        $updated = $this->pageContentService->updateBulk($pageName, $sections);

        return $this->success($updated);
    }
}
