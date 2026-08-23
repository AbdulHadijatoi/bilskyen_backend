<?php

namespace Tests\Unit;

use App\Constants\VehicleListStatus;
use App\Models\Dealer;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DealerContextService;
use App\Support\VehicleListingAccess;
use Mockery;
use Tests\TestCase;

class VehicleListingAccessTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_owner_can_preview_unpublished_draft(): void
    {
        $owner = $this->user(11);
        $vehicle = $this->vehicle([
            'user_id' => 11,
            'dealer_id' => 3,
            'list_status_id' => VehicleListStatus::DRAFT,
        ]);

        $this->assertTrue(VehicleListingAccess::canViewWebPdp($owner, $vehicle, $this->dealers(null)));
        $this->assertSame(200, $this->previewStatus($owner, $vehicle, $this->dealers(null)));
    }

    public function test_dealer_staff_can_preview_unpublished_when_dealer_matches(): void
    {
        $staff = $this->user(22);
        $vehicle = $this->vehicle([
            'user_id' => 11,
            'dealer_id' => 3,
            'list_status_id' => VehicleListStatus::PENDING_REVIEW,
        ]);
        $dealer = new Dealer();
        $dealer->id = 3;

        $this->assertTrue(VehicleListingAccess::canViewWebPdp($staff, $vehicle, $this->dealers($dealer)));
        $this->assertSame(200, $this->previewStatus($staff, $vehicle, $this->dealers($dealer)));
    }

    public function test_stranger_cannot_preview_unpublished_draft(): void
    {
        $stranger = $this->user(99);
        $vehicle = $this->vehicle([
            'user_id' => 11,
            'dealer_id' => 3,
            'list_status_id' => VehicleListStatus::DRAFT,
        ]);

        $this->assertFalse(VehicleListingAccess::canViewWebPdp($stranger, $vehicle, $this->dealers(null)));
        $this->assertSame(404, $this->previewStatus($stranger, $vehicle, $this->dealers(null)));
    }

    public function test_guest_cannot_preview_unpublished_draft(): void
    {
        $vehicle = $this->vehicle([
            'user_id' => 11,
            'list_status_id' => VehicleListStatus::DRAFT,
        ]);

        $this->assertFalse(VehicleListingAccess::canViewWebPdp(null, $vehicle, $this->dealers(null)));
        $this->assertSame(404, $this->previewStatus(null, $vehicle, $this->dealers(null)));
    }

    public function test_sold_listing_stays_public(): void
    {
        $stranger = $this->user(99);
        $vehicle = $this->vehicle([
            'user_id' => 11,
            'list_status_id' => VehicleListStatus::SOLD,
        ]);

        $this->assertTrue(VehicleListingAccess::canViewWebPdp($stranger, $vehicle, $this->dealers(null)));
        $this->assertTrue($vehicle->isPubliclyViewable());
        $this->assertSame(200, $this->previewStatus($stranger, $vehicle, $this->dealers(null)));
    }

    public function test_pdp_preview_hides_enquire_and_is_noindex(): void
    {
        $blade = file_get_contents(resource_path('views/vehicle-detail.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/HomeController.php'));
        $api = file_get_contents(app_path('Http/Controllers/VehicleController.php'));
        $enquiry = file_get_contents(app_path('Http/Controllers/EnquiryController.php'));

        $this->assertStringContainsString('VehicleListingAccess::canViewWebPdp', $controller);
        $this->assertStringContainsString('listingIsDraftPreview', $controller);
        $this->assertStringContainsString('listingContactBlocked', $blade);
        $this->assertStringContainsString('draft_preview_banner', $blade);
        $this->assertStringContainsString('<x-enquiry-dialog type="enquiry"', $blade);
        $this->assertStringContainsString('@if(! $listingContactBlocked)', $blade);
        $this->assertStringContainsString('isPubliclyViewable()', $api);
        $this->assertStringContainsString('isPublished()', $enquiry);
    }

    private function previewStatus(?User $user, Vehicle $vehicle, DealerContextService $dealers): int
    {
        return VehicleListingAccess::canViewWebPdp($user, $vehicle, $dealers) ? 200 : 404;
    }

    private function user(int $id): User
    {
        $user = new User();
        $user->id = $id;

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function vehicle(array $attrs): Vehicle
    {
        return new Vehicle($attrs);
    }

    private function dealers(?Dealer $dealer): DealerContextService
    {
        $mock = Mockery::mock(DealerContextService::class);
        $mock->shouldReceive('getCurrentDealer')->andReturn($dealer);

        return $mock;
    }
}
