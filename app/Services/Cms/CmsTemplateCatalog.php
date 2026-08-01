<?php

namespace App\Services\Cms;

/**
 * Canonical CMS layout / section / style catalog (mirrored in panel_vue constants).
 */
class CmsTemplateCatalog
{
    /** @return list<string> */
    public static function styles(): array
    {
        return ['brand', 'editorial', 'bold', 'soft'];
    }

    /** @return list<string> */
    public static function landingLayouts(): array
    {
        return ['funnel', 'guide', 'spotlight', 'minimal', 'conversion'];
    }

    /** @return list<string> */
    public static function blogLayouts(): array
    {
        return ['classic', 'hero', 'magazine', 'feature'];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function landingSectionVariants(): array
    {
        return [
            'hero' => ['centered-dark', 'split-image', 'minimal-light', 'full-bleed'],
            'richtext' => ['narrow-prose', 'wide', 'two-column'],
            'cta' => ['banner-brand', 'dark', 'soft-band'],
            'vehicle_grid' => ['cards-3', 'featured-row'],
            'faq' => ['accordion', 'two-column'],
            'features' => ['icon-grid', 'checklist'],
            'testimonials' => ['quote-cards'],
            'stats' => ['metric-row'],
            'image_text' => ['image-left', 'image-right'],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function blogSectionVariants(): array
    {
        return [
            'pull_quote' => ['accent-left', 'centered'],
            'cta_inline' => ['soft-band', 'brand-banner'],
            'related_posts' => ['card-row'],
            'toc' => ['sidebar-list'],
            'author_box' => ['simple-card'],
        ];
    }

    /**
     * @return array<string, list<array{type: string, variant: string}>>
     */
    public static function landingLayoutSeeds(): array
    {
        return [
            'funnel' => [
                ['type' => 'hero', 'variant' => 'centered-dark'],
                ['type' => 'features', 'variant' => 'icon-grid'],
                ['type' => 'vehicle_grid', 'variant' => 'cards-3'],
                ['type' => 'faq', 'variant' => 'accordion'],
                ['type' => 'cta', 'variant' => 'banner-brand'],
            ],
            'guide' => [
                ['type' => 'hero', 'variant' => 'minimal-light'],
                ['type' => 'richtext', 'variant' => 'narrow-prose'],
                ['type' => 'faq', 'variant' => 'accordion'],
                ['type' => 'cta', 'variant' => 'soft-band'],
            ],
            'spotlight' => [
                ['type' => 'hero', 'variant' => 'full-bleed'],
                ['type' => 'vehicle_grid', 'variant' => 'featured-row'],
                ['type' => 'cta', 'variant' => 'dark'],
            ],
            'minimal' => [
                ['type' => 'hero', 'variant' => 'minimal-light'],
                ['type' => 'richtext', 'variant' => 'narrow-prose'],
            ],
            'conversion' => [
                ['type' => 'hero', 'variant' => 'split-image'],
                ['type' => 'features', 'variant' => 'checklist'],
                ['type' => 'testimonials', 'variant' => 'quote-cards'],
                ['type' => 'cta', 'variant' => 'banner-brand'],
                ['type' => 'faq', 'variant' => 'two-column'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultContentFor(string $type): array
    {
        return match ($type) {
            'hero' => [
                'headline' => '',
                'subheadline' => '',
                'cta_text' => '',
                'cta_url' => '',
                'image_url' => '',
            ],
            'richtext' => [
                'html' => '',
                'html_secondary' => '',
            ],
            'cta' => [
                'title' => '',
                'subtitle' => '',
                'button_text' => '',
                'button_url' => '',
            ],
            'vehicle_grid' => [
                'title' => '',
                'limit' => 6,
            ],
            'faq' => [
                'title' => '',
                'items' => [['question' => '', 'answer' => '']],
            ],
            'features' => [
                'title' => '',
                'subtitle' => '',
                'items' => [
                    ['title' => '', 'body' => '', 'icon' => 'check'],
                    ['title' => '', 'body' => '', 'icon' => 'check'],
                    ['title' => '', 'body' => '', 'icon' => 'check'],
                ],
            ],
            'testimonials' => [
                'title' => '',
                'items' => [
                    ['quote' => '', 'author' => '', 'role' => ''],
                ],
            ],
            'stats' => [
                'title' => '',
                'items' => [
                    ['value' => '', 'label' => ''],
                    ['value' => '', 'label' => ''],
                    ['value' => '', 'label' => ''],
                ],
            ],
            'image_text' => [
                'title' => '',
                'body' => '',
                'image_url' => '',
                'cta_text' => '',
                'cta_url' => '',
            ],
            'pull_quote' => [
                'quote' => '',
                'attribution' => '',
            ],
            'cta_inline' => [
                'title' => '',
                'button_text' => '',
                'button_url' => '',
            ],
            'related_posts' => [
                'title' => 'Related articles',
                'limit' => 3,
            ],
            'toc' => [
                'title' => 'In this article',
            ],
            'author_box' => [
                'show_bio' => true,
            ],
            default => [],
        };
    }

    public static function defaultVariantFor(string $type, bool $blog = false): string
    {
        $map = $blog ? self::blogSectionVariants() : self::landingSectionVariants();
        $variants = $map[$type] ?? ['default'];

        return $variants[0];
    }

    public static function isValidLandingLayout(string $layout): bool
    {
        return in_array($layout, self::landingLayouts(), true);
    }

    public static function isValidBlogLayout(string $layout): bool
    {
        return in_array($layout, self::blogLayouts(), true);
    }

    public static function isValidStyle(string $style): bool
    {
        return in_array($style, self::styles(), true);
    }

    public static function isValidLandingSection(string $type, string $variant): bool
    {
        $map = self::landingSectionVariants();

        return isset($map[$type]) && in_array($variant, $map[$type], true);
    }

    public static function isValidBlogSection(string $type, string $variant): bool
    {
        $map = self::blogSectionVariants();

        return isset($map[$type]) && in_array($variant, $map[$type], true);
    }

    /**
     * Normalize a landing block to {id, type, variant, content}.
     *
     * @param  array<string, mixed>  $block
     * @return array{id: string, type: string, variant: string, content: array<string, mixed>}
     */
    public static function normalizeLandingBlock(array $block): array
    {
        $type = (string) ($block['type'] ?? 'richtext');
        $variant = (string) ($block['variant'] ?? self::defaultVariantFor($type));
        if (! self::isValidLandingSection($type, $variant)) {
            $variant = self::defaultVariantFor($type);
            if (! isset(self::landingSectionVariants()[$type])) {
                $type = 'richtext';
                $variant = self::defaultVariantFor($type);
            }
        }

        $content = $block['content'] ?? null;
        if (! is_array($content)) {
            $defaults = self::defaultContentFor($type);
            $content = [];
            foreach (array_keys($defaults) as $key) {
                if (array_key_exists($key, $block)) {
                    $content[$key] = $block[$key];
                } else {
                    $content[$key] = $defaults[$key];
                }
            }
        } else {
            $content = array_merge(self::defaultContentFor($type), $content);
        }

        return [
            'id' => (string) ($block['id'] ?? \Illuminate\Support\Str::uuid()),
            'type' => $type,
            'variant' => $variant,
            'content' => $content,
        ];
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array{id: string, type: string, variant: string, content: array<string, mixed>}
     */
    public static function normalizeBlogSection(array $section): array
    {
        $type = (string) ($section['type'] ?? 'pull_quote');
        $variant = (string) ($section['variant'] ?? self::defaultVariantFor($type, true));
        if (! self::isValidBlogSection($type, $variant)) {
            $variant = self::defaultVariantFor($type, true);
            if (! isset(self::blogSectionVariants()[$type])) {
                $type = 'pull_quote';
                $variant = self::defaultVariantFor($type, true);
            }
        }

        $content = is_array($section['content'] ?? null)
            ? array_merge(self::defaultContentFor($type), $section['content'])
            : self::defaultContentFor($type);

        return [
            'id' => (string) ($section['id'] ?? \Illuminate\Support\Str::uuid()),
            'type' => $type,
            'variant' => $variant,
            'content' => $content,
        ];
    }
}
