<?php

namespace App\Services;

use App\Models\PageContent;
use Illuminate\Support\Facades\Cache;

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
}
