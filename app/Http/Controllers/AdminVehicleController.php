<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Constants\ApiStatusCode;
use App\Constants\VehicleListStatus;
use App\Helpers\FilterHelper;
use App\Services\FileService;
use App\Services\VehicleService;
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

    public function __construct(FileService $fileService, VehicleService $vehicleService)
    {
        $this->fileService = $fileService;
        $this->vehicleService = $vehicleService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Vehicle::with([
            'dealer',
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

        // Filter by dealer name (searches CVR, address, city)
        if ($request->has('dealer_name') && $request->input('dealer_name')) {
            $dealerName = $request->input('dealer_name');
            $query->whereHas('dealer', function ($q) use ($dealerName) {
                $q->where('cvr', 'like', "%{$dealerName}%")
                  ->orWhere('address', 'like', "%{$dealerName}%")
                  ->orWhere('city', 'like', "%{$dealerName}%");
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
        $vehicle = Vehicle::with([
            'dealer',
            'user',
            'images',
            'equipment',
            'equipment.equipmentType',
            'priceHistory',
            'viewLogs',
            'gearType',
            'condition',
            'dmrFactVehicle.variant.model.brand',
        ])->findOrFail($id);

        return $this->success($vehicle);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'registration' => ['nullable', 'string', 'max:20'],
            'dmr_fact_vehicle_id' => ['sometimes', 'integer', 'exists:dmr_fact_vehicles,id'],
            'km_driven' => ['nullable', 'numeric', 'min:0'],
            'gear_type_id' => ['nullable', 'integer', 'exists:gear_types,id'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'battery_capacity' => ['nullable', 'integer', 'min:0'],
            'range_km' => ['nullable', 'integer', 'min:0'],
            'charging_type' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'condition_id' => ['nullable', 'integer', 'exists:conditions,id'],
            'servicebog' => ['nullable', 'string', 'max:50'],
            'list_status_id' => ['sometimes', 'nullable', 'integer', 'exists:vehicle_list_statuses,id'],
            'published_at' => ['nullable', 'date'],
        ]);

        $vehicle = Vehicle::findOrFail($id);
        $data = $request->all();

        // Use VehicleService to update vehicle (handles details, equipment, etc.)
        $updatedVehicle = $this->vehicleService->updateVehicle($vehicle, $data);

        return $this->success($updatedVehicle->load(['dealer', 'user', 'images', 'equipment', 'dmrFactVehicle.variant.model.brand']));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(VehicleListStatus::names())],
        ]);

        $vehicle = Vehicle::findOrFail($id);
        $statusId = VehicleListStatus::nameToId($request->status);

        if (!$statusId) {
            return $this->validationError(['status' => ['Invalid status value']]);
        }

        $vehicle->list_status_id = $statusId;
        
        if ($request->status === 'published' && !$vehicle->published_at) {
            $vehicle->published_at = now();
        }

        $vehicle->save();

        return $this->success($vehicle->load(['dealer', 'user', 'images', 'equipment', 'dmrFactVehicle.variant.model.brand']));
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
                        \Log::warning('Failed to create thumbnail for vehicle image', [
                            'vehicle_id' => $vehicle->id,
                            'image_path' => $imagePath,
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                    VehicleImage::create([
                        'vehicle_id' => $vehicle->id,
                        'image_path' => $imagePath,
                        'thumbnail_path' => $thumbnailPath,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
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

