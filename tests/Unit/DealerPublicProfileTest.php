<?php

namespace Tests\Unit;

use App\Http\Controllers\DealerController;
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
    }

    public function test_placeholder_dealer_url_returns_404(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->app->make(DealerController::class)
            ->show(Request::create('/dealer-dealer'), 'dealer');
    }
}
