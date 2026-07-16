<?php

namespace App\Services;

use App\Helpers\FormatHelper;
use App\Models\DmrColour;
use App\Models\DmrEmissionNorm;
use App\Models\DmrVariant;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Location;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Shared seller vehicle edit logic for web ({@see \App\Http\Controllers\SellerController}) and API ({@see \App\Http\Controllers\SellerProfileController}).
 */
class SellerVehicleEditService
{
    public function __construct(
        private VehicleService $vehicleService,
        private AuditLogService $auditLogService,
        private VehicleImageUploadService $vehicleImageUploadService,
    ) {}

    /**
     * Lookup lists for the edit form (DMR-aligned), same as seller-vehicle-edit Blade.
     */
    public function getLookupData(Vehicle $vehicle): array
    {
        $modelId = $vehicle->model_id;
        if ($modelId === null) {
            $vehicle->loadMissing('dmrFactVehicle.variant');
            $modelId = $vehicle->dmrFactVehicle?->variant?->model_id;
        }

        $variantsQuery = DmrVariant::query()->orderBy('name');
        if ($modelId) {
            $variantsQuery->where('model_id', $modelId);
        } elseif ($vehicle->variant_id) {
            $variantsQuery->whereKey($vehicle->variant_id);
        } else {
            $variantsQuery->whereRaw('0 = 1');
        }

        return [
            'variants' => $variantsQuery->get(),
            'dmrColours' => DmrColour::query()->orderBy('name')->get(),
            'dmrEuronorms' => DmrEmissionNorm::query()->orderBy('name')->get(),
            'equipmentTypes' => EquipmentType::with(['equipments' => function ($query) {
                $query->orderBy('name');
            }])->orderBy('name')->get(),
            'equipment' => Equipment::with('equipmentType')->orderBy('name')->get(),
            'locations' => Location::select('city', 'postcode', 'region')->orderBy('city')->get(),
        ];
    }

    /**
     * Payload for Flutter / mobile edit screen (same fields as web form + lookups).
     */
    public function buildEditFormApiPayload(Vehicle $vehicle, User $user): array
    {
        $vehicle->loadMissing(['equipment', 'images', 'dmrFactVehicle.variant.model.brand']);

        $lookup = $this->getLookupData($vehicle);

        $firstRegDate = $vehicle->first_registration_date ? Carbon::parse($vehicle->first_registration_date) : null;
        $lastInspectionDate = $vehicle->last_inspection_date ? Carbon::parse($vehicle->last_inspection_date) : null;

        $servicebog = $vehicle->servicebog ?? 'Default';

        $equipmentWithoutType = $lookup['equipment']->filter(function ($equip) {
            return ! $equip->equipment_type_id;
        });

        return [
            'vehicle' => [
                'id' => $vehicle->id,
                'title' => $vehicle->title,
                'variant_id' => $vehicle->variant_id,
                'colour_id' => $vehicle->colour_id,
                'km_driven' => $vehicle->km_driven,
                'first_registration_month' => $firstRegDate?->month,
                'first_registration_year' => $firstRegDate?->year,
                'last_inspection_month' => $lastInspectionDate?->month,
                'last_inspection_year' => $lastInspectionDate?->year,
                'km_per_liter' => $vehicle->km_per_liter,
                'maximum_weight_kg' => $vehicle->maximum_weight_kg,
                'emission_norm_id' => $vehicle->emission_norm_id,
                'equipment_ids' => $vehicle->equipment->pluck('id')->values()->all(),
                'servicebog' => $servicebog,
                'price' => $vehicle->price,
                'description' => $vehicle->description,
                'seller_phone' => $vehicle->seller_phone ?? $user->phone,
                'seller_address' => $vehicle->address ?? $user->address,
                'seller_postcode' => $vehicle->postcode ?? $user->postcode,
            ],
            'images' => $vehicle->images->sortBy('sort_order')->values()->map(function (VehicleImage $img) {
                return [
                    'id' => $img->id,
                    'url' => $img->image_url,
                    'thumbnail_url' => $img->thumbnail_url,
                    'sort_order' => $img->sort_order,
                ];
            })->all(),
            'lookup' => [
                'variants' => $lookup['variants']->map(fn ($v) => ['id' => $v->id, 'name' => $v->name])->values()->all(),
                'dmr_colours' => $lookup['dmrColours']->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()->all(),
                'dmr_euronorms' => $lookup['dmrEuronorms']->map(fn ($e) => ['id' => $e->id, 'name' => $e->name])->values()->all(),
                'equipment_types' => $lookup['equipmentTypes']->map(function ($type) {
                    return [
                        'id' => $type->id,
                        'name' => $type->name,
                        'equipments' => $type->equipments->map(fn ($eq) => [
                            'id' => $eq->id,
                            'name' => $eq->name,
                        ])->values()->all(),
                    ];
                })->values()->all(),
                'equipment_without_type' => $equipmentWithoutType->map(fn ($eq) => [
                    'id' => $eq->id,
                    'name' => $eq->name,
                ])->values()->all(),
                'locations' => $lookup['locations']->map(fn ($loc) => [
                    'city' => $loc->city,
                    'postcode' => $loc->postcode,
                    'region' => $loc->region,
                ])->values()->all(),
            ],
        ];
    }

