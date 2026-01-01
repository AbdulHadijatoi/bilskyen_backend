<?php

namespace App\Services;

use App\Exceptions\NummerpladeApiException;
use App\Models\Brand;
use App\Models\Category;
use App\Models\FuelType;
use App\Models\ModelYear;
use App\Models\VehicleModel;
use App\Models\BodyType;
use App\Models\Color;
use App\Models\Condition;
use App\Models\GearType;
use App\Models\ListingType;
use App\Models\PriceType;
use App\Models\SalesType;
use App\Models\Type;
use App\Models\VehicleUse;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Permit;
use App\Models\Transmission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Nummerplade API Service
 * Reusable service for Nummerplade API integration
 * Handles errors, rate limiting, and caching
 */
class NummerpladeApiService
{
    protected string $baseUrl;
    protected ?string $apiToken;
    protected int $timeout;
    protected int $cacheTtl;
    protected int $referenceCacheTtl;

    public function __construct()
    {
        $this->baseUrl = config('nummerplade.base_url');
        $this->apiToken = config('nummerplade.api_token');
        $this->timeout = config('nummerplade.timeout', 60); // Increased default to 60 seconds
        $this->cacheTtl = config('nummerplade.cache.ttl', 86400);
        $this->referenceCacheTtl = config('nummerplade.cache.reference_data_ttl', 86400);
    }

    /**
     * Get vehicle by registration (license plate)
     */
    public function getVehicleByRegistration(string $registration, bool $advanced = false): array
    {
        $cacheKey = "nummerplade:vehicle:registration:{$registration}:advanced:" . ($advanced ? '1' : '0');

        $data = Cache::remember($cacheKey, $this->cacheTtl, function () use ($registration, $advanced) {
            // Use longer timeout for vehicle lookups (they can be slow)
            $vehicleLookupTimeout = config('nummerplade.vehicle_lookup_timeout', 60);
            
            try {
                $url = "{$this->baseUrl}/{$registration}";
                
                $response = Http::timeout($vehicleLookupTimeout)
                    ->connectTimeout(15) // Connection timeout separate from request timeout (increased to 15s)
                    ->withHeaders($this->getHeaders())
                    ->when($advanced, fn($request) => $request->withQueryParameters(['advanced' => '1']))
                    ->get($url);

                return $this->handleResponse($response, 'getVehicleByRegistration');
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::warning('Nummerplade API connection timeout', [
                    'method' => 'getVehicleByRegistration',
                    'registration' => $registration,
                    'timeout' => $vehicleLookupTimeout,
                    'error' => $e->getMessage(),
                ]);
                throw NummerpladeApiException::timeout($e->getMessage());
            } catch (\Exception $e) {
                Log::error('Nummerplade API error', [
                    'method' => 'getVehicleByRegistration',
                    'registration' => $registration,
                    'error' => $e->getMessage(),
                ]);
                throw NummerpladeApiException::unknown($e->getMessage());
            }
        });

        // Process the data to replace lookup values with IDs
        return $this->processLookupData($data);
    }

