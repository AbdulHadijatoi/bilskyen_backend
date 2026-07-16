<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Constants\ApiStatusCode;
use App\Constants\VehicleListStatus;
use App\Helpers\FilterHelper;
use App\Services\FileService;
use App\Services\VehicleDetailPresentationService;
use App\Services\VehicleService;
use App\Services\VehicleImageUploadService;
use App\Services\ListingBillingService;
use App\Services\ListingExpirationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

/**
 * Admin Vehicle Controller
 * Admin can see listings from any dealer (not restricted to own dealer)
 */
class AdminVehicleController extends Controller
{
    protected FileService $fileService;

    protected VehicleService $vehicleService;

    protected VehicleDetailPresentationService $vehicleDetailPresentationService;

    protected VehicleImageUploadService $vehicleImageUploadService;

    public function __construct(
        FileService $fileService,
        VehicleService $vehicleService,
        VehicleDetailPresentationService $vehicleDetailPresentationService,
        VehicleImageUploadService $vehicleImageUploadService,
        private ListingBillingService $listingBillingService,
        private ListingExpirationService $listingExpirationService,
    ) {
        $this->fileService = $fileService;
        $this->vehicleService = $vehicleService;
        $this->vehicleDetailPresentationService = $vehicleDetailPresentationService;
        $this->vehicleImageUploadService = $vehicleImageUploadService;
    }

