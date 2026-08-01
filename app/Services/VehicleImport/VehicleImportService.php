<?php

namespace App\Services\VehicleImport;

use App\Constants\VehicleListStatus;
use App\Models\Dealer;
use App\Services\DealerVehicleAddressService;
use App\Services\DealerInvoiceService;
use App\Services\DealerListingQuotaService;
use App\Services\Import\SpreadsheetImportParser;
use App\Services\ListingBillingService;
use App\Services\ListingExpirationService;
use App\Services\SubscriptionFeatureService;
use App\Services\VehicleImageUploadService;
use App\Services\VehicleService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VehicleImportService
{
    public function __construct(
        private SpreadsheetImportParser $parser,
        private VehicleService $vehicleService,
        private VehicleImageUploadService $vehicleImageUploadService,
        private SubscriptionFeatureService $subscriptionFeatureService,
        private ListingBillingService $listingBillingService,
        private ListingExpirationService $listingExpirationService,
        private DealerListingQuotaService $listingQuotaService,
        private DealerInvoiceService $dealerInvoiceService,
        private \App\Services\DmrFactVehicleLookupService $dmrFactVehicleLookupService,
        private DealerVehicleAddressService $dealerVehicleAddressService,
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
        ?callable $onRowComplete = null,
    ): array {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath() ?: $file->getPathname();
        if ($path === '' || ! is_readable($path)) {
            throw new \InvalidArgumentException(__('messages.api.vehicle_import_file_unreadable'));
        }

        return $this->importFromPath($path, $extension, $dealerId, $userId, $dealer, $dryRun, $onRowComplete);
    }

    /**
     * @return array{summary: array<string, int>, rows: list<array<string, mixed>>}
     */
    public function importFromPath(
        string $path,
        string $extension,
        int $dealerId,
        int $userId,
        ?Dealer $dealer,
        bool $dryRun = false,
        ?callable $onRowComplete = null,
    ): array {
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

        $initialPublishedCount = $dealer !== null
            ? $this->listingQuotaService->countPublishedListings($dealer)
            : 0;

        $context = new VehicleImportBatchContext($initialPublishedCount);

        $results = [];
        $summary = $this->emptySummary(count($rows));

        foreach ($rows as $index => $row) {
            $excelRow = $index + 2;
            $rowResult = $this->processRow(
                $resolver,
                $row,
                $excelRow,
                $dealerId,
                $userId,
                $dealer,
                $dryRun,
                $context,
            );
            $results[] = $rowResult;
            $this->accumulateSummary($summary, $rowResult, $dryRun);

            if ($onRowComplete !== null) {
                $onRowComplete($summary, $results);
            }
        }

        return ['summary' => $summary, 'rows' => $results];
    }

    /**
     * @return array{total: int, created: int, validated: int, failed: int, warnings: int}
     */
    private function emptySummary(int $total): array
    {
        return [
            'total' => $total,
            'created' => 0,
            'validated' => 0,
            'failed' => 0,
            'warnings' => 0,
        ];
    }

    /**
     * @param  array{total: int, created: int, validated: int, failed: int, warnings: int}  $summary
     * @param  array<string, mixed>  $rowResult
     */
    private function accumulateSummary(array &$summary, array $rowResult, bool $dryRun): void
    {
        if ($rowResult['status'] === 'failed') {
            $summary['failed']++;
        } elseif ($dryRun && in_array($rowResult['status'], ['validated', 'validated_with_warnings'], true)) {
            $summary['validated']++;
        } elseif (! $dryRun && in_array($rowResult['status'], ['created', 'created_with_warnings'], true)) {
            $summary['created']++;
        }

        if (! empty($rowResult['warnings'])) {
            $summary['warnings']++;
        }
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
        VehicleImportBatchContext $context,
    ): array {
        $imageUrls = $this->parseImageUrls($row['image_urls'] ?? '');
        unset($row['image_urls']);

        $registration = trim((string) ($row['registration'] ?? ''));
        $vin = trim((string) ($row['vin'] ?? ''));
        $dmrRequested = $registration !== '' || $vin !== '';

        $resolved = $resolver->resolve($row, $dealerId, $dmrRequested, $context);
        $payload = $resolved['payload'];
        $warnings = $resolved['warnings'];
        $errors = $resolved['errors'];
        $this->appendPaygImportWarnings($warnings, $dealer, $payload);

        $imageUrls = $this->limitImageUrlsToPlan($imageUrls, $dealer, $warnings);

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

        $blockingInvoiceError = $this->checkBlockingInvoice($dealer, $payload);
        if ($blockingInvoiceError !== null) {
            $errors[] = $blockingInvoiceError;

            return array_merge($base, [
                'status' => 'failed',
                'errors' => $errors,
                'vehicle_id' => null,
            ]);
        }

        $limitError = $this->checkSubscriptionLimits($payload, $dealer, $context, $warnings);
        if ($limitError !== null) {
            $errors[] = $limitError;

            return array_merge($base, [
                'status' => 'failed',
                'errors' => $errors,
                'warnings' => $warnings,
                'vehicle_id' => null,
            ]);
        }

        $normalizedRegistration = trim((string) ($payload['registration'] ?? ''));
        if ($normalizedRegistration !== '') {
            $context->markRegistrationSeen($normalizedRegistration);
        }

        if ($dryRun) {
            if ((int) ($payload['list_status_id'] ?? VehicleListStatus::PUBLISHED) === VehicleListStatus::PUBLISHED) {
                $context->incrementPublishedCount();
            }

            return array_merge($base, [
                'status' => $warnings === [] ? 'validated' : 'validated_with_warnings',
                'vehicle_id' => null,
            ]);
        }

        $payload['dealer_id'] = $dealerId;
        $payload['user_id'] = $userId;
        if ($dealer !== null) {
            // Spreadsheet address columns must never override the dealer profile.
            $payload = $this->dealerVehicleAddressService->applyToPayload($payload, $dealer);
        }

        try {
            $imageWarnings = [];
            $vehicle = DB::transaction(function () use ($payload, $imageUrls, &$imageWarnings) {
                $vehicle = $this->vehicleService->createVehicle($payload);

                if ((int) $vehicle->list_status_id === VehicleListStatus::PUBLISHED) {
                    $this->listingExpirationService->setExpiryOnPublish($vehicle, false);
                    $this->listingBillingService->onVehiclePublished($vehicle->fresh());
                }

                if ($imageUrls !== []) {
                    $imageResult = $this->vehicleImageUploadService->attachImagesFromRemoteUrls(
                        $vehicle->fresh(),
                        $imageUrls
                    );
                    $imageWarnings = $imageResult['warnings'];
                }

                return $vehicle;
            });
        } catch (\Throwable $e) {
            Log::error('Vehicle import row save failed', [
                'dealer_id' => $dealerId,
                'row' => $excelRow,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            $errors[] = [
                'field' => 'row',
                'value' => (string) $excelRow,
                'message' => $this->rowSaveErrorMessage($e),
            ];

            return array_merge($base, [
                'status' => 'failed',
                'errors' => $errors,
                'vehicle_id' => null,
            ]);
        }

        if ((int) ($payload['list_status_id'] ?? VehicleListStatus::PUBLISHED) === VehicleListStatus::PUBLISHED) {
            $context->incrementPublishedCount();
        }

        if ($imageWarnings !== []) {
            $warnings = array_merge($warnings, $imageWarnings);
        }

        return array_merge($base, [
            'status' => $warnings === [] ? 'created' : 'created_with_warnings',
            'vehicle_id' => $vehicle->id,
            'registration' => $vehicle->registration,
            'warnings' => $warnings,
        ]);
    }

    /**
     * @return list<string>
     */
    private function parseImageUrls(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        $parts = preg_split('/[;|,]+/', (string) $raw) ?: [];

        return array_values(array_filter(array_map('trim', $parts)));
    }

    /**
     * Keep all eligible URLs unless the dealer's plan caps images per vehicle.
     *
     * @param  list<string>  $imageUrls
     * @param  list<array{field: string, value: string, message: string}>  $warnings
     * @return list<string>
     */
    private function limitImageUrlsToPlan(array $imageUrls, ?Dealer $dealer, array &$warnings): array
    {
        if ($dealer === null || $imageUrls === []) {
            return $imageUrls;
        }

        $planImageLimit = (int) $this->subscriptionFeatureService->getFeatureLimit($dealer, 'max_vehicle_images', 0);
        if ($planImageLimit <= 0 || count($imageUrls) <= $planImageLimit) {
            return $imageUrls;
        }

        $warnings[] = [
            'field' => 'image_urls',
            'value' => (string) count($imageUrls),
            'message' => __('messages.api.vehicle_import_images_truncated', [
                'max' => $planImageLimit,
            ]),
        ];

        return array_slice($imageUrls, 0, $planImageLimit);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{field: string, value: string, message: string}|null
     */
    private function checkBlockingInvoice(?Dealer $dealer, array $payload): ?array
    {
        if ($dealer === null) {
            return null;
        }

        $listStatusId = (int) ($payload['list_status_id'] ?? VehicleListStatus::PUBLISHED);
        if ($listStatusId !== VehicleListStatus::PUBLISHED) {
            return null;
        }

        if (! $this->dealerInvoiceService->dealerHasBlockingInvoice($dealer)) {
            return null;
        }

        return [
            'field' => 'list_status',
            'value' => 'published',
            'message' => __('messages.api.dealer_overdue_invoice_block'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<array{field: string, value: string, message: string}>  $warnings
     * @return array{field: string, value: string, message: string}|null
     */
    private function checkSubscriptionLimits(
        array &$payload,
        ?Dealer $dealer,
        VehicleImportBatchContext $context,
        array &$warnings,
    ): ?array {
        if ($dealer === null) {
            return null;
        }

        $listStatusId = (int) ($payload['list_status_id'] ?? VehicleListStatus::PUBLISHED);
        if ($listStatusId === VehicleListStatus::PUBLISHED) {
            if (! $this->subscriptionFeatureService->checkFeatureLimit(
                $dealer,
                'max_listings',
                $context->publishedCount,
            )) {
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
            $segments = array_values(array_filter(array_map('trim', explode(',', (string) $payload['lookup_equipments']))));
            if (count($segments) > $equipmentLimit) {
                // Bilbasen exports often exceed plan caps — keep the first N rather than failing the row.
                $payload['lookup_equipments'] = implode(', ', array_slice($segments, 0, max(0, (int) $equipmentLimit)));
                $warnings[] = [
                    'field' => 'equipment',
                    'value' => (string) count($segments),
                    'message' => __('messages.api.max_equipment_per_vehicle_exceeded', ['limit' => $equipmentLimit])
                        .' Equipment list was truncated to the plan limit.',
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

    private function rowSaveErrorMessage(\Throwable $e): string
    {
        if ($e instanceof QueryException) {
            return __('messages.api.vehicle_import_row_save_failed');
        }

        return __('messages.api.vehicle_import_row_save_failed');
    }
}
