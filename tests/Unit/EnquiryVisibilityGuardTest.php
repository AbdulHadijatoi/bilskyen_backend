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
