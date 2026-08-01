<?php

namespace Database\Seeders;

use App\Constants\CmsPostStatus;
use App\Models\CmsMedia;
use App\Models\CmsPost;
use App\Models\CmsPostCategory;
use App\Models\LandingPage;
use App\Models\User;
use App\Services\Cms\CmsTemplateCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds published CMS demos covering every landing/blog layout, style, and section variant.
 *
 * Run: php artisan db:seed --class=CmsTemplateDemoSeeder
 */
class CmsTemplateDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $author = User::query()->where('email', 'admin@example.com')->first()
                ?? User::query()->orderBy('id')->first();

            $category = CmsPostCategory::firstOrCreate(
                ['slug' => 'template-demos'],
                ['name' => 'Template demos', 'sort_order' => 99]
            );

            $media = $this->seedPlaceholderMedia($author?->id);

            $this->seedLandingPages();
            $this->seedBlogPosts($author?->id, $category->id, $media);
        });

        $this->command?->info('CMS template demos seeded.');
        $this->command?->info('Landing: /guides/demo-lp-*');
        $this->command?->info('Blog: /blog/demo-blog-*');
    }

    /**
     * @return array<string, CmsMedia>
     */
    private function seedPlaceholderMedia(?int $uploaderId): array
    {
        $defs = [
            'hero' => ['filename' => 'demo-hero.jpg', 'alt' => 'Demo hero vehicle', 'w' => 1600, 'h' => 900, 'seed' => 'cms-hero'],
            'cover' => ['filename' => 'demo-cover.jpg', 'alt' => 'Demo article cover', 'w' => 1600, 'h' => 900, 'seed' => 'cms-cover'],
            'split' => ['filename' => 'demo-split.jpg', 'alt' => 'Demo split image', 'w' => 1200, 'h' => 900, 'seed' => 'cms-split'],
            'feature' => ['filename' => 'demo-feature.jpg', 'alt' => 'Demo feature image', 'w' => 1200, 'h' => 800, 'seed' => 'cms-feature'],
        ];

        $out = [];
        foreach ($defs as $key => $def) {
            $path = $this->placeholderUrl($def['seed'], $def['w'], $def['h']);
            $out[$key] = CmsMedia::updateOrCreate(
                ['filename' => $def['filename'], 'path' => $path],
                [
                    'disk' => 'public',
                    'mime_type' => 'image/jpeg',
                    'size_bytes' => 0,
                    'alt_text' => $def['alt'],
                    'uploaded_by' => $uploaderId,
                ]
            );
        }

        return $out;
    }

    private function placeholderUrl(string $seed, int $w = 1200, int $h = 800): string
    {
        // Deterministic Unsplash Source-compatible placeholders (no upload required).
        return sprintf('https://picsum.photos/seed/%s/%d/%d', rawurlencode($seed), $w, $h);
    }

    private function seedLandingPages(): void
    {
        $styles = CmsTemplateCatalog::styles();
        $layouts = CmsTemplateCatalog::landingLayouts();
        $seeds = CmsTemplateCatalog::landingLayoutSeeds();

        foreach ($layouts as $i => $layout) {
            foreach ($styles as $j => $style) {
                $slug = "demo-lp-{$layout}-{$style}";
                $title = sprintf('Demo LP · %s · %s', ucfirst($layout), ucfirst($style));
                $blocks = $this->buildLandingBlocksFromSeed($seeds[$layout] ?? [], $layout, $style);

                LandingPage::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'title' => $title,
                        'layout' => $layout,
                        'style' => $style,
                        'blocks' => $blocks,
                        'status' => CmsPostStatus::PUBLISHED,
                        'published_at' => now()->subDays(30 - $i - $j)->subHours($j),
                        'meta_title' => $title,
                        'meta_description' => "Template demo for landing layout {$layout} with {$style} style.",
                    ]
                );
            }
        }

        // Kitchen-sink: every landing section type + every variant.
        $kitchenBlocks = [];
        foreach (CmsTemplateCatalog::landingSectionVariants() as $type => $variants) {
            foreach ($variants as $variant) {
                $kitchenBlocks[] = $this->makeLandingBlock($type, $variant, "{$type}/{$variant}");
            }
        }

        LandingPage::updateOrCreate(
            ['slug' => 'demo-lp-all-sections'],
            [
                'title' => 'Demo LP · All sections & variants',
                'layout' => 'funnel',
                'style' => 'brand',
                'blocks' => $kitchenBlocks,
                'status' => CmsPostStatus::PUBLISHED,
                'published_at' => now()->subDay(),
                'meta_title' => 'All landing section variants',
                'meta_description' => 'Kitchen-sink demo of every landing section type and variant.',
            ]
        );
    }

    /**
     * @param  list<array{type: string, variant: string}>  $seed
     * @return list<array{id: string, type: string, variant: string, content: array<string, mixed>}>
     */
    private function buildLandingBlocksFromSeed(array $seed, string $layout, string $style): array
    {
        $blocks = [];
        foreach ($seed as $item) {
            $blocks[] = $this->makeLandingBlock($item['type'], $item['variant'], "{$layout}-{$style}-{$item['type']}");
        }

        return $blocks;
    }

    /**
     * @return array{id: string, type: string, variant: string, content: array<string, mixed>}
     */
    private function makeLandingBlock(string $type, string $variant, string $labelKey): array
    {
        $content = match ($type) {
            'hero' => [
                'headline' => "Hero · {$variant}",
                'subheadline' => "Sample hero for {$labelKey}. Find quality used cars across Denmark.",
                'cta_text' => 'Browse vehicles',
                'cta_url' => '/biler',
                'image_url' => $this->placeholderUrl("lp-{$variant}-{$labelKey}", 1400, 900),
            ],
            'richtext' => [
                'html' => '<h2>Rich text · '.e($variant).'</h2><p>This is demo copy for the <strong>'.e($labelKey).'</strong> section. Bilskyen helps buyers and dealers connect with transparent listings and trusted dealers.</p><ul><li>Verified listings</li><li>Finance options</li><li>Nationwide coverage</li></ul>',
                'html_secondary' => '<h3>Second column</h3><p>Secondary column content used by the two-column rich text variant. Compare models, book a viewing, and message dealers directly.</p>',
            ],
            'cta' => [
                'title' => "Ready to find your next car? ({$variant})",
                'subtitle' => 'Demo CTA band — explore inventory or talk to a dealer today.',
                'button_text' => 'See cars',
                'button_url' => '/biler',
            ],
            'vehicle_grid' => [
                'title' => "Latest vehicles · {$variant}",
                'limit' => $variant === 'featured-row' ? 8 : 6,
            ],
            'faq' => [
                'title' => "FAQ · {$variant}",
                'items' => [
                    ['question' => 'How do I contact a dealer?', 'answer' => 'Open any listing and use the enquiry form. Dealers typically reply within one business day.'],
                    ['question' => 'Can I finance a purchase?', 'answer' => 'Many dealers offer financing. Check the listing details or ask the dealer for options.'],
                    ['question' => 'Is this a template demo?', 'answer' => 'Yes — this FAQ content is seeded to showcase the '.$variant.' variant.'],
                ],
            ],
            'features' => [
                'title' => "Why Bilskyen · {$variant}",
                'subtitle' => 'Demo feature section highlighting marketplace benefits.',
                'items' => [
                    ['title' => 'Wide selection', 'body' => 'Browse cars from dealers across Denmark in one place.', 'icon' => 'check'],
                    ['title' => 'Clear pricing', 'body' => 'Compare prices and key specs without the guesswork.', 'icon' => 'check'],
                    ['title' => 'Direct contact', 'body' => 'Message dealers directly from the listing page.', 'icon' => 'check'],
                ],
            ],
            'testimonials' => [
                'title' => "What buyers say · {$variant}",
                'items' => [
                    ['quote' => 'Found my car in two days and the dealer was great to work with.', 'author' => 'Mette K.', 'role' => 'Buyer · Copenhagen'],
                    ['quote' => 'The listings are clear and it is easy to compare options.', 'author' => 'Jonas P.', 'role' => 'Buyer · Aarhus'],
                    ['quote' => 'We get serious enquiries from people who are ready to buy.', 'author' => 'Nordic Motors', 'role' => 'Dealer'],
                ],
            ],
            'stats' => [
                'title' => "By the numbers · {$variant}",
                'items' => [
                    ['value' => '12k+', 'label' => 'Active listings'],
                    ['value' => '850+', 'label' => 'Dealers'],
                    ['value' => '98%', 'label' => 'Happy enquiries'],
                ],
            ],
            'image_text' => [
                'title' => "Image + text · {$variant}",
                'body' => "Demo split content for {$labelKey}. Use this section to explain a product benefit next to a supporting photo.",
                'image_url' => $this->placeholderUrl("lp-imgtext-{$variant}", 1200, 900),
                'cta_text' => 'Learn more',
                'cta_url' => '/biler',
            ],
            default => CmsTemplateCatalog::defaultContentFor($type),
        };

        return [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'variant' => $variant,
            'content' => $content,
        ];
    }

    /**
     * @param  array<string, CmsMedia>  $media
     */
    private function seedBlogPosts(?int $authorId, int $categoryId, array $media): void
    {
        $styles = CmsTemplateCatalog::styles();
        $layouts = CmsTemplateCatalog::blogLayouts();

        foreach ($layouts as $i => $layout) {
            foreach ($styles as $j => $style) {
                $slug = "demo-blog-{$layout}-{$style}";
                $title = sprintf('Demo blog · %s · %s', ucfirst($layout), ucfirst($style));
                $sections = $this->defaultChromeForLayout($layout);

                $featured = match ($layout) {
                    'hero' => $media['hero']->id,
                    'feature' => $media['feature']->id,
                    'magazine' => $media['cover']->id,
                    default => $media['cover']->id,
                };

                CmsPost::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'category_id' => $categoryId,
                        'author_user_id' => $authorId,
                        'featured_media_id' => $featured,
                        'title' => $title,
                        'excerpt' => "Template demo article using the {$layout} layout and {$style} style.",
                        'content_html' => $this->sampleArticleHtml($layout, $style),
                        'layout' => $layout,
                        'style' => $style,
                        'sections' => $sections,
                        'status' => CmsPostStatus::PUBLISHED,
                        'published_at' => now()->subDays(20 - $i)->subHours($j + 1),
                        'meta_title' => $title,
                        'meta_description' => "Blog template demo for {$layout} / {$style}.",
                        'og_image' => $media['cover']->path,
                    ]
                );
            }
        }

        // Kitchen-sink: every blog chrome section + variant.
        $chrome = [];
        foreach (CmsTemplateCatalog::blogSectionVariants() as $type => $variants) {
            foreach ($variants as $variant) {
                $chrome[] = $this->makeBlogSection($type, $variant);
            }
        }

        CmsPost::updateOrCreate(
            ['slug' => 'demo-blog-all-chrome'],
            [
                'category_id' => $categoryId,
                'author_user_id' => $authorId,
                'featured_media_id' => $media['feature']->id,
                'title' => 'Demo blog · All chrome sections',
                'excerpt' => 'Kitchen-sink article showing every blog chrome section and variant.',
                'content_html' => $this->sampleArticleHtml('feature', 'brand'),
                'layout' => 'feature',
                'style' => 'brand',
                'sections' => $chrome,
                'status' => CmsPostStatus::PUBLISHED,
                'published_at' => now()->subHours(6),
                'meta_title' => 'All blog chrome sections',
                'meta_description' => 'Demo of pull quote, CTA, related posts, TOC, and author box variants.',
            ]
        );
    }

    /**
     * @return list<array{id: string, type: string, variant: string, content: array<string, mixed>}>
     */
    private function defaultChromeForLayout(string $layout): array
    {
        return match ($layout) {
            'magazine' => [
                $this->makeBlogSection('toc', 'sidebar-list'),
                $this->makeBlogSection('cta_inline', 'soft-band'),
                $this->makeBlogSection('related_posts', 'card-row'),
            ],
            'feature' => [
                $this->makeBlogSection('pull_quote', 'accent-left'),
                $this->makeBlogSection('author_box', 'simple-card'),
                $this->makeBlogSection('cta_inline', 'brand-banner'),
            ],
            'hero' => [
                $this->makeBlogSection('pull_quote', 'centered'),
                $this->makeBlogSection('related_posts', 'card-row'),
            ],
            default => [
                $this->makeBlogSection('cta_inline', 'soft-band'),
                $this->makeBlogSection('author_box', 'simple-card'),
            ],
        };
    }

    /**
     * @return array{id: string, type: string, variant: string, content: array<string, mixed>}
     */
    private function makeBlogSection(string $type, string $variant): array
    {
        $content = match ($type) {
            'pull_quote' => [
                'quote' => "A great car search should feel simple — this is the {$variant} pull quote demo.",
                'attribution' => 'Bilskyen editorial',
            ],
            'cta_inline' => [
                'title' => "Looking for your next car? ({$variant})",
                'button_text' => 'Browse vehicles',
                'button_url' => '/biler',
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
            default => CmsTemplateCatalog::defaultContentFor($type),
        };

        return [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'variant' => $variant,
            'content' => $content,
        ];
    }

    private function sampleArticleHtml(string $layout, string $style): string
    {
        return <<<HTML
<h2>Buying with confidence on Bilskyen</h2>
<p>This demo article showcases the <strong>{$layout}</strong> layout with the <strong>{$style}</strong> theme. Use it to review typography, spacing, and chrome sections in the public blog view.</p>
<h2>What to check before you buy</h2>
<p>Review service history, ownership tax estimates, and recent inspection notes. Compare similar listings so you know a fair market price before you enquire.</p>
<ul>
<li>Confirm the registration and equipment package</li>
<li>Ask about warranty and delivery options</li>
<li>Book a viewing or video walkthrough</li>
</ul>
<h3>Financing and next steps</h3>
<p>Many dealers can help with financing. Start with an enquiry from the listing page — include your timeline and preferred contact method so the dealer can respond quickly.</p>
<blockquote><p>Transparent listings make it easier to decide which cars deserve a closer look.</p></blockquote>
<p>When you are ready, browse the latest inventory and save favourites to compare later.</p>
HTML;
    }
}