    /**
     * Get vehicle by VIN
     */
    public function getVehicleByVin(string $vin, bool $advanced = false): array
    {
        $cacheKey = "nummerplade:vehicle:vin:{$vin}:advanced:" . ($advanced ? '1' : '0');

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($vin, $advanced) {
            // Use longer timeout for vehicle lookups (they can be slow)
            $vehicleLookupTimeout = config('nummerplade.vehicle_lookup_timeout', 60);
            
            try {
                $url = "{$this->baseUrl}/vin/{$vin}";
                
                $response = Http::timeout($vehicleLookupTimeout)
                    ->connectTimeout(15) // Connection timeout separate from request timeout (increased to 15s)
                    ->withHeaders($this->getHeaders())
                    ->when($advanced, fn($request) => $request->withQueryParameters(['advanced' => '1']))
                    ->get($url);

                return $this->handleResponse($response, 'getVehicleByVin');
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::warning('Nummerplade API connection timeout', [
                    'method' => 'getVehicleByVin',
                    'vin' => $vin,
                    'timeout' => $vehicleLookupTimeout,
                    'error' => $e->getMessage(),
                ]);
                throw NummerpladeApiException::timeout($e->getMessage());
            } catch (\Exception $e) {
                Log::error('Nummerplade API error', [
                    'method' => 'getVehicleByVin',
                    'vin' => $vin,
                    'error' => $e->getMessage(),
                ]);
                throw NummerpladeApiException::unknown($e->getMessage());
            }
        });
    }

    /**
     * Get body types reference data
     */
    public function getBodyTypes(): array
    {
        $cacheKey = 'nummerplade:reference:body-types';

        return Cache::remember($cacheKey, $this->referenceCacheTtl, function () {
            try {
                $url = "{$this->baseUrl}/body-types";
                
                $response = Http::timeout($this->timeout)
                    ->withHeaders($this->getHeaders())
                    ->get($url);

                return $this->handleResponse($response, 'getBodyTypes');
            } catch (\Exception $e) {
                Log::error('Nummerplade API error', [
                    'method' => 'getBodyTypes',
                    'error' => $e->getMessage(),
                ]);
                throw NummerpladeApiException::unknown($e->getMessage());
            }
        });
    }

    /**
     * Get colors reference data
     */
    public function getColors(): array
    {
        $cacheKey = 'nummerplade:reference:colors';

        return Cache::remember($cacheKey, $this->referenceCacheTtl, function () {
            try {
                $url = "{$this->baseUrl}/colors";
                
                $response = Http::timeout($this->timeout)
                    ->withHeaders($this->getHeaders())
                    ->get($url);

                return $this->handleResponse($response, 'getColors');
            } catch (\Exception $e) {
                Log::error('Nummerplade API error', [
                    'method' => 'getColors',
                    'error' => $e->getMessage(),
                ]);
                throw NummerpladeApiException::unknown($e->getMessage());
            }
        });
    }

    /**
     * Get fuel types reference data
     */
    public function getFuelTypes(): array
    {
        $cacheKey = 'nummerplade:reference:fuel-types';

        return Cache::remember($cacheKey, $this->referenceCacheTtl, function () {
            try {
                $url = "{$this->baseUrl}/fuel-types";
                
                $response = Http::timeout($this->timeout)
                    ->withHeaders($this->getHeaders())
                    ->get($url);

                return $this->handleResponse($response, 'getFuelTypes');
            } catch (\Exception $e) {
                Log::error('Nummerplade API error', [
                    'method' => 'getFuelTypes',
                    'error' => $e->getMessage(),
                ]);
                throw NummerpladeApiException::unknown($e->getMessage());
            }
        });
    }

    /**
     * Get equipment reference data
     */
    public function getEquipment(): array
    {
        $cacheKey = 'nummerplade:reference:equipment';

        return Cache::remember($cacheKey, $this->referenceCacheTtl, function () {
            try {
                $url = "{$this->baseUrl}/equipment";
                
                $response = Http::timeout($this->timeout)
                    ->withHeaders($this->getHeaders())
                    ->get($url);

                return $this->handleResponse($response, 'getEquipment');
            } catch (\Exception $e) {
                Log::error('Nummerplade API error', [
                    'method' => 'getEquipment',
                    'error' => $e->getMessage(),
                ]);
                throw NummerpladeApiException::unknown($e->getMessage());
            }
        });
    }

    /**
     * Get permits reference data
     */
    public function getPermits(): array
    {
        $cacheKey = 'nummerplade:reference:permits';

        return Cache::remember($cacheKey, $this->referenceCacheTtl, function () {
            try {
                $url = "{$this->baseUrl}/permits";
                
                $response = Http::timeout($this->timeout)
                    ->withHeaders($this->getHeaders())
                    ->get($url);

                return $this->handleResponse($response, 'getPermits');
            } catch (\Exception $e) {
                Log::error('Nummerplade API error', [
                    'method' => 'getPermits',
                    'error' => $e->getMessage(),
                ]);
                throw NummerpladeApiException::unknown($e->getMessage());
            }
        });
    }

    /**
     * Get types reference data
     */
    public function getTypes(): array
    {
        $cacheKey = 'nummerplade:reference:types';

        return Cache::remember($cacheKey, $this->referenceCacheTtl, function () {
            try {
                $url = "{$this->baseUrl}/types";
                
                $response = Http::timeout($this->timeout)
                    ->withHeaders($this->getHeaders())
                    ->get($url);

                return $this->handleResponse($response, 'getTypes');
            } catch (\Exception $e) {
                Log::error('Nummerplade API error', [
                    'method' => 'getTypes',
                    'error' => $e->getMessage(),
                ]);
                throw NummerpladeApiException::unknown($e->getMessage());
            }
        });
    }

    /**
     * Get vehicle uses reference data
     */
    public function getUses(): array
    {
        $cacheKey = 'nummerplade:reference:uses';

        return Cache::remember($cacheKey, $this->referenceCacheTtl, function () {
            try {
                $url = "{$this->baseUrl}/uses";
                
                $response = Http::timeout($this->timeout)
                    ->withHeaders($this->getHeaders())
                    ->get($url);

                return $this->handleResponse($response, 'getUses');
            } catch (\Exception $e) {
                Log::error('Nummerplade API error', [
                    'method' => 'getUses',
                    'error' => $e->getMessage(),
                ]);
                throw NummerpladeApiException::unknown($e->getMessage());
            }
        });
    }

    /**
     * Get vehicle inspections
     */
    public function getInspections(int $vehicleId): array
    {
        try {
            $url = "{$this->baseUrl}/inspections/{$vehicleId}";
            
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get($url);

            return $this->handleResponse($response, 'getInspections');
        } catch (\Exception $e) {
            Log::error('Nummerplade API error', [
                'method' => 'getInspections',
                'vehicle_id' => $vehicleId,
                'error' => $e->getMessage(),
            ]);
            throw NummerpladeApiException::unknown($e->getMessage());
        }
    }

    /**
     * Get DMR data
     */
    public function getDmrData(int $vehicleId): array
    {
        try {
            $url = "{$this->baseUrl}/dmr/{$vehicleId}";
            
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get($url);

            return $this->handleResponse($response, 'getDmrData');
        } catch (\Exception $e) {
            Log::error('Nummerplade API error', [
                'method' => 'getDmrData',
                'vehicle_id' => $vehicleId,
                'error' => $e->getMessage(),
            ]);
            throw NummerpladeApiException::unknown($e->getMessage());
        }
    }

    /**
     * Get debt/tinglysning data
     */
    public function getDebt(int $vehicleId): array
    {
        try {
            $url = "{$this->baseUrl}/debt/{$vehicleId}";
            
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get($url);

            return $this->handleResponse($response, 'getDebt');
        } catch (\Exception $e) {
            Log::error('Nummerplade API error', [
                'method' => 'getDebt',
                'vehicle_id' => $vehicleId,
                'error' => $e->getMessage(),
            ]);
            throw NummerpladeApiException::unknown($e->getMessage());
        }
    }

    /**
     * Get detailed tinglysning data
     */
    public function getTinglysning(string $vin): array
    {
        try {
            $url = "{$this->baseUrl}/tinglysning/{$vin}";
            
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get($url);

            return $this->handleResponse($response, 'getTinglysning');
        } catch (\Exception $e) {
            Log::error('Nummerplade API error', [
                'method' => 'getTinglysning',
                'vin' => $vin,
                'error' => $e->getMessage(),
            ]);
            throw NummerpladeApiException::unknown($e->getMessage());
        }
    }

    /**
     * Get emissions data (registration or VIN)
     */
    public function getEmissions(string $input): array
    {
        try {
            $url = "{$this->baseUrl}/emissions/{$input}";
            
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get($url);

            return $this->handleResponse($response, 'getEmissions');
        } catch (\Exception $e) {
            Log::error('Nummerplade API error', [
                'method' => 'getEmissions',
                'input' => $input,
                'error' => $e->getMessage(),
            ]);
            throw NummerpladeApiException::unknown($e->getMessage());
        }
    }

    /**
     * Get evaluations/DMR data
     */
    public function getEvaluations(string $input): array
    {
        try {
            $url = "{$this->baseUrl}/evaluations/{$input}";
            
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get($url);

            return $this->handleResponse($response, 'getEvaluations');
        } catch (\Exception $e) {
            Log::error('Nummerplade API error', [
                'method' => 'getEvaluations',
                'input' => $input,
                'error' => $e->getMessage(),
            ]);
            throw NummerpladeApiException::unknown($e->getMessage());
        }
    }

    /**
     * Process lookup data - replace names with IDs from database
     * Creates records if they don't exist
     */
    protected function processLookupData(array $data): array
    {
        // Process nested arrays recursively
        foreach ($data as $key => $value) {
            if (is_array($value) && !$this->isNumericArray($value)) {
                // Recursively process nested associative arrays
                $data[$key] = $this->processLookupData($value);
            }
        }

        // Process brand first (model depends on it)
        $brandId = null;
        if (isset($data['brand'])) {
            if (is_string($data['brand'])) {
                $brandId = $this->getOrCreateBrand($data['brand']);
                $data['brand'] = $brandId;
            } elseif (is_array($data['brand']) && isset($data['brand']['name'])) {
                $brandId = $this->getOrCreateBrand($data['brand']['name']);
                $data['brand'] = $brandId;
            }
        } elseif (isset($data['make'])) {
            if (is_string($data['make'])) {
                $brandId = $this->getOrCreateBrand($data['make']);
                $data['make'] = $brandId;
            } elseif (is_array($data['make']) && isset($data['make']['name'])) {
                $brandId = $this->getOrCreateBrand($data['make']['name']);
                $data['make'] = $brandId;
            }
        }

        // Process model (needs brand_id, so process brand first)
        if (isset($data['model'])) {
            $modelName = null;
            if (is_string($data['model'])) {
                $modelName = $data['model'];
            } elseif (is_array($data['model']) && isset($data['model']['name'])) {
                $modelName = $data['model']['name'];
            }

            if ($modelName) {
                // Use the brandId we just got, or check if it's already an ID
                if ($brandId) {
                    $modelId = $this->getOrCreateModel($modelName, $brandId);
                    $data['model'] = $modelId;
                } elseif (isset($data['brand']) && is_int($data['brand'])) {
                    // Brand was already converted to ID
                    $modelId = $this->getOrCreateModel($modelName, $data['brand']);
                    $data['model'] = $modelId;
                } elseif (isset($data['make']) && is_int($data['make'])) {
                    // Make was already converted to ID
                    $modelId = $this->getOrCreateModel($modelName, $data['make']);
                    $data['model'] = $modelId;
                }
            }
        }

        // Process model_year
        if (isset($data['model_year']) && is_string($data['model_year'])) {
            $yearId = $this->getOrCreateModelYear($data['model_year']);
            $data['model_year'] = $yearId;
        } elseif (isset($data['year']) && is_string($data['year'])) {
            $yearId = $this->getOrCreateModelYear($data['year']);
            $data['year'] = $yearId;
        }

        // Process category
        if (isset($data['category']) && is_string($data['category'])) {
            $categoryId = $this->getOrCreateCategory($data['category']);
            $data['category'] = $categoryId;
        } elseif (isset($data['vehicleType']) && is_string($data['vehicleType'])) {
            $categoryId = $this->getOrCreateCategory($data['vehicleType']);
            $data['vehicleType'] = $categoryId;
        }

        // Process fuel_type
        if (isset($data['fuel_type']) && is_string($data['fuel_type'])) {
            $fuelTypeId = $this->getOrCreateFuelType($data['fuel_type']);
            $data['fuel_type'] = $fuelTypeId;
        } elseif (isset($data['fuelType']) && is_string($data['fuelType'])) {
            $fuelTypeId = $this->getOrCreateFuelType($data['fuelType']);
            $data['fuelType'] = $fuelTypeId;
        }

        // Process body_type
        if (isset($data['body_type']) && is_string($data['body_type'])) {
            $bodyTypeId = $this->getOrCreateBodyType($data['body_type']);
            $data['body_type'] = $bodyTypeId;
        } elseif (isset($data['bodyType']) && is_string($data['bodyType'])) {
            $bodyTypeId = $this->getOrCreateBodyType($data['bodyType']);
            $data['bodyType'] = $bodyTypeId;
        }

        // Process color
        if (isset($data['color']) && is_string($data['color'])) {
            $colorId = $this->getOrCreateColor($data['color']);
            $data['color'] = $colorId;
        }

        // Process condition
        if (isset($data['condition']) && is_string($data['condition'])) {
            $conditionId = $this->getOrCreateCondition($data['condition']);
            $data['condition'] = $conditionId;
        }

        // Process gear_type
        if (isset($data['gear_type']) && is_string($data['gear_type'])) {
            $gearTypeId = $this->getOrCreateGearType($data['gear_type']);
            $data['gear_type'] = $gearTypeId;
        } elseif (isset($data['gearType']) && is_string($data['gearType'])) {
            $gearTypeId = $this->getOrCreateGearType($data['gearType']);
            $data['gearType'] = $gearTypeId;
        }

        // Process transmission
        if (isset($data['transmission']) && is_string($data['transmission'])) {
            $transmissionId = $this->getOrCreateTransmission($data['transmission']);
            $data['transmission'] = $transmissionId;
        }

        // Process type
        if (isset($data['type']) && is_string($data['type'])) {
            $typeId = $this->getOrCreateType($data['type']);
            $data['type'] = $typeId;
        }

        // Process use
        if (isset($data['use']) && is_string($data['use'])) {
            $useId = $this->getOrCreateUse($data['use']);
            $data['use'] = $useId;
        }

        // Process equipment (if it's an array)
        if (isset($data['equipment']) && is_array($data['equipment'])) {
            $equipmentIds = [];
            foreach ($data['equipment'] as $equipment) {
                if (is_string($equipment)) {
                    $equipmentId = $this->getOrCreateEquipment($equipment);
                    $equipmentIds[] = $equipmentId;
                } elseif (is_array($equipment) && isset($equipment['name'])) {
                    $equipmentId = $this->getOrCreateEquipment($equipment['name']);
                    $equipmentIds[] = $equipmentId;
                }
            }
            $data['equipment'] = $equipmentIds;
        }

        return $data;
    }

    /**
     * Get or create brand
     */
    protected function getOrCreateBrand(string $name): int
    {
        $brand = Brand::firstOrCreate(
            ['name' => trim($name)],
            ['name' => trim($name)]
        );
        return $brand->id;
    }

    /**
     * Get or create model
     */
    protected function getOrCreateModel(string $name, int $brandId): int
    {
        $model = VehicleModel::firstOrCreate(
            ['name' => trim($name), 'brand_id' => $brandId],
            ['name' => trim($name), 'brand_id' => $brandId]
        );
        return $model->id;
    }

    /**
     * Get or create model year
     */
    protected function getOrCreateModelYear(string $year): int
    {
        $yearStr = trim((string) $year);
        $modelYear = ModelYear::firstOrCreate(
            ['name' => $yearStr],
            ['name' => $yearStr]
        );
        return $modelYear->id;
    }

    /**
     * Get or create category
     */
    protected function getOrCreateCategory(string $name): int
    {
        $category = Category::firstOrCreate(
            ['name' => trim($name)],
            ['name' => trim($name)]
        );
        return $category->id;
    }

    /**
     * Get or create fuel type
     */
    protected function getOrCreateFuelType(string $name): int
    {
        $fuelType = FuelType::firstOrCreate(
            ['name' => trim($name)],
            ['name' => trim($name)]
        );
        return $fuelType->id;
    }

    /**
     * Get or create body type
     */
    protected function getOrCreateBodyType(string $name): int
    {
        $bodyType = BodyType::firstOrCreate(
            ['name' => trim($name)],
            ['name' => trim($name)]
        );
        return $bodyType->id;
    }

    /**
     * Get or create color
     */
    protected function getOrCreateColor(string $name): int
    {
        $color = Color::firstOrCreate(
            ['name' => trim($name)],
            ['name' => trim($name)]
        );
        return $color->id;
    }

    /**
     * Get or create condition
     */
    protected function getOrCreateCondition(string $name): int
    {
        $condition = Condition::firstOrCreate(
            ['name' => trim($name)],
            ['name' => trim($name)]
        );
        return $condition->id;
    }

    /**
     * Get or create gear type
     */
    protected function getOrCreateGearType(string $name): int
    {
        $gearType = GearType::firstOrCreate(
            ['name' => trim($name)],
            ['name' => trim($name)]
        );
        return $gearType->id;
    }

    /**
     * Get or create transmission
     */
    protected function getOrCreateTransmission(string $name): int
    {
        $transmission = Transmission::firstOrCreate(
            ['name' => trim($name)],
            ['name' => trim($name)]
        );
        return $transmission->id;
    }

    /**
     * Get or create type
     */
    protected function getOrCreateType(string $name): int
    {
        $type = Type::firstOrCreate(
            ['name' => trim($name)],
            ['name' => trim($name)]
        );
        return $type->id;
    }

    /**
     * Get or create use
     */
    protected function getOrCreateUse(string $name): int
    {
        $use = VehicleUse::firstOrCreate(
            ['name' => trim($name)],
            ['name' => trim($name)]
        );
        return $use->id;
    }

    /**
     * Get or create equipment
     */
    protected function getOrCreateEquipment(string $name): int
    {
        $equipment = Equipment::firstOrCreate(
            ['name' => trim($name)],
            ['name' => trim($name)]
        );
        return $equipment->id;
    }

    /**
     * Check if array is numeric (list) vs associative
     */
    protected function isNumericArray(array $array): bool
    {
        if (empty($array)) {
            return true;
        }
        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Get API headers
     */
    protected function getHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
        ];

        if ($this->apiToken) {
            $headers['Authorization'] = "Bearer {$this->apiToken}";
        }

        return $headers;
    }

    /**
     * Handle API response and map errors
     */
    protected function handleResponse($response, string $method): array
    {
        if ($response->successful()) {
            return $response->json();
        }

        $statusCode = $response->status();

        // Map HTTP status codes to exceptions
        switch ($statusCode) {
            case 400:
            case 404:
                throw NummerpladeApiException::invalidInput(
                    $response->json()['message'] ?? 'Invalid registration or VIN provided'
                );
            
            case 429:
                throw NummerpladeApiException::rateLimit(
                    $response->json()['message'] ?? 'Nummerplade API rate limit exceeded'
                );
            
            case 503:
            case 502:
                throw NummerpladeApiException::serviceDown(
                    $response->json()['message'] ?? 'Nummerplade API service is unavailable'
                );
            
            default:
                throw NummerpladeApiException::unknown(
                    $response->json()['message'] ?? "Unknown error from Nummerplade API (Status: {$statusCode})"
                );
        }
    }
}

