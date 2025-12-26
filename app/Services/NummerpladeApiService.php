<?php

namespace App\Services;

use App\Exceptions\NummerpladeApiException;
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
        $this->timeout = config('nummerplade.timeout', 30);
        $this->cacheTtl = config('nummerplade.cache.ttl', 86400);
        $this->referenceCacheTtl = config('nummerplade.cache.reference_data_ttl', 86400);
    }

    /**
     * Get vehicle by registration (license plate)
     */
    public function getVehicleByRegistration(string $registration, bool $advanced = false): array
    {
        $cacheKey = "nummerplade:vehicle:registration:{$registration}:advanced:" . ($advanced ? '1' : '0');

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($registration, $advanced) {
            try {
                $url = "{$this->baseUrl}/{$registration}";
                
                $response = Http::timeout($this->timeout)
                    ->withHeaders($this->getHeaders())
                    ->when($advanced, fn($request) => $request->withQueryParameters(['advanced' => '1']))
                    ->get($url);

                return $this->handleResponse($response, 'getVehicleByRegistration');
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
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
    }

    /**
     * Get vehicle by VIN
     */
    public function getVehicleByVin(string $vin, bool $advanced = false): array
    {
        $cacheKey = "nummerplade:vehicle:vin:{$vin}:advanced:" . ($advanced ? '1' : '0');

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($vin, $advanced) {
            try {
                $url = "{$this->baseUrl}/vin/{$vin}";
                
                $response = Http::timeout($this->timeout)
                    ->withHeaders($this->getHeaders())
                    ->when($advanced, fn($request) => $request->withQueryParameters(['advanced' => '1']))
                    ->get($url);

                return $this->handleResponse($response, 'getVehicleByVin');
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
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

