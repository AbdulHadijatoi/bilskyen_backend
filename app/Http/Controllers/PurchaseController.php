<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Services\PurchaseService;
use App\Services\FileService;
use App\Services\NotificationService;
use App\Helpers\FilterHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PurchaseController extends Controller
{
    public function __construct(
        private PurchaseService $purchaseService,
        private FileService $fileService,
        private NotificationService $notificationService
    ) {}

    /**
     * Get purchases list
     */
    public function getPurchases(Request $request): JsonResponse
    {
        $query = Purchase::with(['vehicle', 'contact', 'paidFromFinancialAccount', 'transaction.entries.financialAccount']);

        // Apply filters
        $filters = json_decode($request->input('filters', '[]'), true);
        $joinOperator = $request->input('joinOperator', 'or');
        FilterHelper::applyFilters($query, $filters, $joinOperator);

        // Apply sorting
        $sort = json_decode($request->input('sort', '[]'), true);
        if (empty($sort)) {
            $sort = [['id' => 'purchase_date', 'desc' => true]];
        }
        FilterHelper::applySorting($query, $sort);

        // Paginate
        $perPage = $request->input('perPage', 10);
        $purchases = $query->paginate($perPage);

        // Add computed fields
        $purchases->getCollection()->transform(function ($purchase) {
            return [
                ...$purchase->toArray(),
                'purchase_price' => $purchase->purchase_price,
                'paid_amount' => $purchase->paid_amount,
                'payment_status' => $purchase->payment_status,
            ];
        });

        return $this->paginatedResponse($purchases);
    }

    /**
     * Get purchase by serial number
     */
    public function getPurchaseBySerial(int $serialNo): JsonResponse
    {
        $purchase = Purchase::where('serial_no', $serialNo)
            ->with(['vehicle', 'contact', 'paidFromFinancialAccount', 'transaction.entries.financialAccount'])
            ->firstOrFail();

        return response()->json([
            ...$purchase->toArray(),
            'purchase_price' => $purchase->purchase_price,
            'paid_amount' => $purchase->paid_amount,
            'payment_status' => $purchase->payment_status,
        ]);
    }

    /**
     * Create purchase
     */
    public function create(Request $request): JsonResponse
    {
        $data = $request->all();

        // Handle file uploads
        if ($request->hasFile('images')) {
            $this->fileService->validateFiles($request->file('images'));
            $data['images'] = $this->fileService->uploadFiles($request->file('images'));
        }

        $purchase = $this->purchaseService->createPurchase($data);

        // Create notifications
        $this->notificationService->createPurchaseNotifications(
            $purchase,
            $purchase->vehicle,
            $purchase->contact
        );

        return response()->json($purchase, 201);
    }

    /**
     * Update purchase
     */
    public function update(Request $request, Purchase $purchase): JsonResponse
    {
        $data = $request->all();
        $oldImages = $purchase->images ?? [];

        // Handle file uploads
        if ($request->hasFile('images')) {
            $this->fileService->validateFiles($request->file('images'));
            $data['images'] = $this->fileService->uploadFiles($request->file('images'));
            
            // Delete old images
            $this->fileService->deleteFiles($oldImages);
        }

        $purchase = $this->purchaseService->updatePurchase($purchase, $data);

        // Create notifications
        $this->notificationService->createPurchaseNotifications(
            $purchase,
            $purchase->vehicle,
            $purchase->contact,
            true
        );

        return response()->json($purchase);
    }

    /**
     * Delete purchase
     */
    public function delete(Purchase $purchase): JsonResponse
    {
        // Delete images
        if ($purchase->images) {
            $this->fileService->deleteFiles($purchase->images);
        }
        if ($purchase->transaction && $purchase->transaction->images) {
            $this->fileService->deleteFiles($purchase->transaction->images);
        }

        $this->purchaseService->deletePurchase($purchase);

        return response()->json(['message' => 'Purchase deleted successfully']);
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

