<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\SaleService;
use App\Services\FileService;
use App\Services\NotificationService;
use App\Helpers\FilterHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SaleController extends Controller
{
    public function __construct(
        private SaleService $saleService,
        private FileService $fileService,
        private NotificationService $notificationService
    ) {}

    /**
     * Get sales list
     */
    public function getSales(Request $request): JsonResponse
    {
        $query = Sale::with(['vehicle', 'contact', 'receivedToFinancialAccount', 'transaction.entries.financialAccount']);

        // Apply filters
        $filters = json_decode($request->input('filters', '[]'), true);
        $joinOperator = $request->input('joinOperator', 'or');
        FilterHelper::applyFilters($query, $filters, $joinOperator);

        // Apply sorting
        $sort = json_decode($request->input('sort', '[]'), true);
        if (empty($sort)) {
            $sort = [['id' => 'sale_date', 'desc' => true]];
        }
        FilterHelper::applySorting($query, $sort);

        // Paginate
        $perPage = $request->input('perPage', 10);
        $sales = $query->paginate($perPage);

        // Add computed fields
        $sales->getCollection()->transform(function ($sale) {
            return [
                ...$sale->toArray(),
                'sale_price' => $sale->sale_price,
                'received_amount' => $sale->received_amount,
                'outstanding_amount' => $sale->outstanding_amount,
                'payment_status' => $sale->payment_status,
            ];
        });

        return $this->paginatedResponse($sales);
    }

    /**
     * Get sales overview statistics
     */
    public function getSalesOverview(): JsonResponse
    {
        $totalSales = Sale::count();
        $totalRevenue = Sale::with('transaction.entries')->get()->sum(function ($sale) {
            return $sale->sale_price;
        });
        $averageSaleValue = $totalSales > 0 ? $totalRevenue / $totalSales : 0;
        
        $salesThisMonth = Sale::whereMonth('sale_date', now()->month)
            ->whereYear('sale_date', now()->year)
            ->count();
        
        $salesThisQuarter = Sale::whereBetween('sale_date', [
            now()->startOfQuarter(),
            now()->endOfQuarter()
        ])->count();

        $revenueThisMonth = Sale::whereMonth('sale_date', now()->month)
            ->whereYear('sale_date', now()->year)
            ->with('transaction.entries')
            ->get()
            ->sum(function ($sale) {
                return $sale->sale_price;
            });

        $revenueThisQuarter = Sale::whereBetween('sale_date', [
            now()->startOfQuarter(),
            now()->endOfQuarter()
        ])
        ->with('transaction.entries')
        ->get()
        ->sum(function ($sale) {
            return $sale->sale_price;
        });

        $salesLast7Days = Sale::where('sale_date', '>=', now()->subDays(7))->count();
        $salesLast24Hours = Sale::where('sale_date', '>=', now()->subDay())->count();

        // Calculate average days to sell
        $averageDaysToSell = Sale::selectRaw('AVG(DATEDIFF(sale_date, (SELECT inventory_date FROM vehicles WHERE vehicles.id = sales.vehicle_id))) as avg_days')
            ->value('avg_days') ?? 0;

        // Top selling month
        $topSellingMonth = Sale::selectRaw('DATE_FORMAT(sale_date, "%M %Y") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('count', 'desc')
            ->first();

        return response()->json([
            'totalSales' => $totalSales,
            'totalRevenue' => round($totalRevenue, 2),
            'averageSaleValue' => round($averageSaleValue, 2),
            'salesThisMonth' => $salesThisMonth,
            'salesThisQuarter' => $salesThisQuarter,
            'revenueThisMonth' => round($revenueThisMonth, 2),
            'revenueThisQuarter' => round($revenueThisQuarter, 2),
            'salesLast7Days' => $salesLast7Days,
            'salesLast24Hours' => $salesLast24Hours,
            'averageDaysToSell' => round($averageDaysToSell, 2),
            'topSellingMonth' => $topSellingMonth ? $topSellingMonth->month : null,
        ]);
    }

    /**
     * Get sale by serial number
     */
    public function getSaleBySerial(int $serialNo): JsonResponse
    {
        $sale = Sale::where('serial_no', $serialNo)
            ->with(['vehicle', 'contact', 'receivedToFinancialAccount', 'transaction.entries.financialAccount'])
            ->firstOrFail();

        return response()->json([
            ...$sale->toArray(),
            'sale_price' => $sale->sale_price,
            'received_amount' => $sale->received_amount,
            'outstanding_amount' => $sale->outstanding_amount,
            'payment_status' => $sale->payment_status,
        ]);
    }

    /**
     * Create sale
     */
    public function create(Request $request): JsonResponse
    {
        $data = $request->all();

        // Handle file uploads
        if ($request->hasFile('images')) {
            $this->fileService->validateFiles($request->file('images'));
            $data['images'] = $this->fileService->uploadFiles($request->file('images'));
        }

        $sale = $this->saleService->createSale($data);

        // Create notifications
        $this->notificationService->createSaleNotifications(
            $sale,
            $sale->vehicle,
            $sale->contact
        );

        return response()->json($sale, 201);
    }

    /**
     * Update sale
     */
    public function update(Request $request, Sale $sale): JsonResponse
    {
        $data = $request->all();
        $oldImages = $sale->images ?? [];

        // Handle file uploads
        if ($request->hasFile('images')) {
            $this->fileService->validateFiles($request->file('images'));
            $data['images'] = $this->fileService->uploadFiles($request->file('images'));
            
            // Delete old images
            $this->fileService->deleteFiles($oldImages);
        }

        $sale = $this->saleService->updateSale($sale, $data);

        // Create notifications
        $this->notificationService->createSaleNotifications(
            $sale,
            $sale->vehicle,
            $sale->contact,
            true
        );

        return response()->json($sale);
    }

    /**
     * Delete sale
     */
    public function delete(Sale $sale): JsonResponse
    {
        // Delete images
        if ($sale->images) {
            $this->fileService->deleteFiles($sale->images);
        }
        if ($sale->transaction && $sale->transaction->images) {
            $this->fileService->deleteFiles($sale->transaction->images);
        }

        $this->saleService->deleteSale($sale);

        return response()->json(['message' => 'Sale deleted successfully']);
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
            'hasPrevPage' => $paginator->currentPage() > 1,
            'hasNextPage' => $paginator->hasMorePages(),
            'prevPage' => $paginator->currentPage() > 1 ? $paginator->currentPage() - 1 : null,
            'nextPage' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
        ]);
    }
}

