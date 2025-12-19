<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\VehicleService;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Helpers\FilterHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VehicleController extends Controller
{
    public function __construct(
        private VehicleService $vehicleService
    ) {}

    /**
     * Get featured vehicles
     */
    public function getFeaturedVehicles(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 6);
        $page = $request->input('page', 1);

        $vehicles = Vehicle::orderBy('listing_price', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        return $this->paginatedResponse($vehicles);
    }

    /**
     * Get vehicles list
     */
    public function getVehicles(Request $request): JsonResponse
    {
        $query = Vehicle::query();

        // Apply search
        $search = $request->input('search');
        $searchableFields = [
            'registration_number', 'make', 'model', 'variant', 'color',
            'vehicle_type', 'fuel_type', 'transmission_type', 'inventory_date',
            'odometer', 'remarks'
        ];
        FilterHelper::applySearch($query, $search, $searchableFields);

        // Apply filters
        $filters = json_decode($request->input('filters', '[]'), true);
        $joinOperator = $request->input('joinOperator', 'or');
        FilterHelper::applyFilters($query, $filters, $joinOperator);

        // Apply sorting
        $sort = json_decode($request->input('sort', '[]'), true);
        FilterHelper::applySorting($query, $sort);

        // Paginate
        $perPage = $request->input('perPage', 10);
        $vehicles = $query->withCount(['purchases', 'sales'])->paginate($perPage);

        return $this->paginatedResponse($vehicles);
    }

    /**
     * Get vehicles overview statistics
     */
    public function getVehiclesOverview(): JsonResponse
    {
        $totalVehicles = Vehicle::count();
        $availableVehicles = Vehicle::where('status', 'Available')->count();
        $pendingVehicles = Vehicle::whereIn('status', ['Pending Sale', 'Pending Purchase'])->count();
        $totalInventoryValue = Vehicle::where('status', 'Available')->sum('listing_price');
        $averageVehicleValue = $availableVehicles > 0 ? $totalInventoryValue / $availableVehicles : 0;
        
        // Calculate average days in inventory
        $averageDaysInInventory = Vehicle::where('status', 'Available')
            ->selectRaw('AVG(DATEDIFF(NOW(), inventory_date)) as avg_days')
            ->value('avg_days') ?? 0;

        $vehiclesOver90Days = Vehicle::where('status', 'Available')
            ->whereRaw('DATEDIFF(NOW(), inventory_date) > 90')
            ->count();

        $vehiclesNeedingWork = Vehicle::where('status', 'Available')
            ->whereJsonLength('pending_works', '>', 0)
            ->count();

        $newArrivals7Days = Vehicle::where('created_at', '>=', now()->subDays(7))->count();
        $recentlyUpdated24h = Vehicle::where('updated_at', '>=', now()->subDay())->count();

        return response()->json([
            'totalVehicles' => $totalVehicles,
            'availableVehicles' => $availableVehicles,
            'pendingVehicles' => $pendingVehicles,
            'totalInventoryValue' => $totalInventoryValue,
            'averageVehicleValue' => round($averageVehicleValue, 2),
            'averageDaysInInventory' => round($averageDaysInInventory, 2),
            'vehiclesOver90Days' => $vehiclesOver90Days,
            'vehiclesNeedingWork' => $vehiclesNeedingWork,
            'newArrivals7Days' => $newArrivals7Days,
            'recentlyUpdated24h' => $recentlyUpdated24h,
        ]);
    }

    /**
     * Get vehicle by serial number
     */
    public function getVehicleBySerial(int $serialNo): JsonResponse
    {
        $vehicle = Vehicle::where('serial_no', $serialNo)
            ->withCount(['purchases', 'sales'])
            ->firstOrFail();

        return response()->json($vehicle);
    }

    /**
     * Create vehicle
     */
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Handle file uploads
        if ($request->hasFile('images')) {
            $data['images'] = $request->file('images');
        }

        $vehicle = $this->vehicleService->createVehicle($data);

        return response()->json($vehicle, 201);
    }

    /**
     * Update vehicle
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $data = $request->validated();

        // Handle file uploads
        if ($request->hasFile('images')) {
            $data['images'] = $request->file('images');
        }

        $vehicle = $this->vehicleService->updateVehicle($vehicle, $data);

        return response()->json($vehicle);
    }

    /**
     * Delete vehicle
     */
    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $this->vehicleService->deleteVehicle($vehicle);

        return response()->json(['message' => 'Vehicle deleted successfully']);
    }

    /**
     * Format paginated response
     */
    private function paginatedResponse($paginator): JsonResponse
    {
        return response()->json([
            'docs' => $paginator->items(),
            'totalDocs' => $paginator->total(),
            'limit' => $paginator->perPage(),
            'page' => $paginator->currentPage(),
            'totalPages' => $paginator->lastPage(),
            'hasPrevPage' => $paginator->hasMorePages(),
            'hasNextPage' => $paginator->hasMorePages(),
            'prevPage' => $paginator->currentPage() > 1 ? $paginator->currentPage() - 1 : null,
            'nextPage' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
        ]);
    }
}

