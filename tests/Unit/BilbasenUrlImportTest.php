<?php

namespace Tests\Unit;

use App\Models\Vehicle;
use App\Services\FileService;
use App\Services\VehicleImageUploadService;
use App\Services\VehicleImport\Bilbasen\BilbasenListingFetcher;
use App\Services\VehicleImport\Bilbasen\BilbasenListingParser;
use App\Services\VehicleImport\Bilbasen\BilbasenUrlValidator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class BilbasenUrlImportTest extends TestCase
{
    public function test_url_validator_accepts_listing_urls_and_extracts_id(): void
    {
        $validator = new BilbasenUrlValidator;
        $result = $validator->validate(
            'https://www.bilbasen.dk/brugt/bil/citron/c4-cactus/12-puretech-110-origins-5d/6929875'
        );

        $this->assertSame('6929875', $result['listing_id']);
        $this->assertSame(
            'https://www.bilbasen.dk/brugt/bil/citron/c4-cactus/12-puretech-110-origins-5d/6929875',
            $result['url']
        );
    }

    public function test_url_validator_rejects_non_bilbasen_hosts(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new BilbasenUrlValidator)->validate('https://example.com/brugt/bil/a/b/c/123');
    }

    public function test_url_validator_rejects_non_listing_paths(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new BilbasenUrlValidator)->validate('https://www.bilbasen.dk/soeg');
    }

    public function test_parser_extracts_fields_from_json_ld_fixture(): void
    {
        $html = file_get_contents(base_path('tests/Fixtures/bilbasen/listing_sample.html'));
        $this->assertIsString($html);

        $parser = new BilbasenListingParser;
        $parsed = $parser->parse(
            $html,
            'https://www.bilbasen.dk/brugt/bil/citron/c4-cactus/12-puretech-110-origins-5d/6929875',
            '6929875'
        );

        $this->assertFalse($parsed['blocked']);
        $this->assertSame('6929875', $parsed['external_listing_id']);
        $this->assertSame(129900.0, $parsed['price']);
        $this->assertSame(87500, $parsed['mileage']);
        $this->assertSame('AB12345', $parsed['registration']);
        $this->assertSame('Citroën', $parsed['brand']);
        $this->assertSame('C4 Cactus', $parsed['model']);
        $this->assertGreaterThanOrEqual(2, count($parsed['image_urls']));
    }

    public function test_parser_detects_challenge_page(): void
    {
        $html = file_get_contents(base_path('tests/Fixtures/bilbasen/challenge_page.html'));
        $this->assertIsString($html);

        $parser = new BilbasenListingParser;
        $parsed = $parser->parse(
            $html,
            'https://www.bilbasen.dk/brugt/bil/citron/c4-cactus/slug/6929875',
            '6929875'
        );

        $this->assertTrue($parsed['blocked']);
        $this->assertTrue($parser->isChallengePage($html));
    }

    public function test_fetcher_rejects_non_bilbasen_url_before_http(): void
    {
        Http::fake();

        $this->expectException(\InvalidArgumentException::class);

        $fetcher = new BilbasenListingFetcher(new BilbasenUrlValidator);
        $fetcher->fetch('https://evil.example/listing/1');

        Http::assertNothingSent();
    }

    public function test_fetcher_returns_html_for_allowed_listing(): void
    {
        $html = file_get_contents(base_path('tests/Fixtures/bilbasen/listing_sample.html'));
        $this->assertIsString($html);

        Http::fake([
            'www.bilbasen.dk/*' => Http::response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']),
        ]);

        $fetcher = new BilbasenListingFetcher(new BilbasenUrlValidator);
        $result = $fetcher->fetch(
            'https://www.bilbasen.dk/brugt/bil/citron/c4-cactus/12-puretech-110-origins-5d/6929875'
        );

        $this->assertSame('6929875', $result['listing_id']);
        $this->assertStringContainsString('application/ld+json', $result['html']);
    }

    public function test_fetcher_surfaces_challenge_html_for_parser(): void
    {
        $html = file_get_contents(base_path('tests/Fixtures/bilbasen/challenge_page.html'));
        $this->assertIsString($html);

        Http::fake([
            'www.bilbasen.dk/*' => Http::response($html, 200, ['Content-Type' => 'text/html']),
        ]);

        $fetcher = new BilbasenListingFetcher(new BilbasenUrlValidator);
        $fetched = $fetcher->fetch(
            'https://www.bilbasen.dk/brugt/bil/citron/c4-cactus/slug/6929875'
        );

        $parser = new BilbasenListingParser;
        $parsed = $parser->parse($fetched['html'], $fetched['url'], $fetched['listing_id']);

        $this->assertTrue($parsed['blocked']);
    }

    public function test_fetcher_blocks_redirect_to_non_bilbasen_host_before_following(): void
    {
        Http::fake([
            'https://www.bilbasen.dk/brugt/bil/citron/c4-cactus/slug/6929875' => Http::response(
                '',
                302,
                ['Location' => 'https://evil.example/steal']
            ),
            'evil.example/*' => Http::response('should-not-fetch', 200),
        ]);

        $this->expectException(\RuntimeException::class);

        $fetcher = new BilbasenListingFetcher(new BilbasenUrlValidator);
        try {
            $fetcher->fetch('https://www.bilbasen.dk/brugt/bil/citron/c4-cactus/slug/6929875');
        } finally {
            Http::assertNotSent(fn ($request) => str_contains($request->url(), 'evil.example'));
        }
    }

    public function test_fetcher_blocks_redirect_to_private_ip(): void
    {
        Http::fake([
            'https://www.bilbasen.dk/brugt/bil/citron/c4-cactus/slug/6929875' => Http::response(
                '',
                302,
                ['Location' => 'http://127.0.0.1/internal']
            ),
        ]);

        $this->expectException(\RuntimeException::class);

        $fetcher = new BilbasenListingFetcher(new BilbasenUrlValidator);
        $fetcher->fetch('https://www.bilbasen.dk/brugt/bil/citron/c4-cactus/slug/6929875');
    }

    public function test_fetcher_follows_allowed_bilbasen_redirect(): void
    {
        $html = file_get_contents(base_path('tests/Fixtures/bilbasen/listing_sample.html'));
        $this->assertIsString($html);

        Http::fake([
            'https://www.bilbasen.dk/brugt/bil/citron/c4-cactus/old-slug/6929875' => Http::response(
                '',
                302,
                ['Location' => 'https://www.bilbasen.dk/brugt/bil/citron/c4-cactus/12-puretech-110-origins-5d/6929875']
            ),
            'https://www.bilbasen.dk/brugt/bil/citron/c4-cactus/12-puretech-110-origins-5d/6929875' => Http::response(
                $html,
                200,
                ['Content-Type' => 'text/html; charset=utf-8']
            ),
        ]);

        $fetcher = new BilbasenListingFetcher(new BilbasenUrlValidator);
        $result = $fetcher->fetch(
            'https://www.bilbasen.dk/brugt/bil/citron/c4-cactus/old-slug/6929875'
        );

        $this->assertSame('6929875', $result['listing_id']);
        $this->assertStringContainsString('application/ld+json', $result['html']);
    }

    public function test_remote_image_download_blocks_private_redirect_destination(): void
    {
        Http::fake([
            'https://cdn.example.com/car.jpg' => Http::response('', 302, [
                'Location' => 'http://127.0.0.1/secret.jpg',
            ]),
            'http://127.0.0.1/*' => Http::response('private', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $fileService = Mockery::mock(FileService::class);
        $fileService->shouldNotReceive('uploadVehicleImage');

        $service = new VehicleImageUploadService($fileService);
        $vehicle = new Vehicle;
        $vehicle->id = 1;

        $result = $service->attachImagesFromRemoteUrls($vehicle, ['https://cdn.example.com/car.jpg']);

        $this->assertSame(0, $result['attached']);
        $this->assertNotEmpty($result['warnings']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '127.0.0.1'));
    }

    public function test_import_from_url_route_registers_idempotency_middleware(): void
    {
        $route = collect(Route::getRoutes())->first(
            static fn ($route) => (
                $route->uri() === 'api/v1/dealer/vehicles/import/from-url'
                || $route->uri() === 'api/v1/dealer/vehicles/import-from-url'
            ) && in_array('POST', $route->methods(), true)
        );

        $this->assertNotNull($route);
        $middleware = $route->gatherMiddleware();
        $this->assertTrue(
            collect($middleware)->contains(
                static fn ($item) => is_string($item) && str_contains($item, 'idempotency')
            ),
            'Expected idempotency middleware on import-from-url route'
        );
    }

    public function test_images_truncated_message_is_translated(): void
    {
        $message = __('messages.api.vehicle_import_images_truncated', ['max' => 10]);
        $this->assertStringContainsString('10', $message);
        $this->assertNotSame('messages.api.vehicle_import_images_truncated', $message);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
