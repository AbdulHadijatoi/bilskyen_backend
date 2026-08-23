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
        $this->assertStringContainsString('data-listing-save-search', $source);
        $this->assertStringNotContainsString('data-listing-saved-searches-link', $source);
        $this->assertStringContainsString('saveCurrentSearch', $source);
        $this->assertStringContainsString('<x-recently-viewed-rail', $source);
        $this->assertStringContainsString(':lg-cols="3"', $source);
        $this->assertStringContainsString('name="radius_km"', $source);
        $this->assertStringContainsString("messages.forms.seller_distance_km", $source);
        $this->assertStringNotContainsString('<x-listing-compare-tray', $source);
        $this->assertStringNotContainsString('data-listing-map', $source);
        $this->assertStringNotContainsString('data-compare-toggle', $source);
        $this->assertStringContainsString('id="listing-results-bar"', $source);
        $this->assertStringContainsString('id="listing-main-column"', $source);
        $this->assertLessThan(
            strpos($source, 'id="filter-sidebar"'),
            strpos($source, 'id="pagination-wrap"')
        );
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
        $this->assertStringContainsString('newListingBadgeLabel', $card);
        $this->assertStringContainsString('newListingBadgeTone', $card);
        $this->assertStringContainsString('is-{{ $newListingBadgeTone }}', $card);
        $this->assertStringContainsString('vehicle-listing-badges', $card);
        $this->assertStringContainsString('vehicle-listing-photo-count', $card);
        $this->assertStringContainsString('vehicle-listing-overlays', $card);
        $this->assertStringContainsString('vehicle-listing-favorite', $card);
        $this->assertStringContainsString('line-clamp-2', $card);
        $this->assertStringNotContainsString('Se alle fordele', $card);
        $this->assertStringNotContainsString('data-compare-toggle', $card);
        $this->assertStringContainsString('bg-muted', $card);
        $this->assertStringContainsString('object-cover', $card);
        $this->assertStringContainsString('vehicle-listing-price', $card);
        $this->assertStringContainsString('text-2xl font-extrabold', $card);
        $this->assertStringContainsString('font-weight: 800', $card);
        $this->assertStringContainsString('font-size: 1.5rem', $card);

        $this->assertStringContainsString('vehicle-listing-chip', $card);
        $this->assertStringContainsString('M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z', $card);

        $css = file_get_contents(resource_path('css/site-base.css'));
        $this->assertStringContainsString('.vehicle-listing-price', $css);
        $this->assertStringContainsString('font-weight: 800', $css);
        $this->assertStringContainsString('font-size: 1.5rem', $css);
        $this->assertStringContainsString('.vehicle-listing-new-badge', $css);
        $this->assertStringContainsString('background: #16a34a', $css);
        $this->assertStringContainsString('.vehicle-listing-new-badge.is-recent', $css);
        $this->assertStringContainsString('.vehicle-listing-new-badge.is-older', $css);
        $this->assertStringContainsString('background: #2563eb', $css);
        $this->assertStringContainsString('background: #64748b', $css);
        $this->assertStringContainsString('.vehicle-listing-chip', $css);
        $this->assertStringContainsString('background: #f3f4f6', $css);
    }

    public function test_list_view_keeps_enquire_button_at_content_width(): void
    {
        $source = file_get_contents(resource_path('views/vehicles.blade.php'));

        $this->assertStringContainsString(
            '#vehicle-container[data-view="list"] .vehicle-item-footer',
            $source
        );
        $this->assertStringContainsString(
            '.vehicle-actions-section > .vehicle-card-enquire-btn',
            $source
        );
        $this->assertStringContainsString('flex: 0 0 auto', $source);
        $this->assertStringContainsString('aspect-ratio: 1 / 1', $source);
        $this->assertStringContainsString('white-space: normal', $source);
        $this->assertStringContainsString('-webkit-line-clamp: 2', $source);
        $this->assertStringContainsString('vehicle-listing-overlays', $source);
        $this->assertStringContainsString('grid-column: 2', $source);
        $this->assertStringContainsString('grid-template-columns: minmax(8rem, 200px) minmax(0, 1fr)', $source);
        $this->assertStringContainsString('vehicle-listing-photo-count', $source);
        $this->assertStringContainsString('newListingBadgeLabel', $source);
        $this->assertStringContainsString('newListingBadgeTone', $source);
        $this->assertStringContainsString('is-${newListingTone}', $source);
        $this->assertStringContainsString('#vehicle-container .vehicle-listing-chip', $source);
        $this->assertStringContainsString('background: #f3f4f6', $source);
    }

    public function test_listing_copy_exists_in_danish_and_english(): void
    {
        $en = include resource_path('lang/en/messages.php');
        $da = include resource_path('lang/da/messages.php');

        foreach (['more_filters', 'fewer_filters', 'filters_applied', 'new_listing_today', 'new_listing_days_ago', 'photo_count_label', 'save_search', 'save_search_ok', 'save_search_fail', 'recently_viewed_title', 'compare_tray_title', 'save_search_view', 'listing_map_title'] as $key) {
            $this->assertNotEmpty($en['pages']['vehicles'][$key] ?? null, "Missing EN {$key}");
            $this->assertNotEmpty($da['pages']['vehicles'][$key] ?? null, "Missing DA {$key}");
        }
        $this->assertArrayHasKey('compare_tray_title', $en['pages']['vehicles']);
        $this->assertArrayHasKey('compare_tray_title', $da['pages']['vehicles']);
        $this->assertNotEmpty($en['forms']['seller_distance_km'] ?? null);
        $this->assertNotEmpty($da['forms']['seller_distance_km'] ?? null);
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
