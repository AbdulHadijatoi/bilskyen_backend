<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Constants\VehicleListStatus;
use App\Helpers\FilterHelper;
use App\Services\FileService;
use App\Services\VehicleService;
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
        $query = Vehicle::with(['dealer', 'user', 'images', 'details', 'equipment']);

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

        // Filter by lookup models
        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->has('model_id')) {
            $query->where('model_id', $request->model_id);
        }

        if ($request->has('model_year_id')) {
            $query->where('model_year_id', $request->model_year_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $statusId = VehicleListStatus::nameToId($request->status);
            if ($statusId) {
                $query->where('vehicle_list_status_id', $statusId);
            }
        }

        // General search across title, registration, VIN
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('registration', 'like', "%{$search}%")
                  ->orWhere('vin', 'like', "%{$search}%");
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

        // Paginate
        $perPage = $request->input('limit', 15);
        $vehicles = $query->paginate($perPage);

        return $this->paginated($vehicles);
    }

    public function show(int $id): JsonResponse
    {
        $vehicle = Vehicle::with([
            'dealer',
            'user',
            'images',
            'details',
            'equipment',
            'equipment.equipmentType',
            'priceHistory',
            'viewLogs',
            'model',
            'listingType',
            'gearType'
        ])->findOrFail($id);

        return $this->success($vehicle);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'registration' => ['nullable', 'string', 'max:20'],
            'vin' => ['nullable', 'string', 'size:17', 'regex:/^[A-HJ-NPR-Z0-9]+$/i'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'model_id' => ['nullable', 'integer', 'exists:models,id'],
            'model_year_id' => ['nullable', 'integer', 'exists:model_years,id'],
            'km_driven' => ['nullable', 'integer', 'min:0'],
            'fuel_type_id' => ['sometimes', 'nullable', 'integer', 'exists:fuel_types,id'],
            'gear_type_id' => ['nullable', 'integer', 'exists:gear_types,id'],
            'price' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'battery_capacity' => ['nullable', 'integer', 'min:0'],
            'range_km' => ['nullable', 'integer', 'min:0'],
            'charging_type' => ['nullable', 'string'],
            'engine_power' => ['nullable', 'integer', 'min:0'],
            'towing_weight' => ['nullable', 'integer', 'min:0'],
            'ownership_tax' => ['nullable', 'integer', 'min:0'],
            'first_registration_date' => ['nullable', 'date'],
            'fuel_efficiency' => ['nullable', 'numeric', 'min:0'],
            'vehicle_list_status_id' => ['sometimes', 'nullable', 'integer', 'exists:vehicle_list_statuses,id'],
            'listing_type_id' => ['nullable', 'integer', 'exists:listing_types,id'],
            'published_at' => ['nullable', 'date'],
        ]);

        $vehicle = Vehicle::findOrFail($id);
        $data = $request->all();

        // Use VehicleService to update vehicle (handles details, equipment, etc.)
        $updatedVehicle = $this->vehicleService->updateVehicle($vehicle, $data);

        return $this->success($updatedVehicle->load(['dealer', 'user', 'images', 'details', 'equipment']));
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

        $vehicle->vehicle_list_status_id = $statusId;
        
        if ($request->status === 'published' && !$vehicle->published_at) {
            $vehicle->published_at = now();
        }

        $vehicle->save();

        return $this->success($vehicle->load(['dealer', 'user', 'images', 'details', 'equipment']));
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