    private function applyListingStatusTransition(Vehicle $vehicle, int $oldStatusId, int $newStatusId): void
    {
        if ($newStatusId === VehicleListStatus::PUBLISHED && $oldStatusId !== VehicleListStatus::PUBLISHED) {
            $this->listingExpirationService->setExpiryOnPublish($vehicle, $vehicle->dealer_id === null);
            if ($vehicle->dealer_id) {
                $this->listingBillingService->onVehiclePublished($vehicle->fresh());
            }
        } elseif ($oldStatusId === VehicleListStatus::PUBLISHED && $newStatusId !== VehicleListStatus::PUBLISHED) {
            $this->listingBillingService->onVehicleUnpublished($vehicle);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function adminVehicleShowEagerLoads(): array
    {
        return array_merge($this->vehicleDetailPresentationService->detailEagerLoads(), [
            'dealer' => function ($q) {
                $q->with('owner');
            },
            'user',
            'images' => function ($q) {
                $q->orderBy('sort_order');
            },
            'equipment.equipmentType',
            'priceHistory',
            'viewLogs',
            'dmrFactVehicle.variant.model.brand',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAdminVehicleShowPayload(Vehicle $vehicle): array
    {
        $payload = $this->vehicleDetailPresentationService->buildDetailPayload($vehicle);
        $dealer = $vehicle->dealer;

        return array_merge($payload, [
            'dealer_id' => $vehicle->dealer_id,
            'user_id' => $vehicle->user_id,
            'list_status_id' => $vehicle->list_status_id,
            'published_at' => $vehicle->published_at?->format('Y-m-d H:i:s'),
            'expires_at' => $vehicle->expires_at?->format('Y-m-d H:i:s'),
            'listing_billing_started_at' => $vehicle->listing_billing_started_at?->format('Y-m-d H:i:s'),
            'listing_billing_paused_at' => $vehicle->listing_billing_paused_at?->format('Y-m-d H:i:s'),
            'created_at' => $vehicle->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $vehicle->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $vehicle->deleted_at?->format('Y-m-d H:i:s'),
            'dealer' => $dealer ? [
                'id' => $dealer->id,
                'cvr' => $dealer->cvr,
                'city' => $dealer->city,
                'address' => $dealer->address,
                'slug' => $dealer->slug,
                'name' => $dealer->owner?->name,
                'email' => $dealer->owner?->email,
                'owner' => $dealer->owner ? [
                    'id' => $dealer->owner->id,
                    'name' => $dealer->owner->name,
                    'email' => $dealer->owner->email,
                ] : null,
            ] : null,
            'user' => $vehicle->user,
            'images' => $vehicle->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'vehicle_id' => $image->vehicle_id,
                    'image_url' => $image->image_url,
                    'thumbnail_url' => $image->thumbnail_url,
                    'url' => $image->image_url,
                    'sort_order' => $image->sort_order,
                ];
            }),
            'equipment' => $vehicle->equipment,
            'price_history' => $vehicle->relationLoaded('priceHistory') ? $vehicle->priceHistory : [],
            'view_logs' => $vehicle->relationLoaded('viewLogs') ? $vehicle->viewLogs : [],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Vehicle::with([
            'dealer.owner',
            'user',
            'images',
            'equipment',
            'vehicleListStatus',
            'dmrFactVehicle.variant.model.brand',
        ]);

        // Apply direct query parameter filters
        if ($request->has('dealer_id')) {
            $query->where('dealer_id', $request->dealer_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by dealer name (owner name, CVR, address, city)
        if ($request->has('dealer_name') && $request->input('dealer_name')) {
            $dealerName = $request->input('dealer_name');
            $query->whereHas('dealer', function ($q) use ($dealerName) {
                $q->where('cvr', 'like', "%{$dealerName}%")
                  ->orWhere('address', 'like', "%{$dealerName}%")
                  ->orWhere('city', 'like', "%{$dealerName}%")
                  ->orWhereHas('owner', function ($ownerQuery) use ($dealerName) {
                      $ownerQuery->where('name', 'like', "%{$dealerName}%")
                          ->orWhere('email', 'like', "%{$dealerName}%");
                  });
            });
        }

        // Filter by user name
        if ($request->has('user_name') && $request->input('user_name')) {
            $userName = $request->input('user_name');
            $query->whereHas('user', function ($q) use ($userName) {
                $q->where('name', 'like', "%{$userName}%");
            });
        }

        // Filter by DMR brand / model / year
        if ($request->has('brand_id')) {
            $query->whereHas('dmrFactVehicle.variant.model', function ($q) use ($request) {
                $q->where('brand_id', (int) $request->brand_id);
            });
        }

        if ($request->has('model_id')) {
            $query->whereHas('dmrFactVehicle.variant', function ($q) use ($request) {
                $q->where('model_id', (int) $request->model_id);
            });
        }

        if ($request->has('model_year_id')) {
            $year = (int) $request->model_year_id;
            if ($year >= 1950 && $year <= 2100) {
                $query->whereHas('dmrFactVehicle', function ($q) use ($year) {
                    $q->where('model_aar', $year);
                });
            }
        }

        // Filter by list_status_id (canonical), legacy vehicle_list_status_id, or status name
        $listStatusIdParam = $request->input('list_status_id');
        if ($listStatusIdParam === null || $listStatusIdParam === '') {
            $listStatusIdParam = $request->input('vehicle_list_status_id');
        }
        if ($listStatusIdParam !== null && $listStatusIdParam !== '') {
            $resolvedListStatusId = (int) $listStatusIdParam;
            if (VehicleListStatus::isValid($resolvedListStatusId)) {
                $query->where('list_status_id', $resolvedListStatusId);
            }
        } elseif ($request->has('status') && $request->input('status')) {
            $statusId = VehicleListStatus::nameToId((string) $request->input('status'));
            if ($statusId) {
                $query->where('list_status_id', $statusId);
            }
        }

        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('registration', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply advanced JSON filters (for backward compatibility)
        $filters = json_decode($request->input('filters', '[]'), true);
        if (!empty($filters)) {
            $joinOperator = $request->input('joinOperator', 'or');
            FilterHelper::applyFilters($query, $filters, $joinOperator);
        }

        // Apply sorting
        $sort = json_decode($request->input('sort', '[]'), true);
        if (empty($sort)) {
            // Default sorting by created_at desc
            $query->orderBy('created_at', 'desc');
        } else {
            FilterHelper::applySorting($query, $sort);
        }

        $listStatusCounts = $this->aggregateListStatusCounts(clone $query);

        // Paginate
        $perPage = (int) $request->input('limit', 15);
        $paginator = $query->paginate($perPage);

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
     * Count vehicles per list_status_id using the same constraints as the list query.
     *
     * @return array<int, int>
     */
    private function aggregateListStatusCounts(Builder $query): array
    {
        // `Vehicle` global scope `defaultOrder` adds `order by id desc` when orders are empty.
        // `reorder()` clears orders, which re-triggers that scope and breaks ONLY_FULL_GROUP_BY
        // on this aggregate. Drop the default order scope and order by the grouped column.
        $rows = (clone $query)
            ->withoutGlobalScope('defaultOrder')
            ->withoutEagerLoads()
            ->reorder()
            ->selectRaw('list_status_id, COUNT(*) as aggregate')
            ->groupBy('list_status_id')
            ->orderBy('list_status_id')
            ->pluck('aggregate', 'list_status_id');

        $out = [];
        foreach (VehicleListStatus::values() as $id) {
            $out[$id] = (int) ($rows->get($id) ?? $rows->get((string) $id) ?? 0);
        }

        return $out;
    }

    public function show(int $id): JsonResponse
    {
        $vehicle = Vehicle::with($this->adminVehicleShowEagerLoads())->findOrFail($id);

        return $this->success($this->buildAdminVehicleShowPayload($vehicle));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'registration' => ['nullable', 'string', 'max:20'],
            'vin' => ['nullable', 'string', 'size:17', 'regex:/^[A-HJ-NPR-Z0-9]+$/i'],
            'dmr_fact_vehicle_id' => ['sometimes', 'nullable', 'integer', 'exists:dmr_fact_vehicles,id'],
            'brand_id' => ['nullable', 'integer', 'exists:dmr_brands,id'],
            'model_id' => ['nullable', 'integer', 'exists:dmr_models,id'],
            'variant_id' => ['nullable', 'integer', 'exists:dmr_variants,id'],
            'fuel_type_id' => ['nullable', 'integer', 'exists:dmr_drive_energies,id'],
            'body_type_id' => ['nullable', 'integer', 'exists:dmr_body_types,id'],
            'colour_id' => ['nullable', 'integer', 'exists:dmr_colours,id'],
            'emission_norm_id' => ['nullable', 'integer', 'exists:dmr_emission_norms,id'],
            'vehicle_use_id' => ['nullable', 'integer', 'exists:dmr_vehicle_uses,id'],
            'listing_type_id' => ['nullable', 'integer', 'exists:listing_types,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sales_type_id' => ['nullable', 'integer', 'exists:sales_types,id'],
            'price_type_id' => ['nullable', 'integer', 'exists:price_types,id'],
            'model_year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'km_driven' => ['nullable', 'numeric', 'min:0'],
            'gear_type_id' => ['nullable', 'integer', 'exists:gear_types,id'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'towing_weight' => ['nullable', 'integer', 'min:0'],
            'battery_capacity' => ['nullable', 'integer', 'min:0'],
            'range_km' => ['nullable', 'integer', 'min:0'],
            'charging_type' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'highlights' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'address' => ['nullable', 'string'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'seller_phone' => ['nullable', 'string', 'max:50'],
            'condition_id' => ['nullable', 'integer', 'exists:conditions,id'],
            'servicebog' => ['nullable', 'string', 'max:50'],
            'list_status_id' => ['sometimes', 'nullable', 'integer', 'exists:vehicle_list_statuses,id'],
            'vehicle_list_status_id' => ['sometimes', 'nullable', 'integer', 'exists:vehicle_list_statuses,id'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'first_registration_date' => ['nullable', 'date'],
            'last_inspection_date' => ['nullable', 'date'],
            'production_date' => ['nullable', 'date'],
            'co2_emission' => ['nullable', 'numeric', 'min:0'],
            'engine_power_kw' => ['nullable', 'numeric', 'min:0'],
            'km_per_liter' => ['nullable', 'numeric', 'min:0'],
            'fuel_consumption_wltp' => ['nullable', 'numeric', 'min:0'],
            'fuel_consumption_nedc' => ['nullable', 'numeric', 'min:0'],
            'internal_cost_price' => ['nullable', 'numeric', 'min:0'],
            'annual_tax' => ['nullable', 'numeric', 'min:0'],
            'is_import' => ['nullable', 'boolean'],
            'is_factory_new' => ['nullable', 'boolean'],
            'cover_image_index' => ['nullable', 'integer', 'min:0'],
            'equipment_ids' => ['nullable', 'array'],
            'equipment_ids.*' => ['integer', 'exists:equipments,id'],
        ]);

        $vehicle = Vehicle::findOrFail($id);
        $data = $request->all();

        $updatedVehicle = $this->vehicleService->updateVehicle($vehicle, $data);
        $updatedVehicle->load($this->adminVehicleShowEagerLoads());

        return $this->success($this->buildAdminVehicleShowPayload($updatedVehicle));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['sometimes', Rule::in(VehicleListStatus::names())],
            'list_status_id' => ['sometimes', 'integer', 'exists:vehicle_list_statuses,id'],
        ]);

        $vehicle = Vehicle::findOrFail($id);

        if ($request->has('list_status_id')) {
            $statusId = (int) $request->list_status_id;
        } elseif ($request->has('status')) {
            $statusId = VehicleListStatus::nameToId($request->status);
            if (!$statusId) {
                return $this->validationError(['status' => [__('messages.api.vehicle_invalid_status_value')]]);
            }
        } else {
            return $this->validationError(['status' => [__('messages.api.vehicle_status_or_list_status_required')]]);
        }

        $oldStatusId = (int) $vehicle->list_status_id;
        $vehicle->list_status_id = $statusId;

        if ($statusId === VehicleListStatus::PUBLISHED && !$vehicle->published_at) {
            $vehicle->published_at = now();
        }

        $vehicle->save();
        $vehicle->refresh();

        $this->applyListingStatusTransition($vehicle, $oldStatusId, $statusId);

        return $this->success($vehicle->load(['dealer', 'user', 'images', 'equipment', 'dmrFactVehicle.variant.model.brand']));
    }

    public function renewListing(int $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);
        $oldStatusId = (int) $vehicle->list_status_id;

        if ($oldStatusId === VehicleListStatus::ARCHIVED) {
            $vehicle->list_status_id = VehicleListStatus::PUBLISHED;
            $vehicle->published_at = now();
            $vehicle->save();
            $this->applyListingStatusTransition($vehicle, $oldStatusId, VehicleListStatus::PUBLISHED);
            $vehicle->refresh();
        }

        $this->listingExpirationService->renewListing($vehicle);

        return $this->success($vehicle->fresh()->load(['dealer', 'user', 'images']));
    }

    public function updateListingLifecycle(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'expires_at' => ['nullable', 'date'],
            'published_at' => ['nullable', 'date'],
            'recalculate_expiry' => ['sometimes', 'boolean'],
            'clear_expiry' => ['sometimes', 'boolean'],
        ]);

        $vehicle = Vehicle::findOrFail($id);

        if ($request->boolean('clear_expiry')) {
            $vehicle->expires_at = null;
        } elseif ($request->boolean('recalculate_expiry')) {
            $this->listingExpirationService->setExpiryOnPublish($vehicle, $vehicle->dealer_id === null);
            $vehicle->refresh();
        } elseif ($request->has('expires_at')) {
            $vehicle->expires_at = $request->input('expires_at');
        }

        if ($request->has('published_at')) {
            $vehicle->published_at = $request->input('published_at');
        }

        $vehicle->save();

        return $this->success($vehicle->fresh()->load(['dealer', 'user', 'images']));
    }

    public function rejectPendingReview(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'list_status_id' => ['sometimes', 'integer', Rule::in([VehicleListStatus::DRAFT, VehicleListStatus::ARCHIVED])],
        ]);

        $vehicle = Vehicle::findOrFail($id);

        if ((int) $vehicle->list_status_id !== VehicleListStatus::PENDING_REVIEW) {
            return $this->error(__('messages.api.vehicle_not_pending_review'), [], 422);
        }

        $newStatusId = (int) $request->input('list_status_id', VehicleListStatus::DRAFT);
        $vehicle->list_status_id = $newStatusId;
        $vehicle->save();

        return $this->success($vehicle->fresh()->load(['dealer', 'user', 'images']));
    }

    public function pendingReview(Request $request): JsonResponse
    {
        $request->merge(['list_status_id' => VehicleListStatus::PENDING_REVIEW]);

        return $this->index($request);
    }

    public function approvePendingReview(int $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);

        if ((int) $vehicle->list_status_id !== VehicleListStatus::PENDING_REVIEW) {
            return $this->error(__('messages.api.vehicle_not_pending_review'), [], 422);
        }

        $vehicle->list_status_id = VehicleListStatus::PUBLISHED;
        $vehicle->published_at = now();
        $vehicle->save();

        $this->applyListingStatusTransition($vehicle, VehicleListStatus::PENDING_REVIEW, VehicleListStatus::PUBLISHED);

        return $this->success($vehicle->fresh()->load(['dealer', 'user', 'images']));
    }

    public function delete(int $id, Request $request): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete(); // Soft delete

        return $this->noContent();
    }

    public function getImages(int $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);
        $images = $vehicle->images()->orderBy('sort_order')->get();

        return $this->success($images);
    }

