<?php

namespace Tests\Unit;

use App\Constants\VehicleListStatus;
use App\Http\Controllers\EnquiryController;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class EnquiryVisibilityGuardTest extends TestCase
{
    public function test_published_listings_are_not_blocked(): void
    {
        $vehicle = new Vehicle(['list_status_id' => VehicleListStatus::PUBLISHED]);

        $this->assertNull($this->blockedResponse($vehicle));
    }

    public function test_sold_listings_return_unprocessable_and_do_not_accept_leads(): void
    {
        $vehicle = new Vehicle(['list_status_id' => VehicleListStatus::SOLD]);

        $response = $this->blockedResponse($vehicle);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString(
            __('messages.pages.vehicles.detail.sold_no_enquire'),
            (string) $response->getContent()
        );
    }

    public function test_unpublished_listings_are_not_found(): void
    {
        $vehicle = new Vehicle(['list_status_id' => VehicleListStatus::DRAFT]);

        $this->expectException(NotFoundHttpException::class);
        $this->blockedResponse($vehicle);
    }

    public function test_form_get_aborts_when_not_published(): void
    {
        $vehicle = new Vehicle(['list_status_id' => VehicleListStatus::SOLD]);

        $this->expectException(NotFoundHttpException::class);
        $this->abortUnlessPublished($vehicle);
    }

    public function test_phone_reveal_category_matches_en_and_da_labels(): void
    {
        $method = new ReflectionMethod(EnquiryController::class, 'isPhoneRevealCategory');
        $method->setAccessible(true);
        $controller = $this->controller();

        $this->assertTrue($method->invoke($controller, 'Phone Number Revealed'));
        $this->assertTrue($method->invoke($controller, trans('messages.forms.phone_number_revealed', [], 'da')));
        $this->assertFalse($method->invoke($controller, 'WhatsApp Clicked'));
        $this->assertFalse($method->invoke($controller, 'Enquire'));
        $this->assertFalse($method->invoke($controller, null));
    }

    public function test_phone_reveal_does_not_create_lead_or_notify_dealer(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/EnquiryController.php'));
        $enquireStart = strpos($source, 'public function enquire(');
        $enquireEnd = strpos($source, 'public function showEnquiryForm');
        $this->assertNotFalse($enquireStart);
        $this->assertNotFalse($enquireEnd);

        $enquire = substr($source, $enquireStart, $enquireEnd - $enquireStart);
        $earlyReturnStart = strpos($enquire, 'isPhoneRevealCategory');
        $leadCreatePos = strpos($enquire, 'Lead::create');
        $this->assertNotFalse($earlyReturnStart);
        $this->assertNotFalse($leadCreatePos);
        $this->assertLessThan($leadCreatePos, $earlyReturnStart);

        $earlyReturn = substr($enquire, $earlyReturnStart, $leadCreatePos - $earlyReturnStart);
        $this->assertStringContainsString("'lead_id' => null", $earlyReturn);
        $this->assertStringContainsString("'meta_lead_event_id' => null", $earlyReturn);
        $this->assertStringContainsString('resolveContactPhone', $earlyReturn);
        $this->assertStringNotContainsString('sendVehicleEnquiryEmail', $earlyReturn);
        $this->assertStringNotContainsString('notifyEnquiryRecipients', $earlyReturn);
        $this->assertStringNotContainsString('dispatchMetaLead', $earlyReturn);
    }

    public function test_resolve_contact_phone_prefers_dealer_owner(): void
    {
        $method = new ReflectionMethod(EnquiryController::class, 'resolveContactPhone');
        $method->setAccessible(true);

        $vehicle = new Vehicle();
        $vehicle->setRelation('dealer', null);
        $vehicle->setRelation('user', new \App\Models\User(['phone' => '11111111']));

        $this->assertSame('11111111', $method->invoke($this->controller(), $vehicle));
    }

    private function blockedResponse(Vehicle $vehicle): ?JsonResponse
    {
        $method = new ReflectionMethod(EnquiryController::class, 'enquiryBlockedResponse');
        $method->setAccessible(true);

        return $method->invoke($this->controller(), $vehicle);
    }

    private function abortUnlessPublished(Vehicle $vehicle): void
    {
        $method = new ReflectionMethod(EnquiryController::class, 'abortUnlessPublishedForEnquiry');
        $method->setAccessible(true);
        $method->invoke($this->controller(), $vehicle);
    }

    private function controller(): EnquiryController
    {
        return $this->app->make(EnquiryController::class);
    }
}
