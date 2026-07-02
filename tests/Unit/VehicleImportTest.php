<?php

namespace Tests\Unit;

use App\Services\DmrFactVehicleLookupService;
use App\Services\Import\SpreadsheetImportParser;
use App\Services\VehicleImport\VehicleImportBatchContext;
use App\Services\VehicleImport\VehicleImportLookupCache;
use App\Services\VehicleImport\VehicleImportRowResolver;
use App\Support\RemoteUrlGuard;
use Mockery;
use Tests\TestCase;

class VehicleImportTest extends TestCase
{
    public function test_batch_context_tracks_seen_registrations_and_published_count(): void
    {
        $context = new VehicleImportBatchContext(9);

        $this->assertFalse($context->hasSeenRegistration('AB12345'));
        $context->markRegistrationSeen('AB12345');
        $this->assertTrue($context->hasSeenRegistration('AB12345'));

        $context->incrementPublishedCount();
        $this->assertSame(10, $context->publishedCount);
    }

    public function test_batch_context_caches_dmr_payload_by_key(): void
    {
        $context = new VehicleImportBatchContext(0);
        $payload = ['registration' => 'AB12345', 'brand' => ['id' => 1, 'name' => 'Volvo']];

        $context->cacheDmr('reg:AB12345', $payload);

        $this->assertSame($payload, $context->getCachedDmr('reg:AB12345'));
        $this->assertNull($context->getCachedDmr('reg:ZZ99999'));
    }

    public function test_row_resolver_rejects_duplicate_registration_within_same_import_file(): void
    {
        $dmr = Mockery::mock(DmrFactVehicleLookupService::class);
        $dmr->shouldReceive('normalizeRegistration')
            ->with('AB12345')
            ->andReturn('AB12345');

        $lookupCache = Mockery::mock(VehicleImportLookupCache::class);
        $lookupCache->shouldReceive('resolveBrand')->with('Volvo')->andReturn(1);
        $lookupCache->shouldReceive('resolveModel')->with('XC60', 1)->andReturn(2);
        $lookupCache->shouldReceive('resolveFlat')->andReturnUsing(function (string $column, string $value) {
            return match ($column) {
                'sales_type_id' => 1,
                'fuel_type_id' => 1,
                default => null,
            };
        });

        $context = new VehicleImportBatchContext(0);
        $context->markRegistrationSeen('AB12345');

        $resolver = new VehicleImportRowResolver($lookupCache, $dmr);

        $result = $resolver->resolve(
            [
                'registration' => 'AB12345',
                'price' => '100000',
                'sales_type' => 'Køb',
                'brand' => 'Volvo',
                'model' => 'XC60',
                'fuel_type' => 'Benzin',
            ],
            1,
            false,
            $context,
        );

        $registrationErrors = array_values(array_filter(
            $result['errors'],
            static fn (array $error) => $error['field'] === 'registration'
        ));

        $this->assertNotEmpty($registrationErrors);
        $this->assertSame(
            __('messages.api.vehicle_import_duplicate_registration_in_file'),
            $registrationErrors[0]['message']
        );
    }

    public function test_spreadsheet_parser_detects_semicolon_delimiter(): void
    {
        $this->assertSame(';', SpreadsheetImportParser::detectCsvDelimiter("A;B;C\n"));
        $this->assertSame(',', SpreadsheetImportParser::detectCsvDelimiter("A,B,C\n"));
    }

    public function test_spreadsheet_parser_strips_utf8_bom_from_header_values(): void
    {
        $this->assertSame('Registrering', SpreadsheetImportParser::stripUtf8Bom("\xEF\xBB\xBFRegistrering"));
    }

    public function test_spreadsheet_parser_parses_semicolon_csv_with_bom(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'veh_import_');
        $this->assertNotFalse($path);

        file_put_contents(
            $path,
            "\xEF\xBB\xBFRegistrering;Pris\nAB12345;249900\n"
        );

        try {
            $parser = new SpreadsheetImportParser;
            $rows = $parser->parse($path, 'csv');

            $this->assertCount(1, $rows);
            $this->assertSame('AB12345', $rows[0]['registration']);
            $this->assertSame('249900', $rows[0]['price']);
        } finally {
            @unlink($path);
        }
    }

    public function test_remote_url_guard_rejects_loopback_urls(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        RemoteUrlGuard::assertPublicHttpUrl('http://127.0.0.1/image.jpg');
    }

    public function test_remote_url_guard_accepts_public_ip_literals(): void
    {
        $this->expectNotToPerformAssertions();
        RemoteUrlGuard::assertPublicHttpUrl('http://93.184.216.34/image.jpg');
    }

    public function test_remote_url_guard_identifies_private_ip_ranges(): void
    {
        $this->assertFalse(RemoteUrlGuard::isPublicIp('10.0.0.1'));
        $this->assertFalse(RemoteUrlGuard::isPublicIp('192.168.1.1'));
        $this->assertTrue(RemoteUrlGuard::isPublicIp('8.8.8.8'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
