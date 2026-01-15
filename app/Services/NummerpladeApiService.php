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
use App\Models\Variant;
use App\Models\Euronom;
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

    /**
     * In-memory cache for lookup tables (name => id)
     * Loaded once and reused across all requests
     */
    protected static ?array $lookupCache = null;

    public function __construct()
    {
        $this->baseUrl = config('nummerplade.base_url');
        $this->apiToken = config('nummerplade.api_token');
        $this->timeout = config('nummerplade.timeout', 60); // Increased default to 60 seconds
        $this->cacheTtl = config('nummerplade.cache.ttl', 86400);
        $this->referenceCacheTtl = config('nummerplade.cache.reference_data_ttl', 86400);
        
        // Initialize lookup cache on first use - lazy initialization to prevent memory issues
        // Don't initialize in constructor, initialize on first access
        // $this->initializeLookupCache();
    }

    /**
     * Get vehicle by registration (license plate)
     */
    public function getVehicleByRegistration(string $registration, bool $advanced = false): array
    {
        // TODO: Add cache wrapper here later for API response caching
        // $cacheKey = "nummerplade:vehicle:registration:{$registration}:advanced:" . ($advanced ? '1' : '0');
        
        // Use longer timeout for vehicle lookups (they can be slow)
        $vehicleLookupTimeout = config('nummerplade.vehicle_lookup_timeout', 60);
        
        try {
            $url = "{$this->baseUrl}/{$registration}";
            
            $response = Http::timeout($vehicleLookupTimeout)
                ->connectTimeout(15) // Connection timeout separate from request timeout (increased to 15s)
                ->withHeaders($this->getHeaders())
                ->when($advanced, fn($request) => $request->withQueryParameters(['advanced' => '1']))
                ->get($url);

            $data = $this->handleResponse($response, 'getVehicleByRegistration');
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

        // Process the data to replace lookup values with IDs
        try {
            $startTime = microtime(true);
            $memoryBefore = memory_get_usage(true);
            
            // Check memory before processing
            $memoryLimit = ini_get('memory_limit');
            Log::info('Starting processLookupData', [
                'registration' => $registration,
                'memory_before_mb' => round($memoryBefore / 1024 / 1024, 2),
                'memory_limit' => $memoryLimit,
                'data_size' => strlen(serialize($data)),
            ]);
            
            $processedData = $this->processLookupData($data);
            
            $processingTime = microtime(true) - $startTime;
            $memoryAfter = memory_get_usage(true);
            $memoryUsed = round(($memoryAfter - $memoryBefore) / 1024 / 1024, 2);
            
            Log::info('processLookupData completed', [
                'registration' => $registration,
                'processing_time' => round($processingTime, 2) . 's',
                'memory_used_mb' => $memoryUsed,
                'memory_after_mb' => round($memoryAfter / 1024 / 1024, 2),
                'data_keys_count' => count($processedData),
            ]);
            
            return $processedData;
        } catch (\Throwable $e) {
            Log::error('Error in processLookupData', [
                'registration' => $registration,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                'memory_limit' => ini_get('memory_limit'),
            ]);
            throw $e;
        }
    }

    /**
     * Get vehicle by VIN
     */
    public function getVehicleByVin(string $vin, bool $advanced = false): array
    {
        // TODO: Add cache wrapper here later for API response caching
        // $cacheKey = "nummerplade:vehicle:vin:{$vin}:advanced:" . ($advanced ? '1' : '0');
        
        // Use longer timeout for vehicle lookups (they can be slow)
        $vehicleLookupTimeout = config('nummerplade.vehicle_lookup_timeout', 60);
        
        try {
            $url = "{$this->baseUrl}/vin/{$vin}";
            
            $response = Http::timeout($vehicleLookupTimeout)
                ->connectTimeout(15) // Connection timeout separate from request timeout (increased to 15s)
                ->withHeaders($this->getHeaders())
                ->when($advanced, fn($request) => $request->withQueryParameters(['advanced' => '1']))
                ->get($url);

            $data = $this->handleResponse($response, 'getVehicleByVin');
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

        // Process the data using cached lookup tables (no DB queries for existing values)
        return $this->processLookupData($data);
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
     * Initialize in-memory cache for lookup tables
     * This avoids DB queries when converting names to IDs
     * Cache is shared across all instances (static)
     */
    protected function initializeLookupCache(): void
    {
        if (self::$lookupCache !== null) {
            return; // Already initialized
        }
        
        // Lazy initialization - only initialize when actually needed
        // This prevents memory exhaustion during service instantiation

        // Initialize empty cache structure - load data lazily when needed
        // This prevents memory exhaustion from loading all records at once
        self::$lookupCache = [
            'brands' => [],
            'models' => [],
            'model_years' => [],
            'categories' => [],
            'fuel_types' => [],
            'body_types' => [],
            'colors' => [],
            'conditions' => [],
            'gear_types' => [],
            'transmissions' => [],
            'types' => [],
            'uses' => [],
            'equipment' => [],
            'variants' => [],
            'euronorms' => [],
        ];
        
        // Don't pre-load all data - load on-demand to prevent memory issues
    }

    /**
     * Process lookup data - replace names with IDs using cached lookup tables
     * Only hits DB when creating NEW records that don't exist
     * This dramatically reduces database load for lookups
     */
    protected function processLookupData(array $data, int $depth = 0): array
    {
        // Prevent infinite recursion - limit depth to 3 levels (only for specific known nested structures)
        if ($depth > 3) {
            return $data;
        }

        // Extract special fields that need separate processing
        $typeValue = null;
        $useValue = null;
        $bodyTypeValue = null;
        
        if (isset($data['type'])) {
            $typeValue = $data['type'];
        }
        if (isset($data['use'])) {
            $useValue = $data['use'];
        }
        if (isset($data['body_type']) || isset($data['bodyType'])) {
            $bodyTypeValue = $data['body_type'] ?? $data['bodyType'] ?? null;
        }

        // DO NOT recursively process all nested arrays - this causes memory exhaustion
        // Only process top-level fields we know about

        // Process brand first (model depends on it) - convert to object with id and name
        $brandId = null;
        $brandName = null;
        if (isset($data['brand'])) {
            $brandOriginalValue = $data['brand'];
            $brandId = $this->getBrandId($brandOriginalValue);
            if ($brandId) {
                $brandName = $this->getBrandName($brandOriginalValue, $brandId);
                // Convert to object format like color, variant, euronorm
                $data['brand'] = [
                    'id' => $brandId,
                    'name' => $brandName ?? trim($brandOriginalValue)
                ];
                // Store brand name for title display
                if ($brandName) {
                    $data['brand_name'] = $brandName;
                }
            }
        } elseif (isset($data['make'])) {
            $brandOriginalValue = $data['make'];
            $brandId = $this->getBrandId($brandOriginalValue);
            if ($brandId) {
                $brandName = $this->getBrandName($brandOriginalValue, $brandId);
                // Convert to object format
                $data['brand'] = [
                    'id' => $brandId,
                    'name' => $brandName ?? trim($brandOriginalValue)
                ];
                // Store brand name for title display
                if ($brandName) {
                    $data['brand_name'] = $brandName;
                }
            }
        }

        // Process model (needs brand_id, so process brand first) - convert to object with id and name
        $modelId = null;
        $modelName = null;
        if (isset($data['model']) && $brandId) {
            $modelOriginalValue = $data['model'];
            $modelId = $this->getModelId($modelOriginalValue, $brandId);
            if ($modelId) {
                $modelName = $this->getModelName($modelOriginalValue, $modelId);
                // Convert to object format
                $data['model'] = [
                    'id' => $modelId,
                    'name' => $modelName ?? trim($modelOriginalValue)
                ];
                // Store model name for title display
                if ($modelName) {
                    $data['model_name'] = $modelName;
                }
            }
        } elseif (isset($data['model'])) {
            // Try to get brand from already converted fields
            $brandIdForModel = $brandId 
                ?? (isset($data['brand']) && is_array($data['brand']) ? $data['brand']['id'] : null)
                ?? (isset($data['make']) && is_int($data['make']) ? $data['make'] : null);
            
            if ($brandIdForModel) {
                $modelOriginalValue = $data['model'];
                $modelId = $this->getModelId($modelOriginalValue, $brandIdForModel);
                if ($modelId) {
                    $modelName = $this->getModelName($modelOriginalValue, $modelId);
                    // Convert to object format
                    $data['model'] = [
                        'id' => $modelId,
                        'name' => $modelName ?? trim($modelOriginalValue)
                    ];
                    // Store model name for title display
                    if ($modelName) {
                        $data['model_name'] = $modelName;
                    }
                }
            }
        }

        // Process model_year - convert to object with id and name
        $yearId = null;
        $yearName = null;
        if (isset($data['model_year']) || isset($data['year'])) {
            $yearOriginalValue = $data['model_year'] ?? $data['year'] ?? null;
            $yearId = $this->getModelYearId($yearOriginalValue);
            if ($yearId) {
                $yearName = $this->getModelYearName($yearOriginalValue, $yearId);
                // Convert to object format
                $data['model_year'] = [
                    'id' => $yearId,
                    'name' => $yearName ?? trim((string)$yearOriginalValue)
                ];
                // Store model year name for title display
                if ($yearName) {
                    $data['model_year_name'] = $yearName;
                }
                // Remove year if it exists
                if (isset($data['year'])) {
                    unset($data['year']);
                }
            }
        }

        // Process category - keep as string (not a lookup table in vehicle_details)
        // Category is stored as a string in vehicle_details.category field
        // Don't convert to ID, just keep the original value
        if (isset($data['category']) && !is_string($data['category'])) {
            // If it's an object or array, extract the name/string value
            if (is_array($data['category'])) {
                $data['category'] = $data['category']['name'] ?? $data['category']['value'] ?? (string)$data['category'];
            } elseif (is_object($data['category'])) {
                $data['category'] = $data['category']->name ?? $data['category']->value ?? (string)$data['category'];
            } else {
                $data['category'] = (string)$data['category'];
            }
        }
        // Remove vehicleType if it exists (duplicate of category)
        if (isset($data['vehicleType'])) {
            unset($data['vehicleType']);
        }

        // Process fuel_type - convert to object with id and name
        $fuelTypeId = null;
        $fuelTypeName = null;
        if (isset($data['fuel_type']) || isset($data['fuelType'])) {
            $fuelTypeOriginalValue = $data['fuel_type'] ?? $data['fuelType'] ?? null;
            $fuelTypeId = $this->getFuelTypeId($fuelTypeOriginalValue);
            if ($fuelTypeId) {
                $fuelTypeName = $this->getFuelTypeName($fuelTypeOriginalValue, $fuelTypeId);
                // Convert to object format
                $data['fuel_type'] = [
                    'id' => $fuelTypeId,
                    'name' => $fuelTypeName ?? trim($fuelTypeOriginalValue)
                ];
                // Store fuel type name for title display
                if ($fuelTypeName) {
                    $data['fuel_type_name'] = $fuelTypeName;
                }
                // Remove fuelType if it exists
                if (isset($data['fuelType'])) {
                    unset($data['fuelType']);
                }
            }
        }

        // Process body_type - convert to object with id and name (using pre-extracted value)
        if ($bodyTypeValue !== null) {
            $bodyType = $this->getBodyTypeRecord($bodyTypeValue);
            if ($bodyType) {
                $data['body_type'] = [
                    'id' => $bodyType['id'],
                    'name' => $bodyType['name']
                ];
            }
        }

        // Process color - use name from API to find/create in our database
        if (isset($data['color'])) {
            $colorValue = $data['color'];
            // Extract name from color object if it's an array/object
            $colorName = null;
            if (is_array($colorValue)) {
                $colorName = $colorValue['name'] ?? null;
            } elseif (is_object($colorValue)) {
                $colorName = $colorValue->name ?? null;
            } else {
                $colorName = $colorValue;
            }
            
            if ($colorName) {
                $color = $this->getColor($colorName);
                if ($color) {
                    // Send as object with id and name
                    $data['color'] = [
                        'id' => $color['id'],
                        'name' => $color['name']
                    ];
                }
            }
        }

        // Process variant - use "version" field from API to find/create in our database
        if (isset($data['version']) && !empty($data['version'])) {
            $variantName = $data['version'];
            $variant = $this->getVariant($variantName);
            if ($variant) {
                // Send as object with id and name
                $data['variant'] = [
                    'id' => $variant['id'],
                    'name' => $variant['name']
                ];
            }
        }

        // Process euronorm - use "euronorm" field from API to find/create in our database
        if (isset($data['euronorm']) && !empty($data['euronorm'])) {
            $euronomName = $data['euronorm'];
            $euronom = $this->getEuronom($euronomName);
            if ($euronom) {
                // Send as object with id and name
                $data['euronorm'] = [
                    'id' => $euronom['id'],
                    'name' => $euronom['name']
                ];
            }
        }

        // Process condition
        if (isset($data['condition'])) {
            $conditionId = $this->getConditionId($data['condition']);
            if ($conditionId) {
                $data['condition'] = $conditionId;
            }
        }

        // Process gear_type
        if (isset($data['gear_type']) || isset($data['gearType'])) {
            $gearTypeValue = $data['gear_type'] ?? $data['gearType'] ?? null;
            $gearTypeId = $this->getGearTypeId($gearTypeValue);
            if ($gearTypeId) {
                if (isset($data['gear_type'])) $data['gear_type'] = $gearTypeId;
                if (isset($data['gearType'])) $data['gearType'] = $gearTypeId;
            }
        }

        // Process transmission
        if (isset($data['transmission'])) {
            $transmissionId = $this->getTransmissionId($data['transmission']);
            if ($transmissionId) {
                $data['transmission'] = $transmissionId;
            }
        }

        // Process type - convert to object with id and name (using pre-extracted value)
        if ($typeValue !== null) {
            $type = $this->getTypeRecord($typeValue);
            if ($type) {
                $data['type'] = [
                    'id' => $type['id'],
                    'name' => $type['name']
                ];
            }
        }

        // Process use - convert to object with id and name (using pre-extracted value)
        if ($useValue !== null) {
            $use = $this->getUseRecord($useValue);
            if ($use) {
                $data['use'] = [
                    'id' => $use['id'],
                    'name' => $use['name']
                ];
            }
        }

        // Process equipment (if it's an array) - return as objects with id, name, and equipment_type_id
        if (isset($data['equipment']) && is_array($data['equipment'])) {
            $equipmentObjects = [];
            $maxItems = 50; // Limit to prevent memory issues
            
            foreach (array_slice($data['equipment'], 0, $maxItems) as $equipment) {
                $equipRecord = null;
                
                if (is_array($equipment) && isset($equipment['id']) && isset($equipment['name'])) {
                    // Already has id and name from API - look up equipment_type_id from database
                    $equip = Equipment::find($equipment['id']);
                    if ($equip) {
                        $equipRecord = [
                            'id' => $equip->id,
                            'name' => $equip->name,
                            'equipment_type_id' => $equip->equipment_type_id
                        ];
                    } else {
                        // If not found in DB, use API data but try to get equipment_type_id by name
                        $equipRecord = $this->getEquipmentRecord($equipment['name']);
                        if (!$equipRecord) {
                            // Fallback: use API data without equipment_type_id
                            $equipRecord = [
                                'id' => $equipment['id'],
                                'name' => $equipment['name'],
                                'equipment_type_id' => null
                            ];
                        }
                    }
                } elseif (is_object($equipment) && isset($equipment->id) && isset($equipment->name)) {
                    // Object with id and name - look up equipment_type_id
                    $equip = Equipment::find($equipment->id);
                    if ($equip) {
                        $equipRecord = [
                            'id' => $equip->id,
                            'name' => $equip->name,
                            'equipment_type_id' => $equip->equipment_type_id
                        ];
                    } else {
                        $equipRecord = $this->getEquipmentRecord($equipment->name);
                        if (!$equipRecord) {
                            $equipRecord = [
                                'id' => $equipment->id,
                                'name' => $equipment->name,
                                'equipment_type_id' => null
                            ];
                        }
                    }
                } elseif (is_string($equipment) || (is_array($equipment) && isset($equipment['name']))) {
                    // Has name - get full record including equipment_type_id
                    $name = is_string($equipment) ? $equipment : $equipment['name'];
                    $equipRecord = $this->getEquipmentRecord($name);
                    if (!$equipRecord) {
                        // Skip if not found and not in cache
                        continue;
                    }
                }
                
                if ($equipRecord) {
                    $equipmentObjects[] = $equipRecord;
                }
            }
            
            if (!empty($equipmentObjects)) {
                $data['equipment'] = $equipmentObjects;
            } else {
                // If no equipment processed, keep original (might be empty array)
                // Don't unset it as frontend might expect it
            }
        }

        // Generate title from names (use data array values which are already set)
        $titleParts = [];
        if (!empty($data['brand_name'])) {
            $titleParts[] = $data['brand_name'];
        }
        if (!empty($data['model_name'])) {
            $titleParts[] = $data['model_name'];
        }
        if (!empty($data['model_year_name'])) {
            $titleParts[] = $data['model_year_name'];
        }
        if (!empty($data['fuel_type_name'])) {
            $titleParts[] = $data['fuel_type_name'];
        }
        $data['title'] = !empty($titleParts) ? implode(' ', $titleParts) : '';

        // Remove raw ID fields and aliases - keep object versions
        // Keep: brand, model, model_year, fuel_type (as objects), type, use, body_type (as objects), 
        // category (as string), color, variant, euronorm (as objects)
        unset($data['make'], $data['year'], $data['fuelType'], $data['vehicleType'], 
              $data['condition'], $data['gear_type'], $data['gearType'], 
              $data['transmission'], $data['version']);

        return $data;
    }

    /**
     * Get brand ID from cache, or create if doesn't exist
     */
    protected function getBrandId($value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_array($value) && isset($value['id'])) {
            return (int)$value['id'];
        }
        
        $name = is_array($value) ? ($value['name'] ?? null) : $value;
        if (!$name || !is_string($name)) {
            return null;
        }
        
        $nameKey = strtolower(trim($name));
        
        // Ensure cache is initialized
        $this->initializeLookupCache();
        
        if (isset(self::$lookupCache['brands'][$nameKey])) {
            return self::$lookupCache['brands'][$nameKey];
        }
        
        // Not in cache - create it (only happens for new brands)
        $brand = Brand::firstOrCreate(['name' => trim($name)]);
        self::$lookupCache['brands'][$nameKey] = $brand->id;
        return $brand->id;
    }

    /**
     * Get model ID from cache, or create if doesn't exist
     */
    protected function getModelId($value, int $brandId): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_array($value) && isset($value['id'])) {
            return (int)$value['id'];
        }
        
        $name = is_array($value) ? ($value['name'] ?? null) : $value;
        if (!$name || !is_string($name)) {
            return null;
        }
        
        $nameKey = strtolower(trim($name)) . '|' . $brandId;
        
        // Ensure cache is initialized
        $this->initializeLookupCache();
        
        if (isset(self::$lookupCache['models'][$nameKey])) {
            return self::$lookupCache['models'][$nameKey];
        }
        
        // Not in cache - create it
        $model = VehicleModel::firstOrCreate([
            'name' => trim($name),
            'brand_id' => $brandId
        ]);
        self::$lookupCache['models'][$nameKey] = $model->id;
        return $model->id;
    }

    /**
     * Get model year ID from cache, or create if doesn't exist
     */
    protected function getModelYearId($value): ?int
    {
        $yearKey = null;
        
        // If value is an array with 'id' and/or 'name' keys
        if (is_array($value)) {
            $potentialId = isset($value['id']) ? (int)$value['id'] : null;
            $nameValue = $value['name'] ?? $value['year'] ?? null;
            
            // If there's a name field, prioritize it (most reliable - use it to look up by name)
            if ($nameValue) {
                $yearKey = trim((string)$nameValue);
            } elseif ($potentialId !== null) {
                // No name field, check if the ID looks like a year value (1900-2100 range)
                // If it does, treat it as a year value, not a database ID
                if ($potentialId >= 1900 && $potentialId <= 2100) {
                    $yearKey = (string)$potentialId;
                } else {
                    // ID is outside year range, check if it's a valid database ID
                    $modelYear = ModelYear::find($potentialId);
                    if ($modelYear) {
                        return $modelYear->id;
                    }
                    // If not found, treat as year anyway
                    $yearKey = (string)$potentialId;
                }
            }
        } elseif (is_int($value)) {
            // Integer value - could be a year (like 2020) or a database ID
            // If it's a reasonable year value (1900-2100), treat it as a year
            if ($value >= 1900 && $value <= 2100) {
                $yearKey = (string)$value;
            } else {
                // Otherwise, check if it's a valid database ID
                $modelYear = ModelYear::find($value);
                if ($modelYear) {
                    return $modelYear->id;
                }
                // If not found, treat as year anyway
                $yearKey = (string)$value;
            }
        } else {
            // Extract year value from various formats
            $year = is_array($value) ? ($value['name'] ?? $value['year'] ?? $value['id'] ?? null) : $value;
            if (!$year) {
                return null;
            }
            $yearKey = trim((string)$year);
        }
        
        if (!$yearKey) {
            return null;
        }
        
        // Ensure cache is initialized
        $this->initializeLookupCache();
        
        if (isset(self::$lookupCache['model_years'][$yearKey])) {
            return self::$lookupCache['model_years'][$yearKey];
        }
        
        // Not in cache - look up or create by name (not by ID)
        $modelYear = ModelYear::firstOrCreate(['name' => $yearKey]);
        self::$lookupCache['model_years'][$yearKey] = $modelYear->id;
        return $modelYear->id;
    }

    /**
     * Get category ID from cache, or create if doesn't exist
     */
    protected function getCategoryId($value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_array($value) && isset($value['id'])) {
            return (int)$value['id'];
        }
        
        $name = is_array($value) ? ($value['name'] ?? null) : $value;
        if (!$name || !is_string($name)) {
            return null;
        }
        
        $nameKey = strtolower(trim($name));
        if (isset(self::$lookupCache['categories'][$nameKey])) {
            return self::$lookupCache['categories'][$nameKey];
        }
        
        // Not in cache - create it
        $category = Category::firstOrCreate(['name' => trim($name)]);
        self::$lookupCache['categories'][$nameKey] = $category->id;
        return $category->id;
    }

    /**
     * Get fuel type ID from cache, or create if doesn't exist
     */
    protected function getFuelTypeId($value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_array($value) && isset($value['id'])) {
            return (int)$value['id'];
        }
        
        $name = is_array($value) ? ($value['name'] ?? null) : $value;
        if (!$name || !is_string($name)) {
            return null;
        }
        
        $nameKey = strtolower(trim($name));
        if (isset(self::$lookupCache['fuel_types'][$nameKey])) {
            return self::$lookupCache['fuel_types'][$nameKey];
        }
        
        // Not in cache - create it
        $fuelType = FuelType::firstOrCreate(['name' => trim($name)]);
        self::$lookupCache['fuel_types'][$nameKey] = $fuelType->id;
        return $fuelType->id;
    }

    /**
     * Get brand name from value or ID
     */
    protected function getBrandName($value, ?int $brandId = null): ?string
    {
        // If value is already a string name, return it
        if (is_string($value) && !is_numeric($value)) {
            return trim($value);
        }
        
        // If we have an ID, look it up
        if ($brandId) {
            $brand = Brand::find($brandId);
            return $brand ? $brand->name : null;
        }
        
        return null;
    }

    /**
     * Get model name from value or ID
     */
    protected function getModelName($value, ?int $modelId = null): ?string
    {
        // If value is already a string name, return it
        if (is_string($value) && !is_numeric($value)) {
            return trim($value);
        }
        
        // If value is an array with name
        if (is_array($value) && isset($value['name'])) {
            return trim($value['name']);
        }
        
        // If we have an ID, look it up
        if ($modelId) {
            $model = VehicleModel::find($modelId);
            return $model ? $model->name : null;
        }
        
        return null;
    }

    /**
     * Get model year name from value or ID
     */
    protected function getModelYearName($value, ?int $yearId = null): ?string
    {
        // If value is already a string/number, return it as string
        if (is_string($value) || is_numeric($value)) {
            return trim((string)$value);
        }
        
        // If value is an array with name or year
        if (is_array($value)) {
            if (isset($value['name'])) {
                return trim((string)$value['name']);
            }
            if (isset($value['year'])) {
                return trim((string)$value['year']);
            }
        }
        
        // If we have an ID, look it up
        if ($yearId) {
            $modelYear = ModelYear::find($yearId);
            return $modelYear ? $modelYear->name : null;
        }
        
        return null;
    }

    /**
     * Get fuel type name from value or ID
     */
    protected function getFuelTypeName($value, ?int $fuelTypeId = null): ?string
    {
        // If value is already a string name, return it
        if (is_string($value) && !is_numeric($value)) {
            return trim($value);
        }
        
        // If value is an array with name
        if (is_array($value) && isset($value['name'])) {
            return trim($value['name']);
        }
        
        // If we have an ID, look it up
        if ($fuelTypeId) {
            $fuelType = FuelType::find($fuelTypeId);
            return $fuelType ? $fuelType->name : null;
        }
        
        return null;
    }

    /**
     * Get body type ID from cache, or create if doesn't exist
     */
    protected function getBodyTypeId($value): ?int
    {
        $bodyType = $this->getBodyTypeRecord($value);
        return $bodyType ? $bodyType['id'] : null;
    }

    /**
     * Get body type row (id and name) from cache, or create if doesn't exist
     * Returns array with 'id' and 'name' keys
     */
    protected function getBodyTypeRecord($value): ?array
    {
        // If it's already an integer (our database ID), look it up
        if (is_int($value)) {
            $bodyType = BodyType::find($value);
            return $bodyType ? ['id' => $bodyType->id, 'name' => $bodyType->name] : null;
        }
        
        // Extract name from body type object/array
        $name = null;
        if (is_array($value)) {
            $name = $value['name'] ?? null;
        } elseif (is_object($value)) {
            $name = $value->name ?? null;
        } else {
            $name = $value;
        }
        
        if (!$name || !is_string($name)) {
            return null;
        }
        
        $nameKey = strtolower(trim($name));
        
        // Check cache first
        if (isset(self::$lookupCache['body_types'][$nameKey])) {
            $bodyTypeId = self::$lookupCache['body_types'][$nameKey];
            $bodyType = BodyType::find($bodyTypeId);
            return $bodyType ? ['id' => $bodyType->id, 'name' => $bodyType->name] : null;
        }
        
        // Not in cache - create it using firstOrCreate
        $bodyType = BodyType::firstOrCreate(['name' => trim($name)]);
        self::$lookupCache['body_types'][$nameKey] = $bodyType->id;
        return ['id' => $bodyType->id, 'name' => $bodyType->name];
    }

    /**
     * Get color ID from cache, or create if doesn't exist
     * Uses the name from API to find/create in our database (not the API's ID)
     */
    protected function getColorId($value): ?int
    {
        $color = $this->getColor($value);
        return $color ? $color['id'] : null;
    }

    /**
     * Get color row (id and name) from cache, or create if doesn't exist
     * Returns array with 'id' and 'name' keys
     */
    protected function getColor($value): ?array
    {
        // If it's already an integer (our database ID), look it up
        if (is_int($value)) {
            $color = Color::find($value);
            return $color ? ['id' => $color->id, 'name' => $color->name] : null;
        }
        
        // Extract name from color object/array (ignore API's ID, use name to find/create in our DB)
        $name = null;
        if (is_array($value)) {
            $name = $value['name'] ?? null;
        } elseif (is_object($value)) {
            $name = $value->name ?? null;
        } else {
            $name = $value;
        }
        
        if (!$name || !is_string($name)) {
            return null;
        }
        
        $nameKey = strtolower(trim($name));
        
        // Check cache first
        if (isset(self::$lookupCache['colors'][$nameKey])) {
            $colorId = self::$lookupCache['colors'][$nameKey];
            $color = Color::find($colorId);
            return $color ? ['id' => $color->id, 'name' => $color->name] : null;
        }
        
        // Not in cache - create it using firstOrCreate
        $color = Color::firstOrCreate(['name' => trim($name)]);
        self::$lookupCache['colors'][$nameKey] = $color->id;
        return ['id' => $color->id, 'name' => $color->name];
    }

    /**
     * Get variant row (id and name) from cache, or create if doesn't exist
     * Returns array with 'id' and 'name' keys
     */
    protected function getVariant($name): ?array
    {
        if (!$name || !is_string($name)) {
            return null;
        }
        
        $nameKey = strtolower(trim($name));
        
        // Check if we have a variants cache
        if (!isset(self::$lookupCache['variants'])) {
            self::$lookupCache['variants'] = [];
        }
        
        // Check cache first
        if (isset(self::$lookupCache['variants'][$nameKey])) {
            $variantId = self::$lookupCache['variants'][$nameKey];
            $variant = Variant::find($variantId);
            return $variant ? ['id' => $variant->id, 'name' => $variant->name] : null;
        }
        
        // Not in cache - create it using firstOrCreate
        $variant = Variant::firstOrCreate(['name' => trim($name)]);
        self::$lookupCache['variants'][$nameKey] = $variant->id;
        return ['id' => $variant->id, 'name' => $variant->name];
    }

    /**
     * Get euronom row (id and name) from cache, or create if doesn't exist
     * Returns array with 'id' and 'name' keys
     */
    protected function getEuronom($name): ?array
    {
        if (!$name || !is_string($name)) {
            return null;
        }
        
        $nameKey = strtolower(trim($name));
        
        // Check if we have a euronorms cache
        if (!isset(self::$lookupCache['euronorms'])) {
            self::$lookupCache['euronorms'] = [];
        }
        
        // Check cache first
        if (isset(self::$lookupCache['euronorms'][$nameKey])) {
            $euronomId = self::$lookupCache['euronorms'][$nameKey];
            $euronom = Euronom::find($euronomId);
            return $euronom ? ['id' => $euronom->id, 'name' => $euronom->name] : null;
        }
        
        // Not in cache - create it using firstOrCreate
        $euronom = Euronom::firstOrCreate(['name' => trim($name)]);
        self::$lookupCache['euronorms'][$nameKey] = $euronom->id;
        return ['id' => $euronom->id, 'name' => $euronom->name];
    }

    /**
     * Get condition ID from cache, or create if doesn't exist
     */
    protected function getConditionId($value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_array($value) && isset($value['id'])) {
            return (int)$value['id'];
        }
        
        $name = is_array($value) ? ($value['name'] ?? null) : $value;
        if (!$name || !is_string($name)) {
            return null;
        }
        
        $nameKey = strtolower(trim($name));
        if (isset(self::$lookupCache['conditions'][$nameKey])) {
            return self::$lookupCache['conditions'][$nameKey];
        }
        
        // Not in cache - create it
        $condition = Condition::firstOrCreate(['name' => trim($name)]);
        self::$lookupCache['conditions'][$nameKey] = $condition->id;
        return $condition->id;
    }

    /**
     * Get gear type ID from cache, or create if doesn't exist
     */
    protected function getGearTypeId($value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_array($value) && isset($value['id'])) {
            return (int)$value['id'];
        }
        
        $name = is_array($value) ? ($value['name'] ?? null) : $value;
        if (!$name || !is_string($name)) {
            return null;
        }
        
        $nameKey = strtolower(trim($name));
        if (isset(self::$lookupCache['gear_types'][$nameKey])) {
            return self::$lookupCache['gear_types'][$nameKey];
        }
        
        // Not in cache - create it
        $gearType = GearType::firstOrCreate(['name' => trim($name)]);
        self::$lookupCache['gear_types'][$nameKey] = $gearType->id;
        return $gearType->id;
    }

    /**
     * Get transmission ID from cache, or create if doesn't exist
     */
    protected function getTransmissionId($value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_array($value) && isset($value['id'])) {
            return (int)$value['id'];
        }
        
        $name = is_array($value) ? ($value['name'] ?? null) : $value;
        if (!$name || !is_string($name)) {
            return null;
        }
        
        $nameKey = strtolower(trim($name));
        if (isset(self::$lookupCache['transmissions'][$nameKey])) {
            return self::$lookupCache['transmissions'][$nameKey];
        }
        
        // Not in cache - create it
        $transmission = Transmission::firstOrCreate(['name' => trim($name)]);
        self::$lookupCache['transmissions'][$nameKey] = $transmission->id;
        return $transmission->id;
    }

    /**
     * Get type ID from cache, or create if doesn't exist
     */
    protected function getTypeId($value): ?int
    {
        $type = $this->getTypeRecord($value);
        return $type ? $type['id'] : null;
    }

    /**
     * Get type row (id and name) from cache, or create if doesn't exist
     * Returns array with 'id' and 'name' keys
     */
    protected function getTypeRecord($value): ?array
    {
        // If it's already an integer (our database ID), look it up
        if (is_int($value)) {
            $type = Type::find($value);
            return $type ? ['id' => $type->id, 'name' => $type->name] : null;
        }
        
        // Extract name from type object/array
        $name = null;
        if (is_array($value)) {
            $name = $value['name'] ?? null;
        } elseif (is_object($value)) {
            $name = $value->name ?? null;
        } else {
            $name = $value;
        }
        
        if (!$name || !is_string($name)) {
            return null;
        }
        
        $nameKey = strtolower(trim($name));
        
        // Check cache first
        if (isset(self::$lookupCache['types'][$nameKey])) {
            $typeId = self::$lookupCache['types'][$nameKey];
            $type = Type::find($typeId);
            return $type ? ['id' => $type->id, 'name' => $type->name] : null;
        }
        
        // Not in cache - create it using firstOrCreate
        $type = Type::firstOrCreate(['name' => trim($name)]);
        self::$lookupCache['types'][$nameKey] = $type->id;
        return ['id' => $type->id, 'name' => $type->name];
    }

    /**
     * Get use ID from cache, or create if doesn't exist
     */
    protected function getUseId($value): ?int
    {
        $use = $this->getUseRecord($value);
        return $use ? $use['id'] : null;
    }

    /**
     * Get use row (id and name) from cache, or create if doesn't exist
     * Returns array with 'id' and 'name' keys
     */
    protected function getUseRecord($value): ?array
    {
        // If it's already an integer (our database ID), look it up
        if (is_int($value)) {
            $use = VehicleUse::find($value);
            return $use ? ['id' => $use->id, 'name' => $use->name] : null;
        }
        
        // Extract name from use object/array
        $name = null;
        if (is_array($value)) {
            $name = $value['name'] ?? null;
        } elseif (is_object($value)) {
            $name = $value->name ?? null;
        } else {
            $name = $value;
        }
        
        if (!$name || !is_string($name)) {
            return null;
        }
        
        $nameKey = strtolower(trim($name));
        
        // Check cache first
        if (isset(self::$lookupCache['uses'][$nameKey])) {
            $useId = self::$lookupCache['uses'][$nameKey];
            $use = VehicleUse::find($useId);
            return $use ? ['id' => $use->id, 'name' => $use->name] : null;
        }
        
        // Not in cache - create it using firstOrCreate
        $use = VehicleUse::firstOrCreate(['name' => trim($name)]);
        self::$lookupCache['uses'][$nameKey] = $use->id;
        return ['id' => $use->id, 'name' => $use->name];
    }

    /**
     * Get equipment ID from cache, or create if doesn't exist
     */
    protected function getEquipmentId($value): ?int
    {
        $equipment = $this->getEquipmentRecord($value);
        return $equipment ? $equipment['id'] : null;
    }

    /**
     * Get equipment row (id, name, and equipment_type_id) from cache, or create if doesn't exist
     * Returns array with 'id', 'name', and 'equipment_type_id' keys
     */
    protected function getEquipmentRecord($value): ?array
    {
        // If it's already an integer (our database ID), look it up
        if (is_int($value)) {
            $equipment = Equipment::find($value);
            return $equipment ? [
                'id' => $equipment->id, 
                'name' => $equipment->name,
                'equipment_type_id' => $equipment->equipment_type_id
            ] : null;
        }
        
        // If it's an array with id, look it up
        if (is_array($value) && isset($value['id'])) {
            $equipment = Equipment::find($value['id']);
            return $equipment ? [
                'id' => $equipment->id, 
                'name' => $equipment->name,
                'equipment_type_id' => $equipment->equipment_type_id
            ] : null;
        }
        
        // Extract name from equipment object/array
        $name = null;
        if (is_array($value)) {
            $name = $value['name'] ?? null;
        } elseif (is_object($value)) {
            $name = $value->name ?? null;
        } else {
            $name = $value;
        }
        
        if (!$name || !is_string($name)) {
            return null;
        }
        
        $nameKey = strtolower(trim($name));
        
        // Check cache first
        if (isset(self::$lookupCache['equipment'][$nameKey])) {
            $equipmentId = self::$lookupCache['equipment'][$nameKey];
            $equipment = Equipment::find($equipmentId);
            return $equipment ? [
                'id' => $equipment->id, 
                'name' => $equipment->name,
                'equipment_type_id' => $equipment->equipment_type_id
            ] : null;
        }
        
        // Not in cache - create it using firstOrCreate
        $equipment = Equipment::firstOrCreate(['name' => trim($name)]);
        self::$lookupCache['equipment'][$nameKey] = $equipment->id;
        return [
            'id' => $equipment->id, 
            'name' => $equipment->name,
            'equipment_type_id' => $equipment->equipment_type_id
        ];
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
            $jsonData = $response->json();
            
            // If the response has a 'data' key, unwrap it (Nummerplade API might wrap responses)
            if (isset($jsonData['data']) && is_array($jsonData['data']) && count($jsonData) === 1) {
                return $jsonData['data'];
            }
            
            return $jsonData;
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

