<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->string('layout', 64)->default('guide')->after('title');
            $table->string('style', 64)->default('brand')->after('layout');
        });

        Schema::table('cms_posts', function (Blueprint $table) {
            $table->string('layout', 64)->default('classic')->after('content_html');
            $table->string('style', 64)->default('brand')->after('layout');
            $table->json('sections')->nullable()->after('style');
        });

        $this->normalizeLandingBlocks();
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn(['layout', 'style']);
        });

        Schema::table('cms_posts', function (Blueprint $table) {
            $table->dropColumn(['layout', 'style', 'sections']);
        });
    }

    private function normalizeLandingBlocks(): void
    {
        $defaultVariants = [
            'hero' => 'centered-dark',
            'richtext' => 'narrow-prose',
            'cta' => 'banner-brand',
            'vehicle_grid' => 'cards-3',
            'faq' => 'accordion',
            'features' => 'icon-grid',
            'testimonials' => 'quote-cards',
            'stats' => 'metric-row',
            'image_text' => 'image-left',
        ];

        $contentKeys = [
            'hero' => ['headline', 'subheadline', 'cta_text', 'cta_url', 'image_url'],
            'richtext' => ['html', 'html_secondary'],
            'cta' => ['title', 'button_text', 'button_url', 'subtitle'],
            'vehicle_grid' => ['title', 'limit'],
            'faq' => ['title', 'items'],
            'features' => ['title', 'subtitle', 'items'],
            'testimonials' => ['title', 'items'],
            'stats' => ['title', 'items'],
            'image_text' => ['title', 'body', 'image_url', 'cta_text', 'cta_url'],
        ];

        $pages = DB::table('landing_pages')->select('id', 'blocks')->get();

        foreach ($pages as $page) {
            $blocks = json_decode($page->blocks ?? '[]', true);
            if (! is_array($blocks)) {
                continue;
            }

            $normalized = [];
            foreach ($blocks as $block) {
                if (! is_array($block)) {
                    continue;
                }

                $type = $block['type'] ?? 'richtext';
                if (isset($block['content']) && is_array($block['content'])) {
                    $normalized[] = [
                        'id' => $block['id'] ?? (string) Str::uuid(),
                        'type' => $type,
                        'variant' => $block['variant'] ?? ($defaultVariants[$type] ?? 'default'),
                        'content' => $block['content'],
                    ];
                    continue;
                }

                $keys = $contentKeys[$type] ?? array_keys(array_diff_key($block, ['type' => true, 'id' => true, 'variant' => true]));
                $content = [];
                foreach ($keys as $key) {
                    if (array_key_exists($key, $block)) {
                        $content[$key] = $block[$key];
                    }
                }

                $normalized[] = [
                    'id' => $block['id'] ?? (string) Str::uuid(),
                    'type' => $type,
                    'variant' => $block['variant'] ?? ($defaultVariants[$type] ?? 'default'),
                    'content' => $content,
                ];
            }

            DB::table('landing_pages')->where('id', $page->id)->update([
                'blocks' => json_encode($normalized),
                'layout' => 'guide',
                'style' => 'brand',
            ]);
        }
    }
};
