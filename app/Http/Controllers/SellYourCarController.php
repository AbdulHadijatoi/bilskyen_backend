<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\DmrDriveEnergy;
use App\Constants\VehicleListStatus as VehicleListStatusConstant;
use App\Models\GearType;
use App\Models\Condition;
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
use App\Services\SellYourCarSubmissionService;
use App\Services\VehicleTrustReportService;
use App\Constants\ApiStatusCode;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SellYourCarController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private AuditLogService $auditLogService,
        private FileService $fileService,
        private DmrLookupAssociationService $dmrLookupAssociationService,
        private SellYourCarSubmissionService $sellYourCarSubmissionService,
        private VehicleTrustReportService $trustReportService,
    ) {}

    /**
     * Initial form data for Flutter (same lookups as sell-your-car Blade show).
     * GET /api/v1/sell-your-car/form
     */
    public function apiFormData(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return $this->unauthorized();
        }

        $user->load('dealer');
        $data = $this->sellYourCarSubmissionService->buildInitialFormPayload($user);

        return $this->success($data, ApiStatusCode::OK, __('messages.api.data_retrieved_successfully'));
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

        $equipmentLookup = $this->sellYourCarSubmissionService->equipmentLookupData();

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
            return response()->json(['message' => __('messages.api.unauthorized')], 401);
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
            return response()->json(['message' => __('messages.errors.vehicle_not_found')], 404);
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
            'lookupData' => $this->sellYourCarSubmissionService->equipmentLookupData(),
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
        } catch (ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('messages.api.validation_failed'),
                    'errors' => $e->errors(),
                ], 422);
            }

            return back()
                ->withErrors($e->errors())
                ->withInput();
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

        $isPendingReview = (int) $vehicle->list_status_id === VehicleListStatusConstant::PENDING_REVIEW;

        return view('sell-your-car-success', [
            'listing' => $vehicle,
            'vehicle' => $vehicle,
            'trustReport' => $this->trustReportService->buildForVehicle($vehicle),
            'isFeatured' => $isFeatured,
            'canFeature' => $canFeature,
            'isPendingReview' => $isPendingReview,
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

