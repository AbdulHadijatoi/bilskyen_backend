<?php

namespace Tests\Unit;

use Tests\TestCase;

class HomepageOnBeaconUxTest extends TestCase
{
    public function test_homepage_search_section_uses_hero_pattern_and_collapsed_secondary_filters(): void
    {
        $home = file_get_contents(resource_path('views/home.blade.php'));

        $this->assertStringContainsString('home-search-section', $home);
        $this->assertStringContainsString('home-search-section--has-image', $home);
        $this->assertStringContainsString("id=\"home-filters-panel\" class=\"mt-0 is-collapsed\"", $home);
        $this->assertStringContainsString('home-filter-core-grid', $home);
        $this->assertLessThan(
            strpos($home, 'id="home-filters-panel"'),
            strpos($home, 'filter_help_fuel'),
            'Fuel type should be visible with brand and model, not inside the collapsed panel'
        );
        $this->assertStringContainsString('home-filter-advanced-link', $home);
        $this->assertStringContainsString('filter_help_brand', $home);
        $this->assertStringContainsString('home-trust-heading', $home);
        $this->assertStringContainsString('vehicle-card-enquire-btn', $home);
        $this->assertStringContainsString('vehicle-listing-specs', $home);
        $this->assertStringContainsString("aria-expanded=\"false\"", $home);

        $this->assertDoesNotMatchRegularExpression(
            '/class="home-filter-advanced-link[^"]*bg-primary/',
            $home
        );
    }

    public function test_filter_help_copy_exists_in_danish_and_english(): void
    {
        $keys = [
            'messages.pages.home.filter_help_brand',
            'messages.pages.home.filter_help_model',
            'messages.pages.home.filter_help_fuel',
            'messages.pages.home.filter_help_price',
            'messages.pages.home.filter_help_km',
            'messages.pages.home.filter_help_year',
        ];

        foreach (['da', 'en'] as $locale) {
            app()->setLocale($locale);
            foreach ($keys as $key) {
                $this->assertNotSame($key, __($key), "Missing translation {$key} for {$locale}");
            }
        }
    }

    public function test_site_chrome_uses_heading_token_and_footer_contrast(): void
    {
        $tokens = file_get_contents(resource_path('views/layouts/partials/design-tokens.blade.php'));
        $css = file_get_contents(resource_path('css/site-base.css'));

        $this->assertStringContainsString('--heading:', $tokens);
        $this->assertStringContainsString('--letter-spacing-label:', $tokens);
        $this->assertStringContainsString('rgba(255, 255, 255, 0.88)', $tokens);
        $this->assertStringContainsString('line-height: 1.6', $css);
        $this->assertStringContainsString('font-variant-numeric: tabular-nums', $css);
        $this->assertStringContainsString('font-family: inherit', $css);
        $this->assertStringContainsString('.site-footer h1', $css);
        $this->assertStringContainsString('color: var(--footer-foreground)', $css);
        $this->assertStringContainsString('.site-footer__nav a', $css);
        $this->assertStringContainsString('.site-footer__meta a', $css);

        $footer = file_get_contents(resource_path('views/components/footer.blade.php'));
        $this->assertStringContainsString('site-footer__cta-secondary', $footer);
        $this->assertStringContainsString('site-footer__nav', $footer);
        $this->assertStringContainsString('site-footer__meta', $footer);
    }
}
