<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Models\FeaturedListing;
use App\Services\VehicleService;
use App\Services\VehicleDetailPresentationService;
use App\Services\OwnershipTaxService;
use App\Services\FileService;
use App\Services\AuditLogService;
use App\Services\SellYourCarSubmissionService;
use App\Services\DealerContextService;
use App\Services\SubscriptionFeatureService;
use App\Constants\VehicleListStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Constants\ApiStatusCode;
use Illuminate\Pagination\LengthAwarePaginator;

class VehicleController extends Controller
{
    protected FileService $fileService;

    public function __construct(
        private VehicleService $vehicleService,
        FileService $fileService,
        private AuditLogService $auditLogService,
        private SubscriptionFeatureService $subscriptionFeatureService,
        private VehicleDetailPresentationService $vehicleDetailPresentationService,
        private SellYourCarSubmissionService $sellYourCarSubmissionService,
        private OwnershipTaxService $ownershipTaxService,
    ) {
        $this->fileService = $fileService;
    }

    /**
     * Get featured vehicles
     * Returns only the fields needed for the featured vehicles display
     */
    public function getFeaturedVehicles(Request $request): JsonResponse
    {
        // Fetch featured vehicles from FeaturedListing model
        $featuredListings = FeaturedListing::with([
            'vehicle.images',
            'vehicle.dmrFactVehicle.variant',
        ])
            ->orderBy('sort_order')
            ->get();

        // Format vehicles for JSON response (only fields used in home.blade.php)
        $formattedVehicles = $featuredListings->map(function ($featuredListing) {
            $vehicle = $featuredListing->vehicle;
            if (!$vehicle) {
                return null;
            }

            // Get first image
            $firstImage = $vehicle->images->first();
            $imageUrl = $firstImage?->thumbnail_url ?? $firstImage?->image_url ?? '/placeholder-vehicle.jpg';
            
            // Build title
            $title = $vehicle->title ?? trim(($vehicle->brand_name ?? '') . ' ' . ($vehicle->model_name ?? ''));

            return [
                'id' => $vehicle->id,
                'title' => $title,
                'variant_name' => $vehicle->variant_name,
                'price' => $vehicle->price ?? 0,
                'image' => $imageUrl,
                'km_driven' => $vehicle->km_driven ?? 0,
                'engine_power_hp' => $vehicle->engine_power_hp,
                'first_registration_date' => $vehicle->first_registration_date?->format('Y-m-d'),
                'fuel_type_name' => $vehicle->fuel_type_name,
                'gear_type_name' => $vehicle->gear_type_name,
            ];
        })
        ->filter() // Remove null entries
        ->values(); // Re-index array

        return $this->success([
            'vehicles' => $formattedVehicles,
        ]);
    }

    /** @var array<int, string> Flat allowlist for public vehicle search inputs */
    private const FILTER_KEYS = [
        'search', 'sort', 'page', 'limit',
        'viewer_latitude', 'viewer_longitude',
        'brand_id', 'model_id',
        'listing_type_id', 'list_status_id',
        'category_id', 'sales_type_id', 'price_type_id', 'condition_id',
        'body_type_id', 'fuel_type_id', 'gear_type_id',
        'dealer_id', 'vehicle_use_id', 'measurement_norm_id',
        'colour_id', 'emission_norm_id',
        'model_year_from', 'model_year_to',
        'first_registration_year_from', 'first_registration_year_to',
        'price_from', 'price_to',
        'km_driven_from', 'km_driven_to',
        'max_speed_from', 'max_speed_to',
        'engine_power_kw_from', 'engine_power_kw_to',
        'engine_power_hp_from', 'engine_power_hp_to',
        'calculated_ownership_tax_from', 'calculated_ownership_tax_to',
        'battery_capacity_from', 'battery_capacity_to',
        'electrical_consumption_from', 'electrical_consumption_to',
        'km_per_liter_from', 'km_per_liter_to',
        'range_km_from', 'range_km_to',
        'maximum_weight_kg_from', 'maximum_weight_kg_to',
        'door_count', 'seats_min', 'seats_max', 'axle_count', 'towing_weight',
        'engine_displacement_litres_from', 'engine_displacement_litres_to',
        'charging_type', 'ncap_test',
        'is_import', 'is_factory_new',
        'equipment_ids',
    ];

