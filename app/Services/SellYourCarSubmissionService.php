<?php

namespace App\Services;

use App\Constants\VehicleListStatus as VehicleListStatusConstant;
use App\Models\DmrColour;
use App\Models\DmrEmissionNorm;
use App\Models\GearType;
use App\Models\Condition;
use App\Models\Location;
use App\Models\DmrVariant;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Euronom;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Models\Variant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sell-your-car listing creation — shared by web {@see \App\Http\Controllers\SellYourCarController::store}
 * and API {@see \App\Http\Controllers\VehicleController::sellYourCar}.
 */
class SellYourCarSubmissionService
{
    public function __construct(
        private FileService $fileService,
        private DmrLookupAssociationService $dmrLookupAssociationService,
    ) {}

    /**
     * Initial form options (same structure as sell-your-car Blade show), JSON-safe arrays.
     *
     * @return array<string, mixed>
     */
    public function buildInitialFormPayload(User $user): array
    {
        $user->load('dealer');

        $empty = [];
        $equipmentLookup = $this->equipmentLookupData();

        $lookup = [
            'models' => $empty,
            'brands' => $empty,
            'dmr_brands' => $empty,
            'dmr_models' => $empty,
            'dmr_variants' => $empty,
            'dmr_colours' => $this->mapIdName(DmrColour::query()->orderBy('name')->get()),
            'dmr_euronorms' => $this->mapIdName(DmrEmissionNorm::query()->orderBy('name')->get()),
            'dmr_drive_energies' => $empty,
            'variants' => $empty,
            'model_years' => $empty,
            'gear_types' => $this->mapIdName(GearType::orderBy('name')->get()),
            'conditions' => $this->mapIdName(Condition::orderBy('name')->get()),
            'locations' => Location::select('city', 'postcode', 'region')->orderBy('city')->get()
                ->map(fn ($loc) => ['city' => $loc->city, 'postcode' => $loc->postcode, 'region' => $loc->region])
                ->values()
                ->all(),
            'equipment_types' => $equipmentLookup['equipmentTypes']->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'equipments' => $type->equipments->map(fn ($eq) => ['id' => $eq->id, 'name' => $eq->name])->values()->all(),
                ];
            })->values()->all(),
            'equipment_without_type' => $equipmentLookup['equipment']->filter(fn ($e) => ! $e->equipment_type_id)
                ->map(fn ($eq) => ['id' => $eq->id, 'name' => $eq->name])->values()->all(),
        ];

        return [
            'user' => [
                'id' => $user->id,
                'phone' => $user->phone,
                'address' => $user->address,
                'postcode' => $user->postcode,
            ],
            'lookup' => $lookup,
            'lookup_context_url_template' => url('/sell-your-car/lookup-context').'/{dmrFactVehicleId}',
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object{id: int, name: string}>  $rows
     * @return array<int, array{id: int, name: string}>
     */
    private function mapIdName($rows): array
    {
        return $rows->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->values()->all();
    }

    /**
     * Equipment types + items (same as SellYourCarController::sellYourCarEquipmentLookupData).
     */
    public function equipmentLookupData(): array
    {
        return [
            'equipmentTypes' => EquipmentType::with(['equipments' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get(),
            'equipment' => Equipment::with('equipmentType')->orderBy('name')->get(),
        ];
    }

    /**
     * Validate, require images, create vehicle, success token.
     *
     * @return array{vehicle: Vehicle, token: string}
     *
     * @throws ValidationException
     */
    public function submit(Request $request, User $user): array
    {
        $rawDmr = $request->input('dmr_fact_vehicle_id');
        $dmrFactVehicleId = ($rawDmr !== null && $rawDmr !== '') ? (int) $rawDmr : null;

        $validationRules = [
            'title' => 'nullable|string|max:255',
            'registration' => $dmrFactVehicleId !== null
                ? ['required', 'string', 'max:20']
                : ['nullable', 'string', 'max:20'],
            'price' => 'required|numeric|min:0',
            'km_driven' => 'required|numeric|min:0',
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
            throw new ValidationException($validator);
        }

        if (! $request->hasFile('images')) {
            $msg = __('messages.errors.failed_to_create_vehicle').': '.__('messages.api.at_least_one_image_required');

            throw ValidationException::withMessages([
                'images' => [$msg],
            ]);
        }

        $variantId = $request->input('variant_id') ? (int) $request->input('variant_id') : null;

        $description = $request->input('description');
        if (empty($description)) {
            $description = $this->generateDescription($request, $variantId);
        }
        $description = trim((string) $description);

        $vehicle = $this->createVehicleRecord($request, $user, $description);
        $token = $this->generateSuccessToken($vehicle->id, $user->id);

        return [
            'vehicle' => $vehicle,
            'token' => $token,
        ];
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

    private function createVehicleRecord(Request $request, User $user, string $description): Vehicle
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
                'price' => (float) $request->input('price'),
                'list_status_id' => VehicleListStatusConstant::PUBLISHED,
                'published_at' => now(),
                'description' => $description,
                'gear_type_id' => (int) $request->input('gear_type_id'),
                'km_driven' => (float) $request->input('km_driven'),
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
                LookupService::forgetLookupCacheGroup('equipments');
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
                    if (! $file || ! $file->isValid()) {
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

    private function generateDescription(Request $request, ?int $variantId): string
    {
        $descriptionParts = [];

        if ($request->has('equipment_ids') && is_array($request->input('equipment_ids'))) {
            $equipmentIds = $request->input('equipment_ids');
            if (! empty($equipmentIds)) {
                $equipments = Equipment::whereIn('id', $equipmentIds)->pluck('name')->toArray();
                if (! empty($equipments)) {
                    $descriptionParts[] = 'Equipment: '.implode(', ', $equipments);
                }
            }
        }

        if ($request->has('servicebog') && $request->input('servicebog')) {
            $servicebog = $request->input('servicebog');
            if ($servicebog !== 'Default') {
                $descriptionParts[] = 'Service book: '.$servicebog;
            }
        }

        if ($request->has('km_driven') && $request->input('km_driven')) {
            $descriptionParts[] = 'Kilometers driven: '.number_format($request->input('km_driven'), 0, ',', '.').' km';
        }

        if ($request->has('first_registration_month') && $request->has('first_registration_year')) {
            $month = $request->input('first_registration_month');
            $year = $request->input('first_registration_year');
            $monthName = date('F', mktime(0, 0, 0, $month, 1));
            $descriptionParts[] = 'First registration: '.$monthName.' '.$year;
        } elseif ($request->has('first_registration_date')) {
            $date = Carbon::parse($request->input('first_registration_date'));
            $descriptionParts[] = 'First registration: '.$date->format('F Y');
        }

        if ($request->has('last_inspection_month') && $request->has('last_inspection_year')) {
            $month = $request->input('last_inspection_month');
            $year = $request->input('last_inspection_year');
            $monthName = date('F', mktime(0, 0, 0, $month, 1));
            $descriptionParts[] = 'Last inspection: '.$monthName.' '.$year;
        } elseif ($request->has('last_inspection_date')) {
            $date = Carbon::parse($request->input('last_inspection_date'));
            $descriptionParts[] = 'Last inspection: '.$date->format('F Y');
        }

        if ($request->filled('km_per_liter')) {
            $descriptionParts[] = 'Fuel efficiency: '.number_format((float) $request->input('km_per_liter'), 2).' km/l';
        }

        $emissionNormId = $request->input('emission_norm_id');
        if ($emissionNormId) {
            $dmrNorm = DmrEmissionNorm::find($emissionNormId);
            if ($dmrNorm) {
                $descriptionParts[] = 'Euro norm: '.$dmrNorm->name;
            } else {
                $euronom = Euronom::find($emissionNormId);
                if ($euronom) {
                    $descriptionParts[] = 'Euro norm: '.$euronom->name;
                }
            }
        }

        if ($request->filled('maximum_weight_kg')) {
            $descriptionParts[] = 'Total technical weight: '.number_format((int) $request->input('maximum_weight_kg'), 0, ',', '.').' kg';
        }

        if ($variantId) {
            $dmrVar = DmrVariant::find($variantId);
            if ($dmrVar) {
                $descriptionParts[] = 'Variant: '.$dmrVar->name;
            } else {
                $v = Variant::find($variantId);
                if ($v) {
                    $descriptionParts[] = 'Variant: '.$v->name;
                }
            }
        }

        if (empty($descriptionParts)) {
            return '';
        }

        return implode('. ', $descriptionParts).'.';
    }

    private function generateSuccessToken(int $vehicleId, int $userId): string
    {
        $token = Str::random(32);

        Cache::put("vehicle_success_token:{$token}", [
            'vehicle_id' => $vehicleId,
            'user_id' => $userId,
        ], now()->addHour());

        return $token;
    }
}
