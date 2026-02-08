<?php

namespace App\Services;

use App\Models\PageContent;
use App\Models\PageImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PageContentService
{
    /**
     * Cache TTL in seconds (30 days - long-lived since manually cleared on updates)
     */
    private const CACHE_TTL = 2592000;

    /**
     * Get all home page content from cache or database
     * 
     * @param string $pageName
     * @return array
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
     * 
     * @param string $pageName
     * @return array
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
     * 
     * @param string $pageName
     * @param string $sectionKey
     * @param string|null $content
     * @return PageContent
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
     * @param string $pageName
     * @param array $sections Array of [section_key => content]
     * @return array
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
     * @param string $pageName
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
     * 
     * @param string $pageName
     * @param string $sectionKey
     * @param UploadedFile $file
     * @param string|null $altText
     * @param int $sortOrder
     * @return PageImage
     */
    public function uploadPageImage(
        string $pageName,
        string $sectionKey,
        UploadedFile $file,
        ?string $altText = null,
        int $sortOrder = 0
    ): PageImage {
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $maxSize = 20 * 1024 * 1024; // 20MB

        if (!in_array($file->getMimeType(), $allowedTypes)) {
            throw new \Exception('Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
        }

        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size exceeds maximum allowed size of 20MB');
        }

        // Upload file
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $directory = 'page-content';
        $path = $file->storeAs($directory, $filename, 'public');
        
        // Extract relative path (remove 'public/' prefix if present)
        $imagePath = str_replace('public/', '', $path);

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
     * 
     * @param int $imageId
     * @return bool
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
     * @param int $imageId
     * @param array $data Array with 'alt_text' and/or 'sort_order'
     * @return PageImage
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