    /**
     * Normalize public search input onto the filter names understood by VehicleService.
     * Backward-compatible aliases are still accepted for GET callers.
     */
    private function normalizeVehicleSearchInput(array $input): array
    {
        if (isset($input['km_driven_from'])) {
            $input['mileage_from'] = $input['km_driven_from'];
        }
        if (isset($input['km_driven_to'])) {
            $input['mileage_to'] = $input['km_driven_to'];
        }
        if (isset($input['calculated_ownership_tax_from']) && ! isset($input['ownership_tax_from'])) {
            $input['ownership_tax_from'] = $input['calculated_ownership_tax_from'];
        }
        if (isset($input['calculated_ownership_tax_to']) && ! isset($input['ownership_tax_to'])) {
            $input['ownership_tax_to'] = $input['calculated_ownership_tax_to'];
        }
        if (isset($input['engine_power_hp_from']) && ! isset($input['engine_power_from'])) {
            $input['engine_power_from'] = $input['engine_power_hp_from'];
        }
        if (isset($input['engine_power_hp_to']) && ! isset($input['engine_power_to'])) {
            $input['engine_power_to'] = $input['engine_power_hp_to'];
        }
        if (isset($input['engine_displacement_litres_from']) && ! isset($input['engine_displacement_from'])) {
            $input['engine_displacement_from'] = $input['engine_displacement_litres_from'];
        }
        if (isset($input['engine_displacement_litres_to']) && ! isset($input['engine_displacement_to'])) {
            $input['engine_displacement_to'] = $input['engine_displacement_litres_to'];
        }
        // Ensure condition_id is integer so filter matches vehicles.condition_id correctly
        if (isset($input['condition_id']) && $input['condition_id'] !== '' && $input['condition_id'] !== null) {
            $input['condition_id'] = (int) $input['condition_id'];
        }
        // Accept legacy emission norm aliases and normalize them to the current emission_norm_id column.
        if (!empty($input['euronom_id'])) {
            $input['euronom_id'] = (int) $input['euronom_id'];
        } 
        if (isset($input['year_from']) && ! isset($input['model_year_from'])) {
            $input['model_year_from'] = $input['year_from'];
        }
        if (isset($input['year_to']) && ! isset($input['model_year_to'])) {
            $input['model_year_to'] = $input['year_to'];
        }
        if (! empty($input['euronom_id']) && empty($input['emission_norm_id'])) {
            $input['emission_norm_id'] = (int) $input['euronom_id'];
        }
        if (array_key_exists('sort', $input)) {
            $raw = $input['sort'];
            $input['sort'] = VehicleService::normalizePublicListingSort(
                $raw === null || $raw === '' ? null : (string) $raw
            );
        }

        return $input;
    }

    /**
     * Build JSON response for vehicle list from normalized input (shared by index and searchVehicles).
     */
    private function getVehiclesListResponse(array $input, string $path = '', array $query = []): JsonResponse
    {
        $limit = (int) ($input['limit'] ?? 15);
        $page = (int) ($input['page'] ?? 1);

        $vehicles = $this->vehicleService->getPublicVehiclesWithAdvancedFilters([], $input, $limit, $page);

        $formattedVehicles = collect($vehicles->items())->map(function ($vehicle) {
            $firstImage = $vehicle->images->first();

            // Determine seller type (dealer or private)
            $isDealer = $vehicle->dealer && !str_starts_with($vehicle->dealer->cvr ?? '', 'INDIVIDUAL-');
            $sellerType = $isDealer ? 'Dealer' : 'Private';

            return [
                'id' => $vehicle->id,
                'slug' => $vehicle->slug,
                'dealer_id' => $vehicle->dealer_id,
                'title' => $vehicle->title,
                'variant_name' => $vehicle->variant_name,
                'price' => $vehicle->price,
                'thumbnail_url' => $firstImage?->thumbnail_url ?? '/placeholder-vehicle.jpg',
                'km_driven' => $vehicle->km_driven,
                'engine_power_hp' => $vehicle->engine_power_hp,
                'first_registration_date' => $vehicle->first_registration_date?->format('Y-m-d'),
                'gear_type_name' => $vehicle->gear_type_name,
                'fuel_type_name' => $vehicle->fuel_type_name,
                'model_year_name' => $vehicle->model_year_name,
                'brand_name' => $vehicle->brand_name,
                'model_name' => $vehicle->model_name,
                'seller_type' => $sellerType,
                'is_dealer' => (bool) $isDealer,
                'is_private' => ! $isDealer,
                'seller_address' => $vehicle->seller_address,
                'seller_postcode' => $vehicle->seller_postcode,
                'user_id' => $vehicle->user_id,
                'sales_type_name' => $vehicle->salesType?->name,
            ];
        });

        $formattedPaginator = new LengthAwarePaginator(
            $formattedVehicles,
            $vehicles->total(),
            $vehicles->perPage(),
            $vehicles->currentPage(),
            ['path' => $path ?: url('/api/v1/vehicles'), 'query' => $query]
        );

        // When no results, include fallback list (all vehicles, first page) for better UX
        if ($vehicles->total() === 0) {
            $fallbackVehicles = $this->vehicleService->getPublicVehicles([], $limit, 1);
            $fallbackFormatted = collect($fallbackVehicles->items())->map(function ($vehicle) {
                $firstImage = $vehicle->images->first();
                $isDealer = $vehicle->dealer && !str_starts_with($vehicle->dealer->cvr ?? '', 'INDIVIDUAL-');
                $sellerType = $isDealer ? 'Dealer' : 'Private';
                return [
                    'id' => $vehicle->id,
                    'slug' => $vehicle->slug,
                    'dealer_id' => $vehicle->dealer_id,
                    'title' => $vehicle->title,
                    'variant_name' => $vehicle->variant_name,
                    'price' => $vehicle->price,
                    'thumbnail_url' => $firstImage?->thumbnail_url ?? '/placeholder-vehicle.jpg',
                    'km_driven' => $vehicle->km_driven,
                    'engine_power_hp' => $vehicle->engine_power_hp,
                    'first_registration_date' => $vehicle->first_registration_date?->format('Y-m-d'),
                    'gear_type_name' => $vehicle->gear_type_name,
                    'fuel_type_name' => $vehicle->fuel_type_name,
                    'model_year_name' => $vehicle->model_year_name,
                    'brand_name' => $vehicle->brand_name,
                    'model_name' => $vehicle->model_name,
                    'seller_type' => $sellerType,
                    'is_dealer' => (bool) $isDealer,
                    'is_private' => ! $isDealer,
                    'seller_address' => $vehicle->seller_address,
                    'seller_postcode' => $vehicle->seller_postcode,
                    'user_id' => $vehicle->user_id,
                    'sales_type_name' => $vehicle->salesType?->name,
                ];
            });
            $paginationData = [
                'docs' => $formattedPaginator->items(),
                'limit' => $formattedPaginator->perPage(),
                'page' => $formattedPaginator->currentPage(),
                'hasPrevPage' => false,
                'hasNextPage' => false,
                'prevPage' => null,
                'nextPage' => null,
                'totalDocs' => 0,
                'totalPages' => 0,
                'no_results' => true,
                'fallback_docs' => $fallbackFormatted->values()->all(),
                'fallback_totalDocs' => $fallbackVehicles->total(),
                'fallback_page' => 1,
                'fallback_totalPages' => $fallbackVehicles->lastPage(),
            ];
            return response()->json([
                'success' => true,
                'failed' => false,
                'message' => __('messages.api.data_retrieved_successfully'),
                'data' => $paginationData,
                'errors' => [],
            ], 200);
        }

        return $this->paginated($formattedPaginator);
    }

