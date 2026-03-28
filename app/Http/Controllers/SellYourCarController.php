<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Models\VehicleModel;
use App\Models\DmrDriveEnergy;
use App\Constants\VehicleListStatus as VehicleListStatusConstant;
use App\Models\GearType;
use App\Models\Condition;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Variant;
use App\Models\Euronom;
use App\Models\DmrBrand;
use App\Models\DmrModel;
use App\Models\DmrVariant;
use App\Models\DmrColour;
use App\Models\DmrEmissionNorm;
use App\Models\DmrFactVehicle;
use App\Models\Location;
use App\Models\FeaturedListing;
use App\Services\AuthService;
use App\Services\AuditLogService;
use App\Services\DmrLookupAssociationService;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Carbon\Carbon;

class SellYourCarController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private AuditLogService $auditLogService,
        private FileService $fileService,
        private DmrLookupAssociationService $dmrLookupAssociationService,
    ) {}

    /**
     * Equipment types + items for the equipment checkboxes partial (initial page and lookupContext HTML).
     */
    private function sellYourCarEquipmentLookupData(): array
    {
        return [
            'equipmentTypes' => EquipmentType::with(['equipments' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get(),
            'equipment' => Equipment::with('equipmentType')->orderBy('name')->get(),
        ];
    }

    /**
     * Show the sell your car form page
     */
    public function show(Request $request)
    {
        $user = $this->authService->getAuthenticatedUser($request);
        
        if (!$user) {
            return redirect()->route('login')->with('return_url', '/sell-your-car');
        }

        $user->load('dealer');

        $empty = collect();

        $equipmentLookup = $this->sellYourCarEquipmentLookupData();

        // DMR lists load after registration lookup (see lookupContext); equipment is shared with manual entry.
        $lookupData = [
            'models' => $empty,
            'brands' => $empty,
            'dmrBrands' => $empty,
            'dmrModels' => $empty,
            'dmrVariants' => $empty,
            'dmrColours' => DmrColour::query()->orderBy('name')->get(),
            'dmrEuronorms' => DmrEmissionNorm::query()->orderBy('name')->get(),
            'dmrDriveEnergies' => $empty,
            'variants' => $empty,
            'modelYears' => $empty,
            'equipmentTypes' => $equipmentLookup['equipmentTypes'],
            'equipment' => $equipmentLookup['equipment'],
            'gearTypes' => GearType::orderBy('name')->get(),
            'conditions' => Condition::orderBy('name')->get(),
            'locations' => Location::select('city', 'postcode', 'region')->orderBy('city')->get(),
        ];


        return view('sell-your-car', [
            'user' => $user,
            'lookupData' => $lookupData,
        ]);
    }

    /**
     * After registration lookup: DMR dropdown options scoped to the fact vehicle + full equipment list HTML.
     */
    public function lookupContext(Request $request, int $dmrFactVehicleId): JsonResponse
    {
        $user = $this->authService->getAuthenticatedUser($request);
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $fv = DmrFactVehicle::query()
            ->with([
                'variant.model.brand',
                'colour',
                'emissionNorm',
                'drivmiddelLines' => fn ($q) => $q->orderBy('line_order'),
                'drivmiddelLines.driveEnergy',
            ])
            ->find($dmrFactVehicleId);

        if (!$fv) {
            return response()->json(['message' => 'Vehicle not found'], 404);
        }

        $variant = $fv->variant;
        $model = $variant?->model;
        $brand = $model?->brand;

        $brands = $brand ? collect([$brand]) : collect();
        $models = $brand
            ? DmrModel::where('brand_id', $brand->id)->orderBy('name')->get()
            : collect();
        $variants = $model
            ? DmrVariant::where('model_id', $model->id)->orderBy('name')->get()
            : collect();

        $dmrColours = $fv->colour_id
            ? DmrColour::where('id', $fv->colour_id)->get()
            : collect();

        $dmrEuronorms = $fv->emission_norm_id
            ? DmrEmissionNorm::where('id', $fv->emission_norm_id)->get()
            : collect();

        $energyIds = $fv->drivmiddelLines->pluck('drive_energy_id')->filter()->unique()->values();
        $dmrDriveEnergies = $energyIds->isNotEmpty()
            ? DmrDriveEnergy::whereIn('id', $energyIds)->orderBy('name')->get()
            : collect();

        $modelYears = collect();
        if ($fv->model_aar !== null) {
            $y = (int) $fv->model_aar;
            $modelYears = collect([(object) ['id' => $y, 'name' => (string) $y]]);
        }

        $equipmentHtml = view('partials.sell-your-car-equipment', [
            'lookupData' => $this->sellYourCarEquipmentLookupData(),
        ])->render();

        return response()->json([
            'success' => true,
            'selects' => [
                'manual_brand_id' => $brands->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values(),
                'manual_model_id' => $models->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'brand_id' => $m->brand_id,
                ])->values(),
                'manual_model_year_id' => $modelYears->map(fn ($y) => ['id' => $y->id, 'name' => $y->name])->values(),
                'manual_fuel_type_id' => $dmrDriveEnergies->map(fn ($f) => ['id' => $f->id, 'name' => $f->name])->values(),
                'variant_id' => $variants->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'model_id' => $v->model_id,
                ])->values(),
                'color_id' => $dmrColours->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
                'euronom_id' => $dmrEuronorms->map(fn ($e) => ['id' => $e->id, 'name' => $e->name])->values(),
            ],
            'equipment_html' => $equipmentHtml,
        ]);
    }

    /**
     * Handle form submission and create vehicle
     */
    public function store(Request $request)
    {
        $user = $this->authService->getAuthenticatedUser($request);
        
        if (!$user) {
            return redirect()->route('login')->with('return_url', '/sell-your-car');
        }
        // Log all request data for debugging
        $allRequestData = $request->all();
        

        // Separate files from other data for cleaner logging
        $requestDataWithoutFiles = $allRequestData;
        $fileInfo = [];
        
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            $fileInfo = [
                'has_images' => true,
                'count' => is_array($images) ? count($images) : 1,
                'file_names' => is_array($images) 
                    ? array_map(fn($img) => $img->getClientOriginalName() . ' (' . $img->getSize() . ' bytes)', $images)
                    : [$images->getClientOriginalName() . ' (' . $images->getSize() . ' bytes)']
            ];
            // Remove files from the main log data
            unset($requestDataWithoutFiles['images']);
        } else {
            $fileInfo = ['has_images' => false];
        }

        $rawDmr = $request->input('dmr_fact_vehicle_id');
        $dmrFactVehicleId = ($rawDmr !== null && $rawDmr !== '') ? (int) $rawDmr : null;

        $validationRules = [
            'title' => 'nullable|string|max:255',
            'registration' => $dmrFactVehicleId !== null
                ? ['required', 'string', 'max:20']
                : ['nullable', 'string', 'max:20'],
            'price' => 'required|integer|min:0',
            'km_driven' => 'required|integer|min:0',
            'charging_type' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'equipment_ids' => 'nullable|array',
            'equipment_ids.*' => 'exists:equipments,id',
            'gear_type_id' => 'required|integer|exists:gear_types,id',
            'variant_id' => 'nullable|integer|exists:dmr_variants,id',
            'km_per_liter' => 'nullable|numeric|min:0',
            'maximum_weight_kg' => 'nullable|integer|min:0',
            'colour_id' => 'nullable|integer|exists:dmr_colours,id',
            'emission_norm_id' => 'nullable|integer|exists:dmr_emission_norms,id',
            'first_registration_month' => 'nullable|integer|min:1|max:12',
            'first_registration_year' => 'nullable|integer|min:1900|max:'.((int) date('Y') + 1),
            'last_inspection_month' => 'nullable|integer|min:1|max:12',
            'last_inspection_year' => 'nullable|integer|min:1900|max:'.((int) date('Y') + 1),
            'last_inspection_date' => 'nullable|date',
            'seller_phone' => 'required|string|max:30',
            'seller_address' => 'required|string',
            'seller_postcode' => 'required|string|max:10',
            'images' => 'nullable',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif',
            'condition_id' => 'nullable|integer|exists:conditions,id',
            'servicebog' => 'nullable|string|max:20',
            'lookup_equipments' => 'nullable|string|max:65535',
            'lookup_specifications' => 'nullable|string|max:65535',
        ];

        if ($dmrFactVehicleId !== null) {
            $validationRules['dmr_fact_vehicle_id'] = 'required|integer|exists:dmr_fact_vehicles,id';
        } else {
            $validationRules['dmr_fact_vehicle_id'] = 'nullable';
            $validationRules['brand_id'] = 'required|integer|exists:dmr_brands,id';
            $validationRules['model_id'] = 'required|integer|exists:dmr_models,id';
            $validationRules['model_year'] = 'required|integer|min:1975|max:'.((int) date('Y') + 1);
            $validationRules['fuel_type_id'] = 'required|integer|exists:dmr_drive_energies,id';
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            // Handle AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('messages.api.validation_failed'),
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            if (!$request->hasFile('images')) {
                $msg = __('messages.errors.failed_to_create_vehicle') . ': At least one image is required.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $msg,
                        'errors' => ['images' => [$msg]],
                    ], 422);
                }

                return back()->withErrors(['images' => $msg])->withInput();
            }

            $variantId = $request->input('variant_id') ? (int) $request->input('variant_id') : null;

            $description = $request->input('description');
            if (empty($description)) {
                $description = $this->generateDescription($request, $variantId);
            }
            $description = trim((string) $description);
            $contactBlock = 'Seller contact: ' . $request->input('seller_phone')
                . ', ' . $request->input('seller_address')
                . ', ' . $request->input('seller_postcode');
            $description = $description === ''
                ? $contactBlock
                : $description . "\n\n" . $contactBlock;

            $vehicle = $this->createVehicleRecord($request, $user, $description);

            try {
                $this->auditLogService->logCreate(
                    $user,
                    'Vehicle',
                    $vehicle->id,
                    $vehicle->toArray(),
                    $request,
                    null,
                    null,
                    'Vehicle created via Sell Your Car web form',
                    ['vehicle', 'listing', 'sell-your-car', 'web']
                );
            } catch (\Exception $e) {
                Log::warning('Failed to create audit log for vehicle creation', [
                    'vehicle_id' => $vehicle->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $token = $this->generateSuccessToken($vehicle->id, $user->id);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => __('messages.messages.vehicle_listed_successfully'),
                    'vehicle_id' => $vehicle->id,
                    'token' => $token,
                    'redirect_url' => route('sell-your-car.success', ['token' => $token]),
                ]);
            }

            return redirect()->route('sell-your-car.success', ['token' => $token])
                ->with('success', __('messages.messages.vehicle_listed_successfully'));
        } catch (\Exception $e) {
            // Handle AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('messages.errors.failed_to_create_vehicle') . ': ' . $e->getMessage(),
                    'errors' => ['error' => [$e->getMessage()]]
                ], 500);
            }
            
            return back()
                ->withErrors(['error' => __('messages.errors.failed_to_create_vehicle') . ': ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function mergeVehicleSpecAttributesFromRequest(Request $request, array &$attributes): void
    {
        if ($request->filled('km_per_liter')) {
            $attributes['km_per_liter'] = (float) $request->input('km_per_liter');
        }
        if ($request->filled('maximum_weight_kg')) {
            $attributes['maximum_weight_kg'] = (int) $request->input('maximum_weight_kg');
        }
        if ($request->filled('colour_id')) {
            $attributes['colour_id'] = (int) $request->input('colour_id');
        }
        if ($request->filled('emission_norm_id')) {
            $attributes['emission_norm_id'] = (int) $request->input('emission_norm_id');
        }
        if ($request->filled('body_type_id')) {
            $attributes['body_type_id'] = (int) $request->input('body_type_id');
        }
        if ($request->filled('first_registration_year')) {
            $attributes['first_registration_year'] = (int) $request->input('first_registration_year');
        }
        if ($request->filled('first_registration_month') && $request->filled('first_registration_year')) {
            $y = (int) $request->input('first_registration_year');
            $m = (int) $request->input('first_registration_month');
            try {
                $attributes['first_registration_date'] = Carbon::create($y, $m, 1)->format('Y-m-d');
            } catch (\Throwable) {
            }
        }

        if ($request->filled('last_inspection_date')) {
            try {
                $attributes['last_inspection_date'] = Carbon::parse($request->input('last_inspection_date'))->format('Y-m-d');
            } catch (\Throwable) {
            }
        } elseif ($request->filled('last_inspection_month') && $request->filled('last_inspection_year')) {
            $y = (int) $request->input('last_inspection_year');
            $m = (int) $request->input('last_inspection_month');
            try {
                $attributes['last_inspection_date'] = Carbon::create($y, $m, 1)->format('Y-m-d');
            } catch (\Throwable) {
            }
        }
    }

    private function createVehicleRecord(Request $request, $user, string $description): Vehicle
    {
        return DB::transaction(function () use ($request, $user, $description) {
            $title = $request->input('title');
            $title = is_string($title) ? trim($title) : '';
            $title = $title !== '' ? $title : null;

            $rawDmr = $request->input('dmr_fact_vehicle_id');
            $dmrFactVehicleId = ($rawDmr !== null && $rawDmr !== '') ? (int) $rawDmr : null;

            $regRaw = $request->input('registration');
            $reg = is_string($regRaw) ? trim($regRaw) : '';
            $registration = ($reg === '' || strtoupper($reg) === 'N/A') ? null : $reg;

            $attributes = [
                'dmr_fact_vehicle_id' => $dmrFactVehicleId,
                'user_id' => $user->id,
                'dealer_id' => $user->dealer?->id,
                'title' => $title,
                'registration' => $registration,
                'price' => (int) $request->input('price'),
                'list_status_id' => VehicleListStatusConstant::PUBLISHED,
                'published_at' => now(),
                'description' => $description,
                'gear_type_id' => (int) $request->input('gear_type_id'),
                'km_driven' => (int) $request->input('km_driven'),
                'charging_type' => $request->input('charging_type') ?: null,
                'condition_id' => $request->filled('condition_id') ? (int) $request->input('condition_id') : null,
                'servicebog' => $request->input('servicebog') ?: null,
                'address' => trim((string) $request->input('seller_address', '')),
                'postcode' => trim((string) $request->input('seller_postcode', '')),
            ];

            if ($dmrFactVehicleId === null) {
                $attributes['brand_id'] = (int) $request->input('brand_id');
                $attributes['model_id'] = (int) $request->input('model_id');
                $attributes['model_year'] = (int) $request->input('model_year');
                $attributes['fuel_type_id'] = (int) $request->input('fuel_type_id');
                if ($request->filled('variant_id')) {
                    $attributes['variant_id'] = (int) $request->input('variant_id');
                }
            } elseif ($request->filled('variant_id')) {
                $attributes['variant_id'] = (int) $request->input('variant_id');
            }

            $this->mergeVehicleSpecAttributesFromRequest($request, $attributes);

            $vehicle = Vehicle::create($attributes);

            $checkboxIds = [];
            if ($request->filled('equipment_ids') && is_array($request->input('equipment_ids'))) {
                $checkboxIds = array_values(array_filter(array_map('intval', $request->input('equipment_ids'))));
            }
            $lookupCsv = $request->input('lookup_equipments');
            $lookupCsv = is_string($lookupCsv) ? trim($lookupCsv) : '';
            $lookupIds = $this->dmrLookupAssociationService->resolveEquipmentIdsFromLookupString($lookupCsv !== '' ? $lookupCsv : null);
            if ($lookupCsv !== '') {
                Cache::forget('constants_equipments');
            }
            $allEquipmentIds = array_values(array_unique(array_merge($checkboxIds, $lookupIds)));
            if ($allEquipmentIds !== []) {
                $vehicle->equipment()->sync($allEquipmentIds);
            }

            $lookupSpecsRaw = $request->input('lookup_specifications');
            $lookupSpecsRaw = is_string($lookupSpecsRaw) ? trim($lookupSpecsRaw) : '';
            $specSync = $this->dmrLookupAssociationService->resolveSpecificationSyncFromLookupJson($lookupSpecsRaw !== '' ? $lookupSpecsRaw : null);
            if ($specSync !== []) {
                $vehicle->specifications()->sync($specSync);
            }

            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $files = is_array($images) ? $images : [$images];
                $sortOrder = 0;
                foreach ($files as $file) {
                    if (!$file || !$file->isValid()) {
                        continue;
                    }
                    $this->fileService->validateFile($file);
                    $uploadedUrl = $this->fileService->uploadFiles(
                        [$file],
                        'public',
                        'vehicles',
                        true,
                        false,
                        300,
                        300
                    )[0];
                    $imagePath = str_replace('/storage/', '', parse_url($uploadedUrl, PHP_URL_PATH) ?? '');
                    $thumbnailPath = null;
                    try {
                        $thumbnailUrl = $this->fileService->createThumbnail($uploadedUrl, 300, 300, 'public');
                        $thumbnailPath = str_replace('/storage/', '', parse_url($thumbnailUrl, PHP_URL_PATH) ?? '');
                    } catch (\Exception $e) {
                        Log::debug('Thumbnail generation failed for vehicle image', ['e' => $e->getMessage()]);
                    }
                    VehicleImage::create([
                        'vehicle_id' => $vehicle->id,
                        'image_path' => $imagePath,
                        'thumbnail_path' => $thumbnailPath,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            return $vehicle->fresh(['images', 'equipment', 'specifications', 'dmrFactVehicle']);
        });
    }

    /**
     * Generate title from brand, model, model year, and fuel type
     */
    private function generateTitle(?int $brandId, ?int $modelId, ?int $modelYearId, ?int $fuelTypeId): ?string
    {
        $parts = [];
        
        if ($brandId) {
            $brand = Brand::find($brandId);
            if ($brand) {
                $parts[] = $brand->name;
            }
        }
        
        if ($modelId) {
            $model = VehicleModel::find($modelId);
            if ($model) {
                $parts[] = $model->name;
            }
        }
        
        if ($modelYearId) {
            $parts[] = (string) $modelYearId;
        }
        
        if ($fuelTypeId) {
            $fuelType = DmrDriveEnergy::find($fuelTypeId);
            if ($fuelType) {
                $parts[] = $fuelType->name;
            }
        }
        
        return !empty($parts) ? implode(' ', $parts) : null;
    }

    /**
     * Generate description from various fields
     */
    private function generateDescription(Request $request, ?int $variantId): string
    {
        $descriptionParts = [];
        
        // Equipment
        if ($request->has('equipment_ids') && is_array($request->input('equipment_ids'))) {
            $equipmentIds = $request->input('equipment_ids');
            if (!empty($equipmentIds)) {
                $equipments = Equipment::whereIn('id', $equipmentIds)->pluck('name')->toArray();
                if (!empty($equipments)) {
                    $descriptionParts[] = 'Equipment: ' . implode(', ', $equipments);
                }
            }
        }
        
        // Servicebog
        if ($request->has('servicebog') && $request->input('servicebog')) {
            $servicebog = $request->input('servicebog');
            if ($servicebog !== 'Default') {
                $descriptionParts[] = 'Service book: ' . $servicebog;
            }
        }
        
        // Kilometer Driven
        if ($request->has('km_driven') && $request->input('km_driven')) {
            $descriptionParts[] = 'Kilometers driven: ' . number_format($request->input('km_driven'), 0, ',', '.') . ' km';
        }
        
        // First Registration
        if ($request->has('first_registration_month') && $request->has('first_registration_year')) {
            $month = $request->input('first_registration_month');
            $year = $request->input('first_registration_year');
            $monthName = date('F', mktime(0, 0, 0, $month, 1));
            $descriptionParts[] = 'First registration: ' . $monthName . ' ' . $year;
        } elseif ($request->has('first_registration_date')) {
            $date = \Carbon\Carbon::parse($request->input('first_registration_date'));
            $descriptionParts[] = 'First registration: ' . $date->format('F Y');
        }
        
        // Last Inspection
        if ($request->has('last_inspection_month') && $request->has('last_inspection_year')) {
            $month = $request->input('last_inspection_month');
            $year = $request->input('last_inspection_year');
            $monthName = date('F', mktime(0, 0, 0, $month, 1));
            $descriptionParts[] = 'Last inspection: ' . $monthName . ' ' . $year;
        } elseif ($request->has('last_inspection_date')) {
            $date = \Carbon\Carbon::parse($request->input('last_inspection_date'));
            $descriptionParts[] = 'Last inspection: ' . $date->format('F Y');
        }
        
        // KM/L (Fuel Efficiency)
        if ($request->filled('km_per_liter')) {
            $descriptionParts[] = 'Fuel efficiency: ' . number_format((float) $request->input('km_per_liter'), 2) . ' km/l';
        }
        
        // Euro norm (DMR emission norm or legacy Euronom)
        $emissionNormId = $request->input('emission_norm_id');
        if ($emissionNormId) {
            $dmrNorm = DmrEmissionNorm::find($emissionNormId);
            if ($dmrNorm) {
                $descriptionParts[] = 'Euro norm: ' . $dmrNorm->name;
            } else {
                $euronom = Euronom::find($emissionNormId);
                if ($euronom) {
                    $descriptionParts[] = 'Euro norm: ' . $euronom->name;
                }
            }
        }
        
        // Total Technical Weight
        if ($request->filled('maximum_weight_kg')) {
            $descriptionParts[] = 'Total technical weight: ' . number_format((int) $request->input('maximum_weight_kg'), 0, ',', '.') . ' kg';
        }
        
        if ($variantId) {
            $dmrVar = DmrVariant::find($variantId);
            if ($dmrVar) {
                $descriptionParts[] = 'Variant: ' . $dmrVar->name;
            } else {
                $v = Variant::find($variantId);
                if ($v) {
                    $descriptionParts[] = 'Variant: ' . $v->name;
                }
            }
        }

        if (empty($descriptionParts)) {
            return '';
        }

        return implode('. ', $descriptionParts) . '.';
    }

    /**
     * Generate secure token for success page access
     */
    private function generateSuccessToken(int $vehicleId, int $userId): string
    {
        $token = Str::random(32);

        Cache::put("vehicle_success_token:{$token}", [
            'vehicle_id' => $vehicleId,
            'user_id' => $userId,
        ], now()->addHour());

        return $token;
    }

    /**
     * Verify token and retrieve vehicle data
     */
    private function verifySuccessToken(string $token, int $expectedUserId): ?array
    {
        $tokenData = Cache::get("vehicle_success_token:{$token}");
        
        if (!$tokenData) {
            return null; // Token doesn't exist or expired
        }
        
        // Verify user_id matches
        if ($tokenData['user_id'] !== $expectedUserId) {
            return null; // Token doesn't belong to this user
        }
        
        return $tokenData;
    }

    /**
     * Show success page after vehicle creation
     */
    public function showSuccess(Request $request, string $token)
    {
        $user = $this->authService->getAuthenticatedUser($request);
        
        if (!$user) {
            return redirect()->route('login')->with('return_url', '/sell-your-car');
        }

        $tokenData = $this->verifySuccessToken($token, $user->id);

        if (!$tokenData) {
            return redirect()->route('sell-your-car')
                ->with('error', __('messages.errors.invalid_access_token_listing'));
        }

        $vehicleId = $tokenData['vehicle_id'] ?? $tokenData['vehicle_listing_id'] ?? null;

        if (!$vehicleId) {
            return redirect()->route('sell-your-car')
                ->with('error', __('messages.errors.invalid_access_token_listing'));
        }

        try {
            $vehicle = Vehicle::query()
                ->with([
                    'images',
                    'dmrFactVehicle.variant.model.brand',
                ])
                ->findOrFail($vehicleId);
        } catch (ModelNotFoundException $e) {
            return redirect()->route('sell-your-car')
                ->with('error', __('messages.errors.listing_no_longer_exists'));
        }

        if ($vehicle->user_id !== $user->id) {
            return redirect()->route('sell-your-car')
                ->with('error', __('messages.errors.no_permission_listing'));
        }

        $isFeatured = FeaturedListing::where('vehicle_id', $vehicleId)->exists();

        $canFeature = $user->can('vehicle.seller.feature');

        return view('sell-your-car-success', [
            'listing' => $vehicle,
            'vehicle' => $vehicle,
            'isFeatured' => $isFeatured,
            'canFeature' => $canFeature,
            'token' => $token,
        ]);
    }

    /**
     * Feature a vehicle listing
     */
    public function feature(Request $request, string $token)
    {
        $user = $this->authService->getAuthenticatedUser($request);
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.must_be_logged_in')
            ], 401);
        }

        // Check permission to feature vehicles
        if (!$user->can('vehicle.feature')) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.permission_denied')
            ], 403);
        }

        // Verify token and get vehicle_id
        $tokenData = $this->verifySuccessToken($token, $user->id);
        
        if (!$tokenData) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.invalid_access_token')
            ], 403);
        }

        $vehicleId = $tokenData['vehicle_id'] ?? $tokenData['vehicle_listing_id'] ?? null;

        if (!$vehicleId) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.invalid_access_token'),
            ], 403);
        }

        try {
            $vehicle = Vehicle::findOrFail($vehicleId);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.vehicle_not_found'),
            ], 404);
        }

        if ($vehicle->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.permission_denied'),
            ], 403);
        }

        $existingFeatured = FeaturedListing::where('vehicle_id', $vehicleId)->first();
        if ($existingFeatured) {
            return response()->json([
                'status' => 'success',
                'message' => __('messages.errors.already_featured'),
                'already_featured' => true,
            ]);
        }

        $maxSortOrder = FeaturedListing::max('sort_order') ?? 0;
        $sortOrder = $maxSortOrder + 1;

        $featuredListing = FeaturedListing::create([
            'vehicle_id' => $vehicleId,
            'sort_order' => $sortOrder,
        ]);

        // Handle AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('messages.messages.vehicle_featured_successfully'),
                'featured_listing' => $featuredListing
            ]);
        }

        return redirect()->route('sell-your-car.success', ['token' => $token])
            ->with('success', __('messages.messages.vehicle_featured_successfully'));
    }
}

