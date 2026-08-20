<?php

namespace Tests\Unit;

use App\Models\CmsPost;
use App\Models\LandingPage;
use App\Models\SeoPage;
use App\Services\SeoService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Mockery;
use Tests\TestCase;

class CmsSeoMetaTest extends TestCase
{
    protected function tearDown(): void
    {
        URL::forceRootUrl(null);
        URL::forceScheme(null);
        Mockery::close();
        parent::tearDown();
    }

    public function test_blog_post_defaults_from_model_when_seo_pages_missing(): void
    {
        $post = new CmsPost([
            'slug' => 'used-car-checks',
            'title' => 'Five checks before buying',
            'excerpt' => 'Check history, rust, and a test drive.',
            'meta_title' => '',
            'meta_description' => '',
            'published_at' => now(),
        ]);
        $post->setRelation('featuredMedia', null);

        $seo = Mockery::mock(SeoService::class)->makePartial();
        $seo->shouldReceive('getForPage')->once()->with('blog', 'used-car-checks')->andReturn(null);

        $resolved = $seo->resolveForCmsPost($post);

        $this->assertSame('Five checks before buying', $resolved['meta_title']);
        $this->assertSame('Check history, rust, and a test drive.', $resolved['meta_description']);
        $this->assertSame('index, follow', $resolved['robots']);
        $this->assertSame(route('blog.show', 'used-car-checks'), $resolved['canonical_url']);
        $this->assertSame('Article', $resolved['schema_type']);
        $this->assertIsArray($resolved['schema_json']);
        $this->assertSame('Article', $resolved['schema_json']['@type']);
        $this->assertSame('https://schema.org', $resolved['schema_json']['@context']);
    }

    public function test_blog_post_non_empty_overlay_wins_empty_overlay_ignored(): void
    {
        $post = new CmsPost([
            'slug' => 'used-car-checks',
            'title' => 'Five checks before buying',
            'excerpt' => 'Check history, rust, and a test drive.',
            'meta_title' => 'CMS meta title',
            'meta_description' => 'CMS meta description',
        ]);
        $post->setRelation('featuredMedia', null);

        $seo = Mockery::mock(SeoService::class)->makePartial();
        $seo->shouldReceive('getForPage')->once()->with('blog', 'used-car-checks')->andReturn([
            'meta_title' => 'Override title | Bilskyen',
            'meta_description' => '',
            'og_title' => null,
        ]);

        $resolved = $seo->resolveForCmsPost($post);

        $this->assertSame('Override title | Bilskyen', $resolved['meta_title']);
        $this->assertSame('CMS meta description', $resolved['meta_description']);
        $this->assertSame('CMS meta title', $resolved['og_title']);
    }

    public function test_landing_page_defaults_and_webpage_schema(): void
    {
        $page = new LandingPage([
            'slug' => 'other-guide',
            'title' => 'Other guide',
            'meta_title' => '',
            'meta_description' => '',
        ]);

        $seo = Mockery::mock(SeoService::class)->makePartial();
        $seo->shouldReceive('getForPage')->once()->with('landing', 'other-guide')->andReturn(null);

        $resolved = $seo->resolveForLandingPage($page);

        $this->assertSame('Other guide', $resolved['meta_title']);
        $this->assertNull($resolved['meta_description']);
        $this->assertSame(route('landing.show', 'other-guide'), $resolved['canonical_url']);
        $this->assertSame('WebPage', $resolved['schema_type']);
        $this->assertIsArray($resolved['schema_json']);
        $this->assertSame('WebPage', $resolved['schema_json']['@type']);
    }

    public function test_landing_empty_overlay_does_not_wipe_cms_description(): void
    {
        $page = new LandingPage([
            'slug' => 'brugte-elbiler',
            'title' => 'Brugte elbiler',
            'meta_description' => 'Find used EVs on Bilskyen.',
        ]);

        $seo = Mockery::mock(SeoService::class)->makePartial();
        $seo->shouldReceive('getForPage')->once()->with('landing', 'brugte-elbiler')->andReturn([
            'meta_description' => '',
            'meta_title' => 'SEO landing title',
        ]);

        $resolved = $seo->resolveForLandingPage($page);

        $this->assertSame('SEO landing title', $resolved['meta_title']);
        $this->assertSame('Find used EVs on Bilskyen.', $resolved['meta_description']);
    }

    public function test_sitemap_blog_urls_use_https_route_not_http_app_url_concatenation(): void
    {
        config(['app.url' => 'https://bilskyen.dk']);
        URL::forceRootUrl('https://bilskyen.dk');
        URL::forceScheme('https');

        $this->assertSame('https://bilskyen.dk/blog/used-car-checks', route('blog.show', 'used-car-checks'));
        $this->assertSame('https://bilskyen.dk/blog', route('blog.index'));
        $this->assertSame('https://bilskyen.dk/guides/brugte-elbiler', route('landing.show', 'brugte-elbiler'));

        $source = file_get_contents(app_path('Services/SeoService.php'));
        $this->assertIsString($source);
        $this->assertStringContainsString("route('blog.show'", $source);
        $this->assertStringContainsString("route('blog.index')", $source);
        $this->assertStringNotContainsString("\$baseUrl.'/blog/'.\$post->slug", $source);
        $this->assertStringNotContainsString('$baseUrl . \'/blog\'', $source);
    }

    public function test_get_for_page_miss_does_not_throw_for_blog_index(): void
    {
        Cache::flush();
        Cache::put(SeoPage::getCacheKey('static', 'blog'), false, 60);
        Cache::put(SeoPage::getCacheKey('blog', 'missing-slug'), false, 60);
        Cache::put(SeoPage::getCacheKey('landing', 'missing-slug'), false, 60);

        $seo = app(SeoService::class);
        $this->assertNull($seo->getForPage('static', 'blog'));
        $this->assertNull($seo->getForPage('blog', 'missing-slug'));
        $this->assertNull($seo->getForPage('landing', 'missing-slug'));
    }
}
