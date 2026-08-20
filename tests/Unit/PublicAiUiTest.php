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

    public function test_vehicles_search_bar_is_not_made_sticky_on_scroll(): void
    {
        $source = file_get_contents(resource_path('views/vehicles.blade.php'));

        $this->assertStringContainsString('id="search-bar-container"', $source);
        $this->assertStringNotContainsString("searchBarContainer.classList.add('sticky'", $source);
        $this->assertStringNotContainsString('handleStickySearchBar', $source);
    }
}
