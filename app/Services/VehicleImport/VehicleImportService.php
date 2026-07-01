<?php

namespace App\Services\VehicleImport;

use App\Constants\VehicleListStatus;
use App\Models\Dealer;
use App\Models\Vehicle;
use App\Services\Import\SpreadsheetImportParser;
use App\Services\ListingBillingService;
use App\Services\ListingExpirationService;
use App\Services\SubscriptionFeatureService;
use App\Services\VehicleService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class VehicleImportService
{
    public function __construct(
        private SpreadsheetImportParser $parser,
        private VehicleService $vehicleService,
        private SubscriptionFeatureService $subscriptionFeatureService,
        private ListingBillingService $listingBillingService,
        private ListingExpirationService $listingExpirationService,
        private \App\Services\DmrFactVehicleLookupService $dmrFactVehicleLookupService,
    ) {}

    /**
     * @return array{summary: array<string, int>, rows: list<array<string, mixed>>}
     */
    public function importFromFile(
        UploadedFile $file,
        int $dealerId,
        int $userId,
        ?Dealer $dealer,
        bool $dryRun = false,
    ): array {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath() ?: $file->getPathname();
        if ($path === '' || ! is_readable($path)) {
            throw new \InvalidArgumentException(__('messages.api.vehicle_import_file_unreadable'));
        }

        $rows = $this->parser->parse($path, $extension);

        if (count($rows) > VehicleImportColumnDefinitions::MAX_ROWS) {
            throw new \InvalidArgumentException(__('messages.api.vehicle_import_too_many_rows', [
                'max' => VehicleImportColumnDefinitions::MAX_ROWS,
            ]));
        }

        $resolver = new VehicleImportRowResolver(
            new VehicleImportLookupCache,
            $this->dmrFactVehicleLookupService,
        );

        $results = [];
        $summary = [
            'total' => count($rows),
            'created' => 0,
            'failed' => 0,
            'warnings' => 0,
        ];

        foreach ($rows as $index => $row) {
            $excelRow = $index + 2;
            $rowResult = $this->processRow($resolver, $row, $excelRow, $dealerId, $userId, $dealer, $dryRun);
            $results[] = $rowResult;

            if ($rowResult['status'] === 'failed') {
                $summary['failed']++;
            } elseif (in_array($rowResult['status'], ['created', 'created_with_warnings'], true)) {
                $summary['created']++;
            }

            if (! empty($rowResult['warnings'])) {
                $summary['warnings']++;
            }
        }

        return ['summary' => $summary, 'rows' => $results];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function processRow(
        VehicleImportRowResolver $resolver,
        array $row,
        int $excelRow,
        int $dealerId,
        int $userId,
        ?Dealer $dealer,
        bool $dryRun,
    ): array {
        $registration = trim((string) ($row['registration'] ?? ''));
        $vin = trim((string) ($row['vin'] ?? ''));
        $dmrRequested = $registration !== '' || $vin !== '';

        $resolved = $resolver->resolve($row, $dealerId, $dmrRequested);
        $payload = $resolved['payload'];
        $warnings = $resolved['warnings'];
        $errors = $resolved['errors'];
        $this->appendPaygImportWarnings($warnings, $dealer, $payload);

        $base = [
            'row' => $excelRow,
            'registration' => $registration !== '' ? $registration : ($payload['registration'] ?? null),
            'warnings' => $warnings,
            'errors' => $errors,
        ];

        if ($errors !== []) {
            return array_merge($base, [
                'status' => 'failed',
                'vehicle_id' => null,
            ]);
        }

        $limitError = $this->checkSubscriptionLimits($payload, $dealer);
        if ($limitError !== null) {
            $errors[] = $limitError;

            return array_merge($base, [
                'status' => 'failed',
                'errors' => $errors,
                'vehicle_id' => null,
            ]);
        }

        if ($dryRun) {
            return array_merge($base, [
                'status' => $warnings === [] ? 'validated' : 'validated',
                'vehicle_id' => null,
            ]);
        }

        $payload['dealer_id'] = $dealerId;
        $payload['user_id'] = $userId;

        try {
            $vehicle = DB::transaction(function () use ($payload) {
                $vehicle = $this->vehicleService->createVehicle($payload);

                if ((int) $vehicle->list_status_id === VehicleListStatus::PUBLISHED) {
                    $this->listingExpirationService->setExpiryOnPublish($vehicle, false);
                    $this->listingBillingService->onVehiclePublished($vehicle->fresh());
                }

                return $vehicle;
            });
        } catch (\Throwable $e) {
            $errors[] = [
                'field' => 'row',
                'value' => (string) $excelRow,
                'message' => $e->getMessage(),
            ];

            return array_merge($base, [
                'status' => 'failed',
                'errors' => $errors,
                'vehicle_id' => null,
            ]);
        }

        return array_merge($base, [
            'status' => $warnings === [] ? 'created' : 'created_with_warnings',
            'vehicle_id' => $vehicle->id,
            'registration' => $vehicle->registration,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{field: string, value: string, message: string}|null
     */
    private function checkSubscriptionLimits(array $payload, ?Dealer $dealer): ?array
    {
        if ($dealer === null) {
            return null;
        }

        $listStatusId = (int) ($payload['list_status_id'] ?? VehicleListStatus::PUBLISHED);
        if ($listStatusId === VehicleListStatus::PUBLISHED) {
            $publishedCount = Vehicle::where('dealer_id', $dealer->id)
                ->where('list_status_id', VehicleListStatus::PUBLISHED)
                ->count();

            if (! $this->subscriptionFeatureService->checkFeatureLimit($dealer, 'max_listings', $publishedCount)) {
                $limit = $this->subscriptionFeatureService->getFeatureLimit($dealer, 'max_listings', 0);

                return [
                    'field' => 'list_status',
                    'value' => 'published',
                    'message' => __('messages.api.max_listings_reached', ['limit' => $limit]),
                ];
            }
        }

        if (! empty($payload['lookup_equipments'])) {
            $equipmentLimit = $this->subscriptionFeatureService->getFeatureLimit($dealer, 'max_equipment_per_vehicle', 999);
            $segments = array_filter(array_map('trim', explode(',', (string) $payload['lookup_equipments'])));
            if (count($segments) > $equipmentLimit) {
                return [
                    'field' => 'equipment',
                    'value' => (string) count($segments),
                    'message' => __('messages.api.max_equipment_per_vehicle_exceeded', ['limit' => $equipmentLimit]),
                ];
            }
        }

        return null;
    }

    /**
     * @param  array<int, array{field: string, value: string, message: string}>  $warnings
     * @param  array<string, mixed>  $payload
     */
    private function appendPaygImportWarnings(array &$warnings, ?Dealer $dealer, array $payload): void
    {
        if ($dealer === null) {
            return;
        }

        $listStatusId = (int) ($payload['list_status_id'] ?? VehicleListStatus::PUBLISHED);
        if ($listStatusId !== VehicleListStatus::PUBLISHED) {
            return;
        }

        if (! $this->subscriptionFeatureService->isUsageDailyPlan($dealer)) {
            return;
        }

        $plan = $this->subscriptionFeatureService->getActiveSubscription($dealer)?->plan;
        $cents = (int) ($plan?->price_per_listing_per_day ?? 0);
        if ($cents <= 0) {
            return;
        }

        $warnings[] = [
            'field' => 'billing',
            'value' => (string) $cents,
            'message' => __('messages.api.vehicle_import_payg_row_warning', [
                'amount' => number_format($cents / 100, 2, ',', '.'),
            ]),
        ];
    }
}
