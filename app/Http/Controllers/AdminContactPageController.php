<?php

namespace App\Http\Controllers;

use App\Services\PageContentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Admin Contact Page Content Controller
 */
class AdminContactPageController extends Controller
{
    public function __construct(
        private PageContentService $pageContentService
    ) {}

    /**
     * Get all contact page sections
     */
    public function index(Request $request): JsonResponse
    {
        $pageName = $request->get('page_name', 'contact');
        $sections = $this->pageContentService->getAllSections($pageName);
        $images = $this->pageContentService->getPageImages($pageName);
        
        return $this->success([
            'sections' => $sections,
            'images' => $images,
        ]);
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

        $pageName = $request->get('page_name', 'contact');
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

        $pageName = $request->get('page_name', 'contact');
        $sections = $request->get('sections', []);
        
        $updated = $this->pageContentService->updateBulk($pageName, $sections);

        return $this->success($updated);
    }

    /**
     * Upload an image for a section
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'section_key' => 'required|string|max:100',
            'image' => 'required|image|mimes:jpeg,png,webp,gif|max:20480', // 20MB
            'alt_text' => 'nullable|string|max:255',
            'sort_order' => 'sometimes|integer|min:0',
            'page_name' => 'sometimes|string|max:100',
        ]);

        $pageName = $request->get('page_name', 'contact');
        $sectionKey = $request->get('section_key');
        $file = $request->file('image');
        $altText = $request->get('alt_text');
        $sortOrder = $request->get('sort_order', 0);

        $pageImage = $this->pageContentService->uploadPageImage(
            $pageName,
            $sectionKey,
            $file,
            $altText,
            $sortOrder
        );

        return $this->success($pageImage);
    }

    /**
     * Delete an image
     */
    public function deleteImage(Request $request, int $imageId): JsonResponse
    {
        $this->pageContentService->deletePageImage($imageId);

        return $this->success(['message' => 'Image deleted successfully']);
    }
}
