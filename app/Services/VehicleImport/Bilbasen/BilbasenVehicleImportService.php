<?php

namespace App\Services\VehicleImport\Bilbasen;

use App\Constants\VehicleListStatus;
use App\Exceptions\NummerpladeApiException;
use App\Models\Dealer;
use App\Services\DealerInvoiceService;
use App\Services\DealerListingQuotaService;
use App\Services\DealerVehicleAddressService;
use App\Services\DmrFactVehicleLookupService;
use App\Services\ListingBillingService;
use App\Services\ListingExpirationService;
use App\Services\SubscriptionFeatureService;
use App\Services\VehicleImageUploadService;
use App\Services\VehicleImport\VehicleImportBatchContext;
use App\Services\VehicleImport\VehicleImportLookupCache;
use App\Services\VehicleImport\VehicleImportRowResolver;
use App\Services\VehicleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BilbasenVehicleImportService
{
    public function __construct(
        private BilbasenListingFetcher $fetcher,
        private BilbasenListingParser $parser,
        private DmrFactVehicleLookupService $dmrFactVehicleLookupService,
        private VehicleService $vehicleService,
        private VehicleImageUploadService $vehicleImageUploadService,
        private SubscriptionFeatureService $subscriptionFeatureService,
        private ListingBillingService $listingBillingService,
        private ListingExpirationService $listingExpirationService,
        private DealerListingQuotaService $listingQuotaService,
        private DealerInvoiceService $dealerInvoiceService,
        private DealerVehicleAddressService $dealerVehicleAddressService,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function preview(string $url, int $dealerId): array
    {
        $scraped = $this->fetchAndParse($url);

        if ($scraped['blocked']) {
            throw new \RuntimeException(__('messages.api.bilbasen_import_blocked'));
        }

        return $this->buildPreviewPayload($scraped, $dealerId);
    }

    /**
     * @return array{vehicle_id: int, warnings: list<array{field: string, value: string, message: string}>}
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function publish(string $url, int $dealerId, int $userId, Dealer $dealer, int $salesTypeId): array
    {
        $scraped = $this->fetchAndParse($url);

        if ($scraped['blocked']) {
            throw new \RuntimeException(__('messages.api.bilbasen_import_blocked'));
        }

        $preview = $this->buildPreviewPayload($scraped, $dealerId);
        $rawImageUrls = $preview['image_urls'] ?? [];
        if (! is_array($rawImageUrls)) {
            $rawImageUrls = [];
        }

        $planImageLimit = (int) $this->subscriptionFeatureService->getFeatureLimit($dealer, 'max_vehicle_images', 0);
        $maxImages = $planImageLimit > 0 ? $planImageLimit : null;

        $warnings = [];
        if ($maxImages !== null && count($rawImageUrls) > $maxImages) {
            $warnings[] = [
                'field' => 'image_urls',
                'value' => (string) count($rawImageUrls),
                'message' => __('messages.api.vehicle_import_images_truncated', [
                    'max' => $maxImages,
                ]),
            ];
            $imageUrls = array_slice($rawImageUrls, 0, $maxImages);
        } else {
            $imageUrls = $rawImageUrls;
        }

        $hasMappedIdentity = ! empty($preview['mapped']['brand_id']) && ! empty($preview['mapped']['model_id']);

        $row = [
            'registration' => $preview['registration'] ?? '',
            'vin' => $preview['vin'] ?? '',
            'price' => $preview['price'] ?? '',
            'km_driven' => $preview['mileage'] ?? '',
            'description' => $preview['description'] ?? '',
            'title' => $preview['title'] ?? '',
        ];

        if (! $hasMappedIdentity) {
            $row['brand'] = $preview['brand'] ?? ($preview['mapped']['brand_name'] ?? '');
            $row['model'] = $preview['model'] ?? ($preview['mapped']['model_name'] ?? '');
            $row['variant'] = $preview['variant'] ?? '';
            $row['fuel_type'] = $preview['fuel_type'] ?? '';
        }

        $lookupCache = new VehicleImportLookupCache;
        $resolver = new VehicleImportRowResolver($lookupCache, $this->dmrFactVehicleLookupService);
        $context = new VehicleImportBatchContext(
            $this->listingQuotaService->countPublishedListings($dealer)
        );

        // Preview already attempted DMR enrichment; resolve scalars / duplicates only.
        $resolved = $resolver->resolve($row, $dealerId, false, $context);
        $payload = $resolved['payload'];
        $warnings = array_merge($warnings, $resolved['warnings']);
        $errors = $resolved['errors'];

        foreach (['brand_id', 'model_id', 'variant_id', 'fuel_type_id', 'dmr_fact_vehicle_id'] as $key) {
            if (! empty($preview['mapped'][$key])) {
                $payload[$key] = $preview['mapped'][$key];
            }
        }

        if (! empty($preview['dmr']) && is_array($preview['dmr'])) {
            foreach ([
                'km_per_liter', 'co2_emission', 'electrical_consumption', 'engine_power_kw', 'engine_power_hp',
                'engine_size_cc', 'engine_displacement_litres', 'first_registration_date', 'first_registration_year',
                'door_count', 'gear_count', 'max_speed', 'model_year', 'seats_min', 'seats_max', 'maximum_weight_kg',
                'lookup_equipments', 'lookup_specifications',
            ] as $dmrField) {
                if (! array_key_exists($dmrField, $preview['dmr'])) {
                    continue;
                }
                $value = $preview['dmr'][$dmrField];
                if ($value === null || $value === '') {
                    continue;
                }
                if (! array_key_exists($dmrField, $payload) || $payload[$dmrField] === null || $payload[$dmrField] === '') {
                    $payload[$dmrField] = $value;
                }
            }
            if (! empty($preview['dmr']['equipments']) && empty($payload['lookup_equipments'])) {
                $payload['lookup_equipments'] = $preview['dmr']['equipments'];
            }
            if (! empty($preview['dmr']['specifications']) && empty($payload['lookup_specifications'])) {
                $payload['lookup_specifications'] = is_string($preview['dmr']['specifications'])
                    ? $preview['dmr']['specifications']
                    : json_encode($preview['dmr']['specifications']);
            }
        }

        $payload['sales_type_id'] = $salesTypeId;
        $payload['list_status_id'] = VehicleListStatus::PUBLISHED;
        $payload['published_at'] = now()->toDateTimeString();

        if (($preview['price'] ?? null) !== null) {
            $payload['price'] = $preview['price'];
        }
        if (($preview['mileage'] ?? null) !== null) {
            $payload['km_driven'] = $preview['mileage'];
        }
        if (! empty($preview['description'])) {
            $payload['description'] = $preview['description'];
        }
        if (! empty($preview['title'])) {
            $payload['title'] = $preview['title'];
        }
        if (! empty($preview['registration'])) {
            $payload['registration'] = $this->dmrFactVehicleLookupService->normalizeRegistration($preview['registration']);
        }
        if (! empty($preview['vin'])) {
            $payload['vin'] = $preview['vin'];
        }

        $equipmentLimit = (int) $this->subscriptionFeatureService->getFeatureLimit($dealer, 'max_equipment_per_vehicle', 999);
        if ($equipmentLimit > 0) {
            $equipmentCount = 0;
            if (isset($payload['equipment_ids']) && is_array($payload['equipment_ids'])) {
                $equipmentCount += count($payload['equipment_ids']);
            }
            $lookupCsv = $payload['lookup_equipments'] ?? null;
            if (is_string($lookupCsv) && trim($lookupCsv) !== '') {
                $equipmentCount += count(array_filter(array_map('trim', explode(',', $lookupCsv))));
            } elseif (is_array($lookupCsv)) {
                $equipmentCount += count($lookupCsv);
            }
            if ($equipmentCount > $equipmentLimit) {
                throw new \RuntimeException(__('messages.api.max_equipment_per_vehicle_exceeded', [
                    'limit' => $equipmentLimit,
                ]));
            }
        }

        $errors = array_values(array_filter($errors, static function (array $error) use ($payload) {
            if (in_array($error['field'], ['sales_type', 'brand', 'model', 'fuel_type', 'price'], true)) {
                return false;
            }

            return true;
        }));

        if (empty($payload['brand_id'])) {
            $errors[] = [
                'field' => 'brand',
                'value' => (string) ($preview['brand'] ?? ''),
                'message' => __('messages.api.vehicle_import_brand_required'),
            ];
        }
        if (empty($payload['model_id'])) {
            $errors[] = [
                'field' => 'model',
                'value' => (string) ($preview['model'] ?? ''),
                'message' => __('messages.api.vehicle_import_model_required'),
            ];
        }
        if (! array_key_exists('price', $payload) || $payload['price'] === '' || $payload['price'] === null) {
            $errors[] = [
                'field' => 'price',
                'value' => '',
                'message' => __('messages.api.vehicle_import_price_required'),
            ];
        }
        if (empty($payload['dmr_fact_vehicle_id']) && empty($payload['fuel_type_id'])) {
            $errors[] = [
                'field' => 'fuel_type',
                'value' => (string) ($preview['fuel_type'] ?? ''),
                'message' => __('messages.api.vehicle_import_fuel_type_required'),
            ];
        }

        if ($errors !== []) {
            throw new \InvalidArgumentException($this->formatErrors($errors));
        }

        if ($this->dealerInvoiceService->dealerHasBlockingInvoice($dealer)) {
            throw new \RuntimeException(__('messages.api.dealer_overdue_invoice_block'));
        }

        if (! $this->listingQuotaService->canPublishAnotherListing($dealer, $this->subscriptionFeatureService)) {
            $limit = $this->subscriptionFeatureService->getFeatureLimit($dealer, 'max_listings', 0);
            throw new \RuntimeException(__('messages.api.max_listings_reached', ['limit' => $limit]));
        }

        $payload['dealer_id'] = $dealerId;
        $payload['user_id'] = $userId;
        $payload = $this->dealerVehicleAddressService->applyToPayload($payload, $dealer);

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
            Log::error('Bilbasen vehicle import publish failed', [
                'dealer_id' => $dealerId,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException(__('messages.api.bilbasen_import_publish_failed'), 0, $e);
        }

        // Download images after commit so long remote fetches do not hold the DB transaction open.
        $imageWarnings = [];
        if ($imageUrls !== []) {
            $imageResult = $this->vehicleImageUploadService->attachImagesFromRemoteUrls(
                $vehicle->fresh(),
                $imageUrls
            );
            $imageWarnings = $imageResult['warnings'];
        }

        $warnings = array_merge($warnings, $imageWarnings);
        foreach ($preview['warnings'] ?? [] as $message) {
            if (is_string($message) && $message !== '') {
                $warnings[] = [
                    'field' => 'listing',
                    'value' => $preview['external_listing_id'] ?? '',
                    'message' => $message,
                ];
            }
        }

        return [
            'vehicle_id' => $vehicle->id,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchAndParse(string $url): array
    {
        $fetched = $this->fetcher->fetch($url);

        return $this->parser->parse($fetched['html'], $fetched['url'], $fetched['listing_id']);
    }

    /**
     * @param  array<string, mixed>  $scraped
     * @return array<string, mixed>
     */
    private function buildPreviewPayload(array $scraped, int $dealerId): array
    {
        $warnings = [];
        foreach ($scraped['warnings'] ?? [] as $warning) {
            if (is_string($warning) && $warning !== '') {
                $warnings[] = $warning;
            }
        }

        $dmr = null;
        $mapped = [
            'brand_id' => null,
            'model_id' => null,
            'variant_id' => null,
            'fuel_type_id' => null,
            'dmr_fact_vehicle_id' => null,
            'brand_name' => $scraped['brand'] ?? null,
            'model_name' => $scraped['model'] ?? null,
        ];

        $registration = $scraped['registration'] ?? null;
        $vin = $scraped['vin'] ?? null;

        if ($registration || $vin) {
            try {
                $dmr = $registration
                    ? $this->dmrFactVehicleLookupService->lookupByRegistration($registration)
                    : $this->dmrFactVehicleLookupService->lookupByVin((string) $vin);

                $mapped['dmr_fact_vehicle_id'] = $dmr['dmr_fact_vehicle_id'] ?? null;
                $mapped['brand_id'] = $dmr['brand']['id'] ?? null;
                $mapped['model_id'] = $dmr['model']['id'] ?? null;
                $mapped['variant_id'] = $dmr['variant']['id'] ?? null;
                $mapped['fuel_type_id'] = $dmr['fuel_type']['id'] ?? null;
                $mapped['brand_name'] = $dmr['brand']['name'] ?? $mapped['brand_name'];
                $mapped['model_name'] = $dmr['model']['name'] ?? $mapped['model_name'];

                if (! empty($dmr['registration'])) {
                    $registration = $dmr['registration'];
                }
                if (! empty($dmr['vin'])) {
                    $vin = $dmr['vin'];
                }
            } catch (NummerpladeApiException $e) {
                $warnings[] = $e->getMessage();
            }
        }

        $lookupCache = null;
        if (empty($mapped['brand_id']) || empty($mapped['model_id'])) {
            $lookupCache = new VehicleImportLookupCache;
            if (empty($mapped['brand_id']) && ! empty($scraped['brand'])) {
                $mapped['brand_id'] = $lookupCache->resolveBrand((string) $scraped['brand']);
            }
            if (! empty($mapped['brand_id']) && empty($mapped['model_id']) && ! empty($scraped['model'])) {
                $mapped['model_id'] = $lookupCache->resolveModel((string) $scraped['model'], (int) $mapped['brand_id']);
            }
        }

        // Resolve variant/fuel from scraped names even when DMR already mapped brand+model.
        if (
            (! empty($mapped['model_id']) && empty($mapped['variant_id']) && ! empty($scraped['variant']))
            || (empty($mapped['fuel_type_id']) && ! empty($scraped['fuel_type']))
        ) {
            $lookupCache ??= new VehicleImportLookupCache;
            if (! empty($mapped['model_id']) && empty($mapped['variant_id']) && ! empty($scraped['variant'])) {
                $mapped['variant_id'] = $lookupCache->resolveVariant((string) $scraped['variant'], (int) $mapped['model_id']);
            }
            if (empty($mapped['fuel_type_id']) && ! empty($scraped['fuel_type'])) {
                $mapped['fuel_type_id'] = $lookupCache->resolveFlat('fuel_type_id', (string) $scraped['fuel_type']);
            }
        }

        // Duplicate registration soft-warning for preview (hard error on publish).
        if ($registration) {
            $normalized = $this->dmrFactVehicleLookupService->normalizeRegistration($registration);
            $exists = \App\Models\Vehicle::query()
                ->where('dealer_id', $dealerId)
                ->where('registration', $normalized)
                ->exists();
            if ($exists) {
                $warnings[] = __('messages.api.vehicle_import_duplicate_registration');
            }
            $registration = $normalized;
        }

        $imageUrls = array_values(array_filter(
            is_array($scraped['image_urls'] ?? null) ? $scraped['image_urls'] : [],
            static fn ($url) => is_string($url) && trim($url) !== ''
        ));

        return [
            'source_url' => $scraped['source_url'],
            'external_listing_id' => $scraped['external_listing_id'],
            'registration' => $registration,
            'vin' => $vin,
            'price' => $scraped['price'] ?? null,
            'mileage' => $scraped['mileage'] ?? null,
            'description' => $scraped['description'] ?? null,
            'title' => $scraped['title'] ?? null,
            'brand' => $scraped['brand'] ?? null,
            'model' => $scraped['model'] ?? null,
            'variant' => $scraped['variant'] ?? null,
            'fuel_type' => $scraped['fuel_type'] ?? null,
            'year' => $scraped['year'] ?? null,
            'image_urls' => $imageUrls,
            'dmr' => $dmr,
            'mapped' => $mapped,
            'warnings' => $warnings,
            'blocked' => false,
        ];
    }

    /**
     * @param  list<array{field: string, value: string, message: string}>  $errors
     */
    private function formatErrors(array $errors): string
    {
        $messages = array_map(static fn (array $e) => $e['message'], $errors);

        return implode(' ', $messages);
    }
}