    /**
     * Get vehicles list (public) - GET with query params
     */
    public function index(Request $request): JsonResponse
    {
        $input = $this->normalizeVehicleSearchInput($request->query());
        return $this->getVehiclesListResponse($input, $request->url(), $request->query());
    }

    /**
     * Search vehicles - POST with body (same filter set as index, same response shape)
     */
    public function searchVehicles(Request $request): JsonResponse
    {
        $input = array_intersect_key($request->all(), array_flip(self::FILTER_KEYS));
        $input = $this->normalizeVehicleSearchInput($input);
        return $this->getVehiclesListResponse($input);
    }

    /**
     * Count vehicles matching public filters (efficient COUNT query only).
     */
    public function count(Request $request): JsonResponse
    {
        $input = $this->normalizeVehicleSearchInput($request->query());
        unset($input['page'], $input['limit'], $input['sort']);

        $count = $this->vehicleService->countPublicVehiclesWithFilters($input);

        return $this->success(['count' => $count]);
    }

    /**
     * Get dealer vehicles list
     */
    public function dealerIndex(Request $request): JsonResponse
    {
        $dealer = $request->user()?->dealer;
        if (!$dealer) {
            $emptyPaginator = new LengthAwarePaginator([], 0, $request->input('limit', 15), $request->input('page', 1));
            return $this->paginated($emptyPaginator);
        }

        $filters = $request->only([
            'search',
            'list_status_id',
            'sort',
        ]);

        // Handle status parameter (convert status name to ID)
        if ($request->has('status') && $request->input('status')) {
            $statusId = VehicleListStatus::nameToId($request->input('status'));
            if ($statusId) {
                $filters['list_status_id'] = $statusId;
            }
        }

        $perPage = (int) $request->input('limit', 15);
        $page = (int) $request->input('page', 1);

        // Stat cards always show dealer-wide counts (ignore list_status_id filter).
        $countFilters = $filters;
        unset($countFilters['list_status_id']);
        $countQuery = $this->vehicleService->buildDealerVehiclesQuery($dealer->id, $countFilters);
        $listStatusCounts = $this->vehicleService->aggregateListStatusCounts(clone $countQuery);

        $paginator = $this->vehicleService->buildDealerVehiclesQuery($dealer->id, $filters)
            ->paginate($perPage, ['*'], 'page', $page);

        $paginationData = [
            'docs' => $paginator->items(),
            'limit' => $paginator->perPage(),
            'page' => $paginator->currentPage(),
            'hasPrevPage' => $paginator->currentPage() > 1,
            'hasNextPage' => $paginator->hasMorePages(),
            'prevPage' => $paginator->currentPage() > 1 ? $paginator->currentPage() - 1 : null,
            'nextPage' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
            'totalDocs' => $paginator->total(),
            'totalPages' => $paginator->lastPage(),
            'list_status_counts' => $listStatusCounts,
        ];

        return $this->success(
            $paginationData,
            ApiStatusCode::OK,
            __('messages.api.data_retrieved_successfully')
        );
    }