    public function updateImages(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'images' => 'nullable|array',
            'images.*' => 'nullable|string', // URLs as strings
            'files' => 'nullable|array',
            'files.*' => 'nullable|image|max:10240', // File uploads (10MB max)
        ]);

        $vehicle = Vehicle::findOrFail($id);
        
        // Get list of images to keep (from the request)
        $imageUrls = $request->input('images', []);
        $imagesToKeep = [];
        foreach ($imageUrls as $imageUrl) {
            if (is_string($imageUrl) && !empty($imageUrl)) {
                $imagePath = str_replace('/storage/', '', parse_url($imageUrl, PHP_URL_PATH));
                $imagesToKeep[$imagePath] = true;
            }
        }
        
        // Preserve existing image data (including thumbnail paths) for images we're keeping
        $existingImagesData = [];
        $existingImages = $vehicle->images;
        foreach ($existingImages as $image) {
            $imagePath = $image->image_path; // Already a path, not a URL
            if ($imagePath && isset($imagesToKeep[$imagePath])) {
                // This image is being kept, preserve its thumbnail path
                $existingImagesData[$imagePath] = [
                    'thumbnail_path' => $image->thumbnail_path,
                ];
            }
        }
        
        // Delete images that are NOT being kept (and their files)
        foreach ($existingImages as $image) {
            $imagePath = $image->image_path;
            if (!isset($imagesToKeep[$imagePath])) {
                // This image is being removed, delete its files
                if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
                if ($image->thumbnail_path && Storage::disk('public')->exists($image->thumbnail_path)) {
                    Storage::disk('public')->delete($image->thumbnail_path);
                }
            }
        }
        
        // Delete all existing image records (we'll recreate them)
        $vehicle->images()->delete();

        // Process images (both existing and new)
        $sortOrder = 0;
        
        // Handle URL strings (existing images being preserved)
        foreach ($imageUrls as $imageUrl) {
            if (is_string($imageUrl) && !empty($imageUrl)) {
                $imagePath = str_replace('/storage/', '', parse_url($imageUrl, PHP_URL_PATH));
                
                // Check if we have a preserved thumbnail path for this image
                $thumbnailPath = null;
                if (isset($existingImagesData[$imagePath]['thumbnail_path']) && 
                    $existingImagesData[$imagePath]['thumbnail_path'] &&
                    Storage::disk('public')->exists($existingImagesData[$imagePath]['thumbnail_path'])) {
                    // Use the preserved thumbnail path (file still exists because we didn't delete it)
                    $thumbnailPath = $existingImagesData[$imagePath]['thumbnail_path'];
                } else {
                    // Try to generate thumbnail if it doesn't exist
                    try {
                        $thumbnailUrl = $this->fileService->createThumbnail($imageUrl, 300, 300, 'public');
                        $thumbnailPath = str_replace('/storage/', '', parse_url($thumbnailUrl, PHP_URL_PATH));
                    } catch (\Exception $e) {
                        // Thumbnail generation failed, continue without thumbnail
                        \Log::warning('Failed to create thumbnail for existing vehicle image', [
                            'vehicle_id' => $vehicle->id,
                            'image_path' => $imagePath,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                VehicleImage::create([
                    'vehicle_id' => $vehicle->id,
                    'image_path' => $imagePath,
                    'thumbnail_path' => $thumbnailPath,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }
        
        // Handle file uploads
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            $this->vehicleImageUploadService->uploadVehicleImages($vehicle, $files, $sortOrder);
        }

        return $this->success($vehicle->load('images'));
    }

    public function deleteImage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'image_id' => 'required|integer|exists:vehicle_images,id',
        ]);

        $vehicle = Vehicle::findOrFail($id);
        $image = VehicleImage::where('id', $request->image_id)
            ->where('vehicle_id', $vehicle->id)
            ->firstOrFail();

        // Delete image file
        if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }

        // Delete thumbnail file
        if ($image->thumbnail_path && Storage::disk('public')->exists($image->thumbnail_path)) {
            Storage::disk('public')->delete($image->thumbnail_path);
        }

        $image->delete();

        return $this->success(['message' => __('messages.messages.image_deleted_successfully')]);
    }

    public function updateEquipment(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'equipment_ids' => 'required|array',
            'equipment_ids.*' => 'integer|exists:equipments,id',
        ]);

        $vehicle = Vehicle::findOrFail($id);
        
        // Sync equipment associations
        $vehicle->equipment()->sync($request->equipment_ids);

        return $this->success($vehicle->load('equipment'));
    }

    public function getHistory(int $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);
        
        $history = [
            'price_history' => $vehicle->priceHistory()
                ->with('changedByUser')
                ->orderBy('changed_at', 'desc')
                ->get(),
            'view_logs' => $vehicle->viewLogs()
                ->with('user')
                ->orderBy('viewed_at', 'desc')
                ->limit(100)
                ->get(),
        ];

        return $this->success($history);
    }
}

