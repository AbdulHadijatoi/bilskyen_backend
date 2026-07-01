<?php

namespace App\Services;

use App\Models\PageContent;
use App\Models\PageImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PageContentService
{
    public function __construct(
        private FileService $fileService
    ) {}

    /**
     * Cache TTL in seconds (30 days - long-lived since manually cleared on updates)
     */
    private const CACHE_TTL = 2592000;

    /**
     * Get all home page content from cache or database
     */
    public function getHomePageContent(string $pageName = 'home'): array
    {
        $cacheKey = PageContent::getCacheKey($pageName);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($pageName) {
            $sections = PageContent::where('page_name', $pageName)
                ->get()
                ->keyBy('section_key')
                ->map(function ($item) {
                    return $item->content;
                })
                ->toArray();

            return $sections;
        });
    }

    /**
     * Get all sections for admin panel
     */
    public function getAllSections(string $pageName = 'home'): array
    {
        $sections = PageContent::where('page_name', $pageName)
            ->orderBy('section_key')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'section_key' => $item->section_key,
                    'content' => $item->content,
                    'page_name' => $item->page_name,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            })
            ->toArray();

        return $sections;
    }

    /**
     * Update a single section
     */
    public function updateSection(string $pageName, string $sectionKey, ?string $content): PageContent
    {
        $pageContent = PageContent::updateOrCreate(
            [
                'page_name' => $pageName,
                'section_key' => $sectionKey,
            ],
            [
                'content' => $content,
            ]
        );

        // Cache is automatically cleared by model event
        return $pageContent;
    }

    /**
     * Update multiple sections at once
     *
     * @param  array  $sections  Array of [section_key => content]
     */
    public function updateBulk(string $pageName, array $sections): array
    {
        $updated = [];

        foreach ($sections as $sectionKey => $content) {
            $pageContent = $this->updateSection($pageName, $sectionKey, $content);
            $updated[] = $pageContent;
        }

        // Cache is automatically cleared by model events
        return $updated;
    }

    /**
     * Get all page images from cache or database
     *
     * @return array Array keyed by section_key, each containing array of images
     */
    public function getPageImages(string $pageName = 'home'): array
    {
        $cacheKey = PageImage::getCacheKey($pageName);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($pageName) {
            $images = PageImage::where('page_name', $pageName)
                ->orderBy('section_key')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('section_key')
                ->map(function ($group) {
                    // Convert to array to ensure image_url is included via $appends
                    return $group->map(function ($image) {
                        return $image->toArray();
                    })->values()->toArray();
                })
                ->toArray();

            return $images;
        });
    }

    /**
     * Upload a page image
     */
    public function uploadPageImage(
        string $pageName,
        string $sectionKey,
        UploadedFile $file,
        ?string $altText = null,
        int $sortOrder = 0
    ): PageImage {
        $this->fileService->validateFile($file);

        $urls = $this->fileService->uploadFiles([$file], 'public', 'page-content');
        $fileUrl = $urls[0] ?? '';
        if ($fileUrl === '') {
            throw new \RuntimeException('Page image upload failed.');
        }

        $imagePath = str_replace('/storage/', '', parse_url($fileUrl, PHP_URL_PATH) ?? '');

        // Create page image record
        $pageImage = PageImage::create([
            'page_name' => $pageName,
            'section_key' => $sectionKey,
            'image_path' => $imagePath,
            'alt_text' => $altText,
            'sort_order' => $sortOrder,
        ]);

        // Cache is automatically cleared by model event
        return $pageImage;
    }

    /**
     * Delete a page image
     */
    public function deletePageImage(int $imageId): bool
    {
        $pageImage = PageImage::findOrFail($imageId);
        $pageName = $pageImage->page_name;
        $imagePath = $pageImage->image_path;

        // Delete file from storage
        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }

        // Delete record (cache is automatically cleared by model event)
        return $pageImage->delete();
    }

    /**
     * Update a page image (alt text or sort order)
     *
     * @param  array  $data  Array with 'alt_text' and/or 'sort_order'
     */
    public function updatePageImage(int $imageId, array $data): PageImage
    {
        $pageImage = PageImage::findOrFail($imageId);

        if (isset($data['alt_text'])) {
            $pageImage->alt_text = $data['alt_text'];
        }

        if (isset($data['sort_order'])) {
            $pageImage->sort_order = $data['sort_order'];
        }

        $pageImage->save();

        // Cache is automatically cleared by model event
        return $pageImage;
    }
}
