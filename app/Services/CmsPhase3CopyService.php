<?php

namespace App\Services;

use App\Models\CmsPost;
use App\Models\LandingPage;

/**
 * Idempotent, slug-gated CMS copy for Phase 3. Skips missing rows (no demo pollution).
 */
class CmsPhase3CopyService
{
    public const BLOG_SLUG = '5-ting-du-boer-tjekke-foer-du-koeber-en-brugt-bil';

    /**
     * @var array<string, array{title: string, description: string}>
     */
    public const GUIDE_METAS = [
        'brugte-elbiler' => [
            'title' => 'Brugte elbiler til salg | Bilskyen',
            'description' => 'Find brugte elbiler hos forhandlere og private på Bilskyen. Sammenlign rækkevidde, pris og stand, før du køber elbil.',
        ],
        'brugte-biler-under-100000-kr' => [
            'title' => 'Brugte biler under 100.000 kr. | Bilskyen',
            'description' => 'Se brugte biler under 100.000 kr. på Bilskyen. Filtrer på mærke, km og brændstof, og kontakt sælger direkte.',
        ],
    ];

    /**
     * @return array{blog: bool, guides: list<string>}
     */
    public function apply(): array
    {
        $appliedGuides = [];

        $blogUpdated = $this->applyBlog();
        foreach (array_keys(self::GUIDE_METAS) as $slug) {
            if ($this->applyGuide($slug)) {
                $appliedGuides[] = $slug;
            }
        }

        return [
            'blog' => $blogUpdated,
            'guides' => $appliedGuides,
        ];
    }

    public function applyBlog(): bool
    {
        $post = CmsPost::query()->where('slug', self::BLOG_SLUG)->first();
        if (! $post) {
            return false;
        }

        $path = database_path('content/blog/'.self::BLOG_SLUG.'.html');
        if (! is_readable($path)) {
            return false;
        }

        $html = (string) file_get_contents($path);
        if (trim($html) === '') {
            return false;
        }

        $post->content_html = $html;
        $post->save();

        return true;
    }

    public function applyGuide(string $slug): bool
    {
        $meta = self::GUIDE_METAS[$slug] ?? null;
        if (! $meta) {
            return false;
        }

        $page = LandingPage::query()->where('slug', $slug)->first();
        if (! $page) {
            return false;
        }

        $page->meta_description = $meta['description'];
        if (trim((string) $page->meta_title) === '') {
            $page->meta_title = $meta['title'];
        }
        $page->save();

        return true;
    }
}
