<?php

namespace Tests\Unit;

use App\Http\Controllers\DealerController;
use App\Helpers\FormatHelper;
use App\Models\Dealer;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class DealerPublicProfileTest extends TestCase
{
    public function test_placeholder_and_empty_slugs_are_not_public(): void
    {
        $this->assertFalse(Dealer::isPublicProfileSlug('dealer'));
        $this->assertFalse(Dealer::isPublicProfileSlug('Dealer'));
        $this->assertFalse(Dealer::isPublicProfileSlug(''));
        $this->assertFalse(Dealer::isPublicProfileSlug(null));
        $this->assertTrue(Dealer::isPublicProfileSlug('carhouse-59'));
        $this->assertTrue(Dealer::isSitemapEligible('birken-biler-17', true));
        $this->assertFalse(Dealer::isSitemapEligible('birken-biler-17', false));
        $this->assertFalse(Dealer::isSitemapEligible('dealer', true));
        $this->assertFalse(Dealer::isSitemapEligible('', true));
        $this->assertFalse(Dealer::isSitemapEligible(null, true));
    }

    public function test_placeholder_dealer_url_returns_404(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->app->make(DealerController::class)
            ->show(Request::create('/dealer-dealer'), 'dealer');
    }

    public function test_public_cvr_accepts_integer_values(): void
    {
        $this->assertTrue(FormatHelper::isValidPublicCvr(45251853));
        $this->assertTrue(FormatHelper::isValidPublicCvr('45251853'));
        $this->assertFalse(FormatHelper::isValidPublicCvr(null));
        $this->assertFalse(FormatHelper::isValidPublicCvr(''));
        $this->assertFalse(FormatHelper::isValidPublicCvr(['45251853']));
    }
}