    /**
     * Get vehicle details (DMR-linked listing).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $vehicle = Vehicle::with(array_merge($this->vehicleDetailPresentationService->detailEagerLoads(), [
            'dealer' => function ($q) {
                $q->with('owner');
            },
            'user',
            'images' => function ($q) {
                $q->orderBy('sort_order');
            },
        ]))->findOrFail($id);

        $dealerViewer = $request->user()?->dealer;
        if ($dealerViewer) {
            $vehicle->loadMissing(['dmrFactVehicle.drivmiddelLines']);
        }

        $isDealer = $vehicle->dealer && ! str_starts_with($vehicle->dealer->cvr ?? '', 'INDIVIDUAL-');
        $sellerType = $isDealer ? 'Dealer' : 'Private';

        $payload = $this->vehicleDetailPresentationService->buildDetailPayload($vehicle);

        $response = array_merge($payload, [
            'dealer_id' => $vehicle->dealer_id,
            'user_id' => $vehicle->user_id,
            'published_at' => $vehicle->published_at?->format('Y-m-d H:i:s'),
            'created_at' => $vehicle->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $vehicle->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $vehicle->deleted_at?->format('Y-m-d H:i:s'),
            'first_registration_date' => $vehicle->first_registration_date?->format('Y-m-d'),
            'last_inspection_date' => $vehicle->last_inspection_date?->format('Y-m-d'),
            'images' => $vehicle->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image_url' => $image->image_url,
                    'thumbnail_url' => $image->thumbnail_url,
                    'sort_order' => $image->sort_order,
                ];
            }),
            'dealer' => $vehicle->dealer ? [
                'id' => $vehicle->dealer->id,
                'slug' => $vehicle->dealer->slug,
                'cvr' => $vehicle->dealer->cvr,
                'address' => $vehicle->dealer->address,
                'city' => $vehicle->dealer->city,
                'postcode' => $vehicle->dealer->postcode,
                'logo_url' => $vehicle->dealer->logo_url,
                'owner' => $vehicle->dealer->owner ? [
                    'id' => $vehicle->dealer->owner->id,
                    'name' => $vehicle->dealer->owner->name,
                    'email' => $vehicle->dealer->owner->email,
                    'phone' => $vehicle->dealer->owner->phone,
                    'whatsapp_number' => $vehicle->dealer->owner->whatsapp_number,
                ] : null,
            ] : null,
            'user' => $vehicle->user ? [
                'id' => $vehicle->user->id,
                'name' => $vehicle->user->name,
                'email' => $vehicle->user->email,
                'phone' => $vehicle->user->phone,
            ] : null,
            'seller_type' => $sellerType,
        ]);

        if ($dealerViewer) {
            $taxFromRules = $this->ownershipTaxService->calculateForVehicle($vehicle);
            $response['ownership_tax'] = $taxFromRules;
            $response['calculated_ownership_tax'] = $taxFromRules;
        }

        return $this->success($response);
    }
    /**
     * Get vehicles list (legacy method for backward compatibility)
     */
    public function getVehicles(Request $request): JsonResponse
    {
        return $this->index($request);
    }

    /**
     * Get vehicles overview statistics
     */
    public function getVehiclesOverview(): JsonResponse
    {
        $totalVehicles = Vehicle::count();
        $publishedVehicles = Vehicle::where('list_status_id', VehicleListStatus::PUBLISHED)->count();
        $draftVehicles = Vehicle::where('list_status_id', VehicleListStatus::DRAFT)->count();
        $soldVehicles = Vehicle::where('list_status_id', VehicleListStatus::SOLD)->count();

        $totalInventoryValue = Vehicle::where('list_status_id', VehicleListStatus::PUBLISHED)->sum('price');
        $averageVehicleValue = $publishedVehicles > 0 ? $totalInventoryValue / $publishedVehicles : 0;

        $averageDaysListed = Vehicle::where('list_status_id', VehicleListStatus::PUBLISHED)
            ->whereNotNull('published_at')
            ->selectRaw('AVG(DATEDIFF(NOW(), published_at)) as avg_days')
            ->value('avg_days') ?? 0;

        $listingsOver90Days = Vehicle::where('list_status_id', VehicleListStatus::PUBLISHED)
            ->whereNotNull('published_at')
            ->whereRaw('DATEDIFF(NOW(), published_at) > 90')
            ->count();

        $newArrivals7Days = Vehicle::where('created_at', '>=', now()->subDays(7))->count();
        $recentlyUpdated24h = Vehicle::where('updated_at', '>=', now()->subDay())->count();

        return $this->success([
            'totalVehicles' => $totalVehicles,
            'publishedVehicles' => $publishedVehicles,
            'draftVehicles' => $draftVehicles,
            'soldVehicles' => $soldVehicles,
            'totalInventoryValue' => $totalInventoryValue,
            'averageVehicleValue' => round($averageVehicleValue, 2),
            'averageDaysListed' => round((float) $averageDaysListed, 2),
            'publishedListingsOver90Days' => $listingsOver90Days,
            'newArrivals7Days' => $newArrivals7Days,
            'recentlyUpdated24h' => $recentlyUpdated24h,
        ]);
    }

    /**
     * Get vehicle by serial number
     */
    public function getVehicleBySerial(int $serialNo): JsonResponse
    {
        $vehicle = Vehicle::where('serial_no', $serialNo)
            ->withCount(['purchases', 'sales'])
            ->firstOrFail();

        return $this->success($vehicle);
    }