    /**
     * Update seller vehicle (same validation and image handling as web POST seller.vehicle.update).
     *
     * @return array{vehicle: Vehicle} On success
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function updateSellerVehicle(Request $request, Vehicle $vehicle, User $user): array
    {
        $vehicle->load(['images', 'equipment', 'dmrFactVehicle.variant.model.brand']);

        $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'km_driven' => ['nullable', 'numeric', 'min:0'],
            'list_status_id' => ['sometimes', 'nullable', 'integer', 'exists:vehicle_list_statuses,id'],
            'description' => ['nullable', 'string'],
            'variant_id' => ['nullable', 'integer', 'exists:dmr_variants,id'],
            'colour_id' => ['nullable', 'integer', 'exists:dmr_colours,id'],
            'first_registration_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'first_registration_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'last_inspection_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'last_inspection_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'km_per_liter' => ['nullable', 'numeric', 'min:0'],
            'fuel_efficiency' => ['nullable', 'numeric', 'min:0'],
            'maximum_weight_kg' => ['nullable', 'integer', 'min:0'],
            'emission_norm_id' => ['nullable', 'integer', 'exists:dmr_emission_norms,id'],
            'equipment_ids' => ['nullable', 'array'],
            'equipment_ids.*' => ['exists:equipments,id'],
            'servicebog' => ['nullable', 'in:Yes,No,Default'],
            'seller_phone' => ['nullable', 'string', 'max:30'],
            'seller_address' => ['nullable', 'string'],
            'seller_postcode' => ['nullable', 'string', 'max:10'],
            'images' => ['nullable'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif'],
            'existing_image_ids' => ['nullable', 'array'],
            'existing_image_ids.*' => [
                'integer',
                Rule::exists('vehicle_images', 'id')->where('vehicle_id', $vehicle->id),
            ],
            'deleted_image_ids' => ['nullable', 'array'],
            'deleted_image_ids.*' => [
                'integer',
                Rule::exists('vehicle_images', 'id')->where('vehicle_id', $vehicle->id),
            ],
            'image_sort_order' => ['nullable', 'array'],
            'image_sort_order.*' => ['integer'],
        ]);

        $beforeState = $vehicle->toArray();

        $vehicleData = [];
            $vehicle->loadMissing(['brand', 'model']);
            $vehicleData['title'] = FormatHelper::generateListingTitleFromBrandAndModel(
                $vehicle->brand?->name,
                $vehicle->model?->name
            ) ?: $vehicle->title;
            if ($request->has('price')) {
                $vehicleData['price'] = $request->input('price');
            }
            if ($request->has('km_driven')) {
                $vehicleData['km_driven'] = $request->input('km_driven');
            }
            if ($request->has('list_status_id')) {
                $vehicleData['list_status_id'] = $request->input('list_status_id');
            }
            $kml = $request->input('km_per_liter', $request->input('fuel_efficiency'));
            if ($kml !== null && $kml !== '') {
                $vehicleData['km_per_liter'] = $kml;
            }

            if ($request->has('seller_address')) {
                $vehicleData['address'] = trim((string) $request->input('seller_address', ''));
            }
            if ($request->has('seller_postcode')) {
                $vehicleData['postcode'] = trim((string) $request->input('seller_postcode', ''));
            }

            if ($request->has('description')) {
                $vehicleData['description'] = $request->input('description');
            }

            if ($request->has('variant_id')) {
                $raw = $request->input('variant_id');
                $vehicleData['variant_id'] = ($raw === '' || $raw === null) ? null : (int) $raw;
            }

            if ($request->has('colour_id')) {
                $raw = $request->input('colour_id');
                $vehicleData['colour_id'] = ($raw === '' || $raw === null) ? null : (int) $raw;
            }

            if ($request->has('emission_norm_id')) {
                $raw = $request->input('emission_norm_id');
                $vehicleData['emission_norm_id'] = ($raw === '' || $raw === null) ? null : (int) $raw;
            }

            if ($request->has('maximum_weight_kg')) {
                $raw = $request->input('maximum_weight_kg');
                $vehicleData['maximum_weight_kg'] = ($raw === '' || $raw === null) ? null : (int) $raw;
            }

            if ($request->has('seller_phone')) {
                $vehicleData['seller_phone'] = $request->input('seller_phone');
            }

            if ($request->has('servicebog')) {
                $vehicleData['servicebog'] = $request->input('servicebog');
            }

            if ($request->has(['first_registration_month', 'first_registration_year'])) {
                $month = $request->input('first_registration_month');
                $year = $request->input('first_registration_year');
                if ($month && $year) {
                    $vehicleData['first_registration_date'] = sprintf('%04d-%02d-01', (int) $year, (int) $month);
                } else {
                    $vehicleData['first_registration_date'] = null;
                }
            }

            if ($request->has(['last_inspection_month', 'last_inspection_year'])) {
                $month = $request->input('last_inspection_month');
                $year = $request->input('last_inspection_year');
                if ($month && $year) {
                    $vehicleData['last_inspection_date'] = sprintf('%04d-%02d-01', (int) $year, (int) $month);
                } else {
                    $vehicleData['last_inspection_date'] = null;
                }
            }

            if ($request->has('deleted_image_ids') && is_array($request->input('deleted_image_ids'))) {
                $deletedImageIds = $request->input('deleted_image_ids');
                $imagesToDelete = VehicleImage::whereIn('id', $deletedImageIds)
                    ->where('vehicle_id', $vehicle->id)
                    ->get();

                foreach ($imagesToDelete as $img) {
                    try {
                        $fileService = app(FileService::class);
                        if ($img->image_path) {
                            $fileService->deleteFiles([$img->image_path]);
                        }
                        if ($img->thumbnail_path) {
                            $fileService->deleteFiles([$img->thumbnail_path]);
                        }
                    } catch (\Exception $e) {
                        if ($img->image_path && file_exists(storage_path('app/public/'.$img->image_path))) {
                            @unlink(storage_path('app/public/'.$img->image_path));
                        }
                        if ($img->thumbnail_path && file_exists(storage_path('app/public/'.$img->thumbnail_path))) {
                            @unlink(storage_path('app/public/'.$img->thumbnail_path));
                        }
                    }
                    $img->delete();
                }
            }

            if ($request->has('image_sort_order') && is_array($request->input('image_sort_order'))) {
                $sortOrder = $request->input('image_sort_order');
                foreach ($sortOrder as $imageId => $order) {
                    VehicleImage::where('id', $imageId)
                        ->where('vehicle_id', $vehicle->id)
                        ->update(['sort_order' => (int) $order]);
                }
            }

            if ($request->hasFile('images')) {
                $newImages = $request->file('images');
                if (! is_array($newImages)) {
                    $newImages = [$newImages];
                }

                $currentMaxSortOrder = VehicleImage::where('vehicle_id', $vehicle->id)->max('sort_order') ?? -1;
                $nextSortOrder = $currentMaxSortOrder + 1;

                if ($request->has('image_sort_order') && is_array($request->input('image_sort_order'))) {
                    $sortOrder = $request->input('image_sort_order');
                    if (! empty($sortOrder)) {
                        $maxUsedOrder = max(array_values($sortOrder));
                        $nextSortOrder = $maxUsedOrder + 1;
                    }
                }

                $this->vehicleImageUploadService->uploadVehicleImages($vehicle, $newImages, $nextSortOrder);
            }

            $vehicleData['equipment_ids'] = $request->input('equipment_ids', []);

            $updatedVehicle = $this->vehicleService->updateVehicle($vehicle, $vehicleData);

            try {
                $this->auditLogService->logUpdate(
                    $user,
                    'Vehicle',
                    $vehicle->id,
                    $beforeState,
                    $updatedVehicle->toArray(),
                    $request,
                    'Seller',
                    null,
                    "Vehicle updated by seller: {$updatedVehicle->title}",
                    ['vehicle', 'seller', 'update']
                );
            } catch (\Exception $e) {
                Log::warning('Failed to create audit log for vehicle update', [
                    'vehicle_id' => $vehicle->id,
                    'error' => $e->getMessage(),
                ]);
            }

        return [
            'vehicle' => $updatedVehicle->load(['images', 'equipment', 'dmrFactVehicle.variant.model.brand']),
        ];
    }
}
