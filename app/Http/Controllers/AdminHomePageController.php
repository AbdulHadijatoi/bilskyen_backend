<?php

namespace App\Http\Controllers;

use App\Models\PageImage;
use App\Services\PageContentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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

    /**
     * Upload an image for a home page section.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'section_key' => 'required|string|max:100',
            'image' => 'required|image|mimes:jpeg,png,webp,gif|max:20480',
            'alt_text' => 'nullable|string|max:255',
            'sort_order' => 'sometimes|integer|min:0',
            'page_name' => 'sometimes|string|max:100',
        ]);

        $pageName = $request->get('page_name', 'home');
        $sectionKey = $request->get('section_key');

        if ($sectionKey === 'hero_background') {
            $existing = PageImage::query()
                ->where('page_name', $pageName)
                ->where('section_key', $sectionKey)
                ->get();
            foreach ($existing as $image) {
                $this->pageContentService->deletePageImage($image->id);
            }
        }

        $pageImage = $this->pageContentService->uploadPageImage(
            $pageName,
            $sectionKey,
            $request->file('image'),
            $request->get('alt_text'),
            $request->get('sort_order', 0)
        );

        return $this->success($pageImage);
    }

    /**
     * Delete a home page image.
     */
    public function deleteImage(Request $request, int $imageId): JsonResponse
    {
        $this->pageContentService->deletePageImage($imageId);

        return $this->success(['message' => __('messages.messages.image_deleted_successfully')]);
    }
}
