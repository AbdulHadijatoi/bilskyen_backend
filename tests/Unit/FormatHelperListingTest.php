<?php

namespace Tests\Unit;

use App\Helpers\FormatHelper;
use Carbon\Carbon;
use Tests\TestCase;

class FormatHelperListingTest extends TestCase
{
    public function test_fuel_type_short_strips_parenthetical_detail(): void
    {
        $this->assertSame('Hybrid', FormatHelper::formatFuelTypeShort('Hybrid (Diesel + El)'));
        $this->assertSame('Plug-in Hybrid', FormatHelper::formatFuelTypeShort('Plug-in Hybrid'));
        $this->assertSame('Plug-in Hybrid', FormatHelper::formatFuelTypeShort('Plug-in Hybrid Electric Vehicle (PHEV)'));
        $this->assertSame('', FormatHelper::formatFuelTypeShort(null));
    }

    public function test_listing_location_uses_stored_address(): void
    {
        $this->assertSame(
            'Erhvervsbyvej 11, 8700 Horsens',
            FormatHelper::formatListingLocation('Erhvervsbyvej 11, 8700 Horsens', '8700', 'Horsens')
        );
        $this->assertSame(
            'Address, 77150 city',
            FormatHelper::formatListingLocation('Address, 77150 city', '77150', 'city')
        );
        $this->assertSame('Erhvervsbyvej 11', FormatHelper::formatListingLocation('Erhvervsbyvej 11', null, null));
        $this->assertSame('', FormatHelper::formatListingLocation(null));
        $this->assertSame('', FormatHelper::formatListingLocation('  '));
    }

    public function test_listing_card_title_appends_variant_when_missing(): void
    {
        $this->assertSame(
            'Mercedes Gle 350 De Amg Line',
            FormatHelper::formatListingCardTitle('Mercedes GLE 350 de AMG Line', 'AMG Line')
        );
        $this->assertSame(
            'Mercedes Gle 350 De 2.0 Amg Line 4Matic',
            FormatHelper::formatListingCardTitle('Mercedes GLE 350 de', '2.0 AMG Line 4Matic')
        );
    }

    public function test_new_listing_badge_uses_today_then_days_ago(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-22 12:00:00', 'Europe/Copenhagen'));

        $this->assertSame(0, FormatHelper::newListingAgeDays(now()));
        $this->assertSame(1, FormatHelper::newListingAgeDays(now()->subDay()));
        $this->assertSame(2, FormatHelper::newListingAgeDays(now()->subDays(2)));
        $this->assertSame(7, FormatHelper::newListingAgeDays(now()->subDays(7)));
        $this->assertSame(8, FormatHelper::newListingAgeDays(now()->subDays(8)));
        $this->assertSame(30, FormatHelper::newListingAgeDays(now()->subDays(30)));
        $this->assertNull(FormatHelper::newListingAgeDays(now()->subDays(8), FormatHelper::NEW_LISTING_MAX_DAYS));
        $this->assertTrue(FormatHelper::isNewListing(now()->subDays(7)));
        $this->assertFalse(FormatHelper::isNewListing(now()->subDays(8)));
        $this->assertFalse(FormatHelper::isNewListing(null));

        $this->assertSame('today', FormatHelper::newListingBadgeTone(now()));
        $this->assertSame('recent', FormatHelper::newListingBadgeTone(now()->subDay()));
        $this->assertSame('recent', FormatHelper::newListingBadgeTone(now()->subDays(7)));
        $this->assertSame('older', FormatHelper::newListingBadgeTone(now()->subDays(8)));
        $this->assertNull(FormatHelper::newListingBadgeTone(null));

        app()->setLocale('en');
        $this->assertSame('New today', FormatHelper::newListingBadgeLabel(now()));
        $this->assertSame('1 day ago', FormatHelper::newListingBadgeLabel(now()->subDay()));
        $this->assertSame('2 days ago', FormatHelper::newListingBadgeLabel(now()->subDays(2)));
        $this->assertSame('8 days ago', FormatHelper::newListingBadgeLabel(now()->subDays(8)));

        app()->setLocale('da');
        $this->assertSame('Ny i dag', FormatHelper::newListingBadgeLabel(now()));
        $this->assertSame('1 dag siden', FormatHelper::newListingBadgeLabel(now()->subDay()));
        $this->assertSame('2 dage siden', FormatHelper::newListingBadgeLabel(now()->subDays(2)));
        $this->assertSame('8 dage siden', FormatHelper::newListingBadgeLabel(now()->subDays(8)));

        Carbon::setTestNow();
    }
}