    /**
     * Create vehicle
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'registration' => 'nullable|string|max:20',
            'vin' => 'nullable|string|max:17',
            'brand_id' => ['required', 'integer', 'exists:dmr_brands,id'],
            'model_id' => ['required', 'integer', 'exists:dmr_models,id'],
            'list_status_id' => 'nullable|integer|exists:vehicle_list_statuses,id',
            'vehicle_list_status_id' => 'nullable|integer|exists:vehicle_list_statuses,id',
            'seller_phone' => 'nullable|string|max:50',
            'annual_tax' => 'nullable|numeric|min:0',
            'price' => ['required', 'numeric', 'min:0'],
            'sales_type_id' => ['required', 'integer', 'exists:sales_types,id'],
        ]);

        $data = $request->all();

        // Set dealer_id from authenticated user
        $dealer = null;
        if ($request->user() && $request->user()->dealer) {
            $dealer = $request->user()->dealer;
            $data['dealer_id'] = $dealer->id;
        }

        // Set user_id (creator)
        $data['user_id'] = $request->user()->id;

        if (! isset($data['list_status_id']) && isset($data['vehicle_list_status_id'])) {
            $data['list_status_id'] = $data['vehicle_list_status_id'];
        }

        // Check max_listings limit if vehicle is being published
        $vehicleListStatusId = $data['list_status_id'] ?? null;
        if ($dealer && ($vehicleListStatusId == VehicleListStatus::PUBLISHED || $vehicleListStatusId == 2)) {
            $publishedCount = Vehicle::where('dealer_id', $dealer->id)
                ->where('list_status_id', VehicleListStatus::PUBLISHED)
                ->count();
            
            if (!$this->subscriptionFeatureService->checkFeatureLimit($dealer, 'max_listings', $publishedCount)) {
                $limit = $this->subscriptionFeatureService->getFeatureLimit($dealer, 'max_listings', 0);
                return $this->error(
                    __('messages.api.max_listings_reached', ['limit' => $limit]),
                    [],
                    403
                );
            }
        }

        // Check max_equipment_per_vehicle limit (checkbox IDs + upper bound from DMR CSV)
        if ($dealer && (isset($data['equipment_ids']) || ! empty($data['lookup_equipments'] ?? null))) {
            $equipmentLimit = $this->subscriptionFeatureService->getFeatureLimit($dealer, 'max_equipment_per_vehicle', 999);
            $equipmentCount = isset($data['equipment_ids']) && is_array($data['equipment_ids'])
                ? count($data['equipment_ids'])
                : 0;
            $lookupCsv = $data['lookup_equipments'] ?? null;
            if (is_string($lookupCsv) && trim($lookupCsv) !== '') {
                $csvSegments = array_filter(array_map('trim', explode(',', $lookupCsv)));
                $equipmentCount += count($csvSegments);
            }
            if ($equipmentCount > $equipmentLimit) {
                return $this->error(
                    __('messages.api.max_equipment_per_vehicle_exceeded', ['limit' => $equipmentLimit]),
                    [],
                    403
                );
            }
        }

        // Check max_vehicle_images limit on create
        if ($dealer && $request->hasFile('images')) {
            $images = $request->file('images');
            $newImageCount = is_array($images) ? count($images) : 1;
            $maxImages = $this->subscriptionFeatureService->getFeatureLimit($dealer, 'max_vehicle_images', 0);
            if ($maxImages > 0 && $newImageCount > $maxImages) {
                return $this->error(
                    __('messages.api.max_vehicle_images_per_vehicle_exceeded', ['limit' => $maxImages]),
                    [],
                    403
                );
            }
        }

        // Handle file uploads
        if ($request->hasFile('images')) {
            $data['images'] = $request->file('images');
        }

        $vehicle = $this->vehicleService->createVehicle($data);

        // Audit log
        try {
            $this->auditLogService->logCreate(
                $request->user(),
                'Vehicle',
                $vehicle->id,
                $vehicle->toArray(),
                $request,
                'Dealer',
                $vehicle->dealer_id,
                "Vehicle created: {$vehicle->title}",
                ['vehicle', 'dealer', 'create']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for vehicle creation', [
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->created($vehicle->load(['dealer', 'images', 'equipment', 'specifications', 'dmrFactVehicle.variant.model.brand']));
    }

    /**
     * Create vehicle draft — {@see Vehicle::$brand_id} and {@see Vehicle::$model_id} are required; other fields optional.
     */
    public function storeDraft(Request $request): JsonResponse
    {
        $request->validate([
            'brand_id' => ['required', 'integer', 'exists:dmr_brands,id'],
            'model_id' => ['required', 'integer', 'exists:dmr_models,id'],
        ]);

        $data = $request->all();

        // Set dealer_id from authenticated user
        if ($request->user() && $request->user()->dealer) {
            $data['dealer_id'] = $request->user()->dealer->id;
        }

        // Set user_id (creator)
        $data['user_id'] = $request->user()->id;

        // Automatically set status to Draft (ID: 1)
        $data['list_status_id'] = 1;

        // Handle file uploads
        if ($request->hasFile('images')) {
            $data['images'] = $request->file('images');
        }

        $vehicle = $this->vehicleService->createVehicle($data);

        // Audit log
        try {
            $this->auditLogService->logCreate(
                $request->user(),
                'Vehicle',
                $vehicle->id,
                $vehicle->toArray(),
                $request,
                'Dealer',
                $vehicle->dealer_id,
                "Vehicle draft created: {$vehicle->title}",
                ['vehicle', 'dealer', 'draft', 'create']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for vehicle draft creation', [
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->created($vehicle->load(['dealer', 'images', 'equipment', 'specifications', 'dmrFactVehicle.variant.model.brand']), 'Vehicle draft saved successfully');
    }

    /**
     * Update vehicle
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);
        $data = $request->all();

        if (! isset($data['list_status_id']) && isset($data['vehicle_list_status_id'])) {
            $data['list_status_id'] = $data['vehicle_list_status_id'];
        }
        
        // Store before state for audit log
        $beforeState = $vehicle->toArray();

        $dealer = $vehicle->dealer;
        if ($dealer) {
            // Check max_equipment_per_vehicle limit when updating equipment
            if ($request->has('equipment_ids') && is_array($request->equipment_ids)) {
                $equipmentLimit = $this->subscriptionFeatureService->getFeatureLimit($dealer, 'max_equipment_per_vehicle', 999);
                $equipmentCount = count($request->equipment_ids);
                if ($equipmentCount > $equipmentLimit) {
                    return $this->error(
                        "You may select at most {$equipmentLimit} equipment items per vehicle. Your plan limit has been exceeded.",
                        403
                    );
                }
            }

            // Check max_vehicle_images limit when updating images
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $newImageCount = is_array($images) ? count($images) : 1;
                $maxImages = $this->subscriptionFeatureService->getFeatureLimit($dealer, 'max_vehicle_images', 0);
                if ($maxImages > 0 && $newImageCount > $maxImages) {
                    return $this->error(
                        "You may upload at most {$maxImages} images per vehicle. Your plan limit has been exceeded.",
                        403
                    );
                }
            }
        }

        // Handle file uploads
        if ($request->hasFile('images')) {
            $data['images'] = $request->file('images');
        }

        $vehicle = $this->vehicleService->updateVehicle($vehicle, $data);
        $vehicle->refresh();

        // Audit log
        try {
            $this->auditLogService->logUpdate(
                $request->user(),
                'Vehicle',
                $vehicle->id,
                $beforeState,
                $vehicle->toArray(),
                $request,
                'Dealer',
                $vehicle->dealer_id,
                "Vehicle updated: {$vehicle->title}",
                ['vehicle', 'dealer', 'update']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for vehicle update', [
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success($vehicle->load(['dealer', 'images', 'equipment', 'dmrFactVehicle.variant.model.brand']));
    }

    /**
     * Delete vehicle (soft delete)
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);
        
        // Store before state for audit log
        $beforeState = $vehicle->toArray();
        $dealerId = $vehicle->dealer_id;
        
        $this->vehicleService->deleteVehicle($vehicle);

        // Audit log
        try {
            $this->auditLogService->logDelete(
                $request->user(),
                'Vehicle',
                $id,
                $beforeState,
                $request,
                'Dealer',
                $dealerId,
                "Vehicle deleted: " . $beforeState['title'] ?? 'N/A',
                ['vehicle', 'dealer', 'delete']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for vehicle deletion', [
                'vehicle_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->noContent();
    }

    /**
     * Update vehicle status (single endpoint replaces publish/unpublish)
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['sometimes', 'in:published,unpublished,archived,draft,sold'],
            'list_status_id' => ['sometimes', 'integer', 'exists:vehicle_list_statuses,id'],
        ]);

        $vehicle = Vehicle::findOrFail($id);
        
        // If list_status_id is provided, use it directly
        if ($request->has('list_status_id')) {
            $statusId = $request->list_status_id;
        } elseif ($request->has('status')) {
            // Otherwise, convert status name to ID
            $statusId = \App\Constants\VehicleListStatus::nameToId($request->status);
            
            if (!$statusId) {
                return $this->validationError(['status' => [__('messages.api.vehicle_invalid_status_value')]]);
            }
        } else {
            return $this->validationError(['status' => [__('messages.api.vehicle_status_or_list_status_required')]]);
        }

        $oldStatusId = $vehicle->list_status_id;
        
        // Check max_listings limit if changing to published status
        if ($statusId == VehicleListStatus::PUBLISHED && $oldStatusId != VehicleListStatus::PUBLISHED) {
            $dealer = $vehicle->dealer;
            if ($dealer) {
                $publishedCount = Vehicle::where('dealer_id', $dealer->id)
                    ->where('list_status_id', VehicleListStatus::PUBLISHED)
                    ->count();
                
                // Don't count the current vehicle if it's already published
                if ($oldStatusId == VehicleListStatus::PUBLISHED) {
                    $publishedCount--;
                }
                
                if (!$this->subscriptionFeatureService->checkFeatureLimit($dealer, 'max_listings', $publishedCount)) {
                    $limit = $this->subscriptionFeatureService->getFeatureLimit($dealer, 'max_listings', 0);
                    return $this->error(
                        __('messages.api.max_listings_reached', ['limit' => $limit]),
                        [],
                        403
                    );
                }
            }
        }
        
        $vehicle->list_status_id = $statusId;
        
        if ($request->input('status') === 'published' && !$vehicle->published_at) {
            $vehicle->published_at = now();
        }

        $vehicle->save();
        $vehicle->refresh();

        // Audit log
        try {
            $this->auditLogService->logUpdate(
                $request->user(),
                'Vehicle',
                $vehicle->id,
                ['list_status_id' => $oldStatusId],
                ['list_status_id' => $statusId],
                $request,
                'Dealer',
                $vehicle->dealer_id,
                "Vehicle status changed: {$request->input('status', 'status_id ' . $statusId)}",
                ['vehicle', 'dealer', 'status', 'update']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for vehicle status change', [
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success($vehicle);
    }

    /**
     * Update vehicle price (creates price_history entry)
     */
    public function updatePrice(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'price' => 'required|numeric|min:0',
        ]);

        $vehicle = Vehicle::findOrFail($id);
        $oldPrice = $vehicle->price;

        $vehicle->price = $request->price;
        $vehicle->save();

        // Create price history entry
        \App\Models\PriceHistory::create([
            'vehicle_id' => $vehicle->id,
            'old_price' => $oldPrice,
            'new_price' => $request->price,
            'changed_by_user_id' => $request->user()->id,
        ]);

        // Audit log
        try {
            $this->auditLogService->logUpdate(
                $request->user(),
                'Vehicle',
                $vehicle->id,
                ['price' => $oldPrice],
                ['price' => $request->price],
                $request,
                'Dealer',
                $vehicle->dealer_id,
                "Vehicle price updated: {$oldPrice} -> {$request->price}",
                ['vehicle', 'dealer', 'price', 'update']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for vehicle price change', [
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success($vehicle);
    }

    /**
     * Preview vehicle data from DMR by registration or VIN (same source as /api/v1/dmr/vehicle-by-registration).
     */
    public function lookupByRegistration(Request $request): JsonResponse
    {
        $request->validate([
            'registration' => 'required_without:vin|string|max:20',
            'vin' => 'required_without:registration|string|max:17',
        ]);

        try {
            $data = $this->vehicleService->fetchVehicleDataFromNummerplade(
                $request->input('registration'),
                $request->input('vin')
            );

            return $this->success($data);
        } catch (\App\Exceptions\NummerpladeApiException $e) {
            return $this->error(
                $e->getMessage(),
                $e->toArray(),
                $e->isRetryable() ? 503 : 400
            );
        }
    }

    /**
     * Upload vehicle images
     */
    public function uploadImages(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'images' => 'required|array|min:1|max:20',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
        ]);

        $vehicle = Vehicle::findOrFail($id);
        
        // Check max_vehicle_images limit
        $dealer = $vehicle->dealer;
        if ($dealer) {
            $currentImageCount = $vehicle->images()->count();
            $newImageCount = count($request->file('images', []));
            $totalImageCount = $currentImageCount + $newImageCount;
            
            $maxImages = $this->subscriptionFeatureService->getFeatureLimit($dealer, 'max_vehicle_images', 0);
            if ($maxImages > 0 && $totalImageCount > $maxImages) {
                return $this->error(
                    __('messages.api.max_vehicle_images_total_reached', ['limit' => $maxImages]),
                    [],
                    403
                );
            }
        }
        
        // Get current sort order (highest existing sort_order + 1)
        $currentMaxSortOrder = $vehicle->images()->max('sort_order') ?? -1;
        $sortOrder = $currentMaxSortOrder + 1;
        
        $uploadedImages = [];
        
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            // Handle both array format (images[]) and single file
            if (!is_array($files)) {
                $files = [$files];
            }
            
            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $this->fileService->validateFile($file);
                    
                    // Upload file (without thumbnail first to ensure file is saved)
                    $uploadedUrl = $this->fileService->uploadFiles(
                        [$file],
                        'public',
                        'vehicles',
                        false, // Don't create thumbnails here - we'll do it explicitly below
                        false, // optimizeImages
                        300, // thumbnailWidth
                        300  // thumbnailHeight
                    )[0];
                    
                    $imagePath = str_replace('/storage/', '', parse_url($uploadedUrl, PHP_URL_PATH));
                    
                    // Explicitly create thumbnail for each image to ensure it's created
                    $thumbnailPath = null;
                    try {
                        $thumbnailUrl = $this->fileService->createThumbnail($uploadedUrl, 300, 300, 'public');
                        $thumbnailPath = str_replace('/storage/', '', parse_url($thumbnailUrl, PHP_URL_PATH));
                    } catch (\Exception $e) {
                        // Log the error but continue - image will be saved without thumbnail
                        Log::warning('Failed to create thumbnail for vehicle image', [
                            'vehicle_id' => $vehicle->id,
                            'image_path' => $imagePath,
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                    $vehicleImage = VehicleImage::create([
                        'vehicle_id' => $vehicle->id,
                        'image_path' => $imagePath,
                        'thumbnail_path' => $thumbnailPath,
                        'sort_order' => $sortOrder++,
                    ]);
                    
                    $uploadedImages[] = $vehicleImage;
                }
            }
        }

        // Audit log
        try {
            $imageCount = count($uploadedImages);
            $this->auditLogService->logCreate(
                $request->user(),
                'VehicleImage',
                $vehicle->id,
                ['images_uploaded' => $imageCount],
                $request,
                'Vehicle',
                $vehicle->id,
                "Uploaded {$imageCount} image(s) to vehicle: {$vehicle->title}",
                ['vehicle', 'dealer', 'media', 'upload']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for vehicle image upload', [
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Cover follows gallery order: first image by sort_order is the cover (index 0).
        $vehicle->update([
            'cover_image_index' => $vehicle->images()->exists() ? 0 : null,
        ]);

        return $this->success($vehicle->load('images'));
    }

    /**
     * Delete vehicle image
     */
    public function deleteImage(int $id, int $imageId, Request $request): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);
        $image = VehicleImage::where('id', $imageId)
            ->where('vehicle_id', $vehicle->id)
            ->firstOrFail();

        $imageData = $image->toArray();

        // Delete image file
        if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }

        // Delete thumbnail file
        if ($image->thumbnail_path && Storage::disk('public')->exists($image->thumbnail_path)) {
            Storage::disk('public')->delete($image->thumbnail_path);
        }

        $image->delete();

        $vehicle->update([
            'cover_image_index' => $vehicle->images()->exists() ? 0 : null,
        ]);

        // Audit log
        try {
            $this->auditLogService->logDelete(
                $request->user(),
                'VehicleImage',
                $imageId,
                $imageData,
                $request,
                'Vehicle',
                $vehicle->id,
                "Deleted image from vehicle: {$vehicle->title}",
                ['vehicle', 'dealer', 'media', 'delete']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for vehicle image deletion', [
                'vehicle_id' => $id,
                'image_id' => $imageId,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success(['message' => __('messages.messages.image_deleted_successfully')]);
    }

    /**
     * Update vehicle equipment
     */
    public function updateEquipment(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'equipment_ids' => 'required|array',
            'equipment_ids.*' => 'integer|exists:equipments,id',
        ]);

        $vehicle = Vehicle::findOrFail($id);
        $dealer = $vehicle->dealer;

        if ($dealer) {
            $equipmentLimit = $this->subscriptionFeatureService->getFeatureLimit($dealer, 'max_equipment_per_vehicle', 999);
            $equipmentCount = count($request->equipment_ids);
            if ($equipmentCount > $equipmentLimit) {
                return $this->error(
                    __('messages.api.max_equipment_per_vehicle_exceeded', ['limit' => $equipmentLimit]),
                    [],
                    403
                );
            }
        }
        
