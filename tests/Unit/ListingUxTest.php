<?php

namespace Tests\Unit;

use Tests\TestCase;

class ListingUxTest extends TestCase
{
    public function test_listing_page_uses_progressive_disclosure(): void
    {
        $source = file_get_contents(resource_path('views/vehicles.blade.php'));

        $this->assertStringContainsString('id="listing-advanced-filters"', $source);
        $this->assertStringContainsString('id="listing-more-filters-toggle"', $source);
        $this->assertStringContainsString("messages.pages.vehicles.more_filters", $source);
        $this->assertStringNotContainsString('data-listing-save-search', $source);
        $this->assertStringNotContainsString('<x-listing-compare-tray', $source);
        $this->assertStringContainsString('id="listing-results-bar"', $source);
        $this->assertStringContainsString('id="listing-filters-applied"', $source);
        $this->assertStringContainsString("variant=\"sidebar\"", $source);
        $this->assertStringNotContainsString('<x-popular-cities class="rounded-xl border border-border bg-card p-4" />', $source);
        $this->assertStringNotContainsString('lg:sticky', $source);
        $this->assertStringContainsString('pagination-page-current', $source);
        $this->assertStringContainsString('listing-skeleton-card', $source);
    }

    public function test_listing_cards_keep_primary_view_and_outline_enquire(): void
    {
        $card = file_get_contents(resource_path('views/components/vehicle-listing-item.blade.php'));

        $this->assertStringContainsString('bg-primary', $card);
        $this->assertStringContainsString("messages.pages.vehicles.view_details", $card);
        $this->assertStringContainsString('vehicle-card-enquire-btn', $card);
        $this->assertStringContainsString("messages.pages.vehicles.enquire", $card);
        $this->assertStringNotContainsString('Se alle fordele', $card);
        $this->assertStringNotContainsString('data-compare-toggle', $card);
        $this->assertStringContainsString('bg-muted', $card);
        $this->assertStringContainsString('object-cover', $card);
        $this->assertStringContainsString('vehicle-listing-price', $card);
        $this->assertStringContainsString('text-2xl font-extrabold', $card);
    }

    public function test_list_view_keeps_enquire_button_at_content_width(): void
    {
        $source = file_get_contents(resource_path('views/vehicles.blade.php'));

        $this->assertStringContainsString(
            '#vehicle-container[data-view="list"] .vehicle-item-footer',
            $source
        );
        $this->assertStringContainsString('min-width: min-content', $source);
        $this->assertStringContainsString(
            '.vehicle-actions-section > .vehicle-card-enquire-btn',
            $source
        );
        $this->assertStringContainsString('flex: 0 0 auto', $source);
        $this->assertStringContainsString('white-space: normal', $source);
        $this->assertStringContainsString('-webkit-line-clamp: unset', $source);
        $this->assertStringContainsString('grid-template-columns: subgrid', $source);
        $this->assertStringContainsString('grid-template-columns: minmax(8rem, 200px) minmax(0, 1fr)', $source);
    }

    public function test_listing_copy_exists_in_danish_and_english(): void
    {
        $en = include resource_path('lang/en/messages.php');
        $da = include resource_path('lang/da/messages.php');

        foreach (['more_filters', 'fewer_filters', 'filters_applied'] as $key) {
            $this->assertNotEmpty($en['pages']['vehicles'][$key] ?? null, "Missing EN {$key}");
            $this->assertNotEmpty($da['pages']['vehicles'][$key] ?? null, "Missing DA {$key}");
        }
    }

    public function test_popular_cities_sidebar_variant_is_compact(): void
    {
        $source = file_get_contents(resource_path('views/components/popular-cities.blade.php'));

        $this->assertStringContainsString("'variant' => 'strip'", $source);
        $this->assertStringContainsString('$variant === \'sidebar\'', $source);
        $this->assertStringContainsString('listing-popular-cities', $source);
        $this->assertStringContainsString('filter-pill', $source);
    }
}
