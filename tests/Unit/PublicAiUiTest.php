<?php

namespace Tests\Unit;

use Tests\TestCase;

class PublicAiUiTest extends TestCase
{
    public function test_home_lifestyle_chips_are_gated_behind_public_ai(): void
    {
        $home = file_get_contents(resource_path('views/home.blade.php'));
        $chipPos = strpos($home, 'ai-search-example-chip lifestyle');
        $gatePos = strpos($home, '@if(!empty($publicAiEnabled))');

        $this->assertNotFalse($chipPos);
        $this->assertNotFalse($gatePos);
        $this->assertLessThan($chipPos, $gatePos);
        $this->assertStringContainsString('$lifestyleChips', $home);
    }

    public function test_navbar_gates_ai_car_match_behind_public_ai(): void
    {
        $navbar = file_get_contents(resource_path('views/components/navbar.blade.php'));

        $this->assertStringContainsString('@if(!empty($publicAiEnabled))', $navbar);
        $this->assertSame(2, substr_count($navbar, "route('find-perfect-car')"));
    }

    public function test_navbar_favorites_icon_sits_beside_notifications_not_in_profile_menu(): void
    {
        $navbar = file_get_contents(resource_path('views/components/navbar.blade.php'));
        $authStatus = file_get_contents(resource_path('views/components/user-auth-status.blade.php'));
        $favorites = file_get_contents(resource_path('views/components/navbar-favorites.blade.php'));

        $this->assertStringContainsString("components.navbar-favorites", $navbar);
        $this->assertStringContainsString("components.navbar-saved-searches", $navbar);
        $this->assertLessThan(
            strpos($navbar, "components.marketplace-notifications"),
            strpos($navbar, "components.navbar-favorites")
        );
        $this->assertStringContainsString("route('favorites')", $favorites);
        $this->assertStringContainsString('rounded-full bg-primary-foreground/10', $favorites);
        $this->assertStringContainsString("\$showFavorites ? 'inline-flex' : 'hidden'", $favorites);
        $this->assertStringContainsString('flex items-center gap-2 md:gap-3" data-navbar-auth', $navbar);
        $this->assertStringNotContainsString("route('favorites')", $authStatus);
        $this->assertStringNotContainsString('my_favorites', $authStatus);
    }

    public function test_vehicles_search_bar_is_not_made_sticky_on_scroll(): void
    {
        $source = file_get_contents(resource_path('views/vehicles.blade.php'));

        $this->assertStringContainsString('id="search-bar-container"', $source);
        $this->assertStringNotContainsString("searchBarContainer.classList.add('sticky'", $source);
        $this->assertStringNotContainsString('handleStickySearchBar', $source);
    }

    public function test_vehicles_listing_uses_keyword_search_not_ai_field(): void
    {
        $source = file_get_contents(resource_path('views/vehicles.blade.php'));

        $this->assertSame(1, substr_count($source, 'let equipmentFiltersLoaded'));
        $this->assertStringContainsString("id=\"search-input\"", $source);
        $this->assertStringContainsString("__('messages.forms.search_placeholder')", $source);
        $this->assertStringNotContainsString('id="vehicles-ai-examples"', $source);
        $this->assertStringNotContainsString('renderExampleChips', $source);
        $this->assertStringNotContainsString('lg:sticky', $source);
    }
}