        $oldEquipmentIds = $vehicle->equipment()->pluck('equipments.id')->toArray();
        
        // Sync equipment associations
        $vehicle->equipment()->sync($request->equipment_ids);

        // Audit log
        try {
            $this->auditLogService->logUpdate(
                $request->user(),
                'Vehicle',
                $vehicle->id,
                ['equipment_ids' => $oldEquipmentIds],
                ['equipment_ids' => $request->equipment_ids],
                $request,
                'Dealer',
                $vehicle->dealer_id,
                "Vehicle equipment updated: {$vehicle->title}",
                ['vehicle', 'dealer', 'equipment', 'update']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for vehicle equipment update', [
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success($vehicle->load(['equipment', 'equipment.equipmentType']));
    }

    /**
     * Sell Your Car API — same submission logic as web {@see \App\Http\Controllers\SellYourCarController::store}.
     * POST multipart/form-data with the same fields as sell-your-car.blade.php / sell-your-car-form.js.
     */
    public function sellYourCar(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->error(__('messages.api.unauthorized'), [], ApiStatusCode::UNAUTHORIZED);
        }

        try {
            $result = $this->sellYourCarSubmissionService->submit($request, $user);
            $vehicle = $result['vehicle'];
            $token = $result['token'];

            try {
                $this->auditLogService->logCreate(
                    $user,
                    'Vehicle',
                    $vehicle->id,
                    $vehicle->toArray(),
                    $request,
                    null,
                    null,
                    'Vehicle created via Sell Your Car API',
                    ['vehicle', 'listing', 'sell-your-car', 'api']
                );
            } catch (\Exception $e) {
                Log::warning('Failed to create audit log for vehicle creation', [
                    'vehicle_id' => $vehicle->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return $this->created([
                'vehicle' => $vehicle->load(['images', 'equipment', 'specifications', 'dmrFactVehicle.variant.model.brand']),
                'token' => $token,
                'success_redirect_url' => route('sell-your-car.success', ['token' => $token]),
            ], __('messages.messages.vehicle_listed_successfully'));
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            Log::error('VehicleController::sellYourCar', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                __('messages.errors.failed_to_create_vehicle').': '.$e->getMessage(),
                ['error' => [$e->getMessage()]],
                ApiStatusCode::INTERNAL_SERVER_ERROR
            );
        }
    }
}

