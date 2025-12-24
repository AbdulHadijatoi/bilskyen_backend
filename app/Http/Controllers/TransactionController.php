<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TransactionService;
use App\Services\FileService;
use App\Helpers\FilterHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function __construct(
        private TransactionService $transactionService,
        private FileService $fileService
    ) {}

    /**
     * Get general ledger entries (unwound transaction entries)
     */
    public function getGeneralLedgerEntries(Request $request): JsonResponse
    {
        $query = \App\Models\TransactionEntry::with(['transaction', 'financialAccount']);

        // Apply search
        $search = $request->input('search');
        if ($search) {
            $query->whereHas('transaction', function ($q) use ($search) {
                $q->where('date', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('narration', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%");
            })
            ->orWhere('type', 'like', "%{$search}%")
            ->orWhere('amount', 'like', "%{$search}%");
        }

        // Apply filters
        $filters = json_decode($request->input('filters', '[]'), true);
        $joinOperator = $request->input('joinOperator', 'or');
        FilterHelper::applyFilters($query, $filters, $joinOperator);

        // Apply sorting
        $sort = json_decode($request->input('sort', '[]'), true);
        if (empty($sort)) {
            $sort = [['id' => 'date', 'desc' => false]];
        }
        FilterHelper::applySorting($query, $sort);

        // Paginate
        $perPage = $request->input('perPage', 10);
        $entries = $query->paginate($perPage);

        // Transform to include transaction fields
        $entries->getCollection()->transform(function ($entry) {
            return [
                'id' => $entry->id,
                'transaction_id' => $entry->transaction_id,
                'financial_account_id' => $entry->financial_account_id,
                'financial_account' => $entry->financialAccount,
                'amount' => $entry->amount,
                'type' => $entry->type,
                'description' => $entry->description,
                'date' => $entry->transaction->date,
                'transaction_type' => $entry->transaction->type,
                'narration' => $entry->transaction->narration,
                'remarks' => $entry->transaction->remarks,
                'entry_type' => $entry->type,
                'created_at' => $entry->created_at,
            ];
        });

        return $this->paginatedResponse($entries);
    }

    /**
     * Get transaction by ID
     */
    public function getTransaction(int $transactionId): JsonResponse
    {
        $transaction = Transaction::with(['entries.financialAccount', 'purchase', 'sale'])
            ->findOrFail($transactionId);

        return response()->json([
            'transaction' => $transaction,
            'relatedPurchase' => $transaction->purchase,
            'relatedSale' => $transaction->sale,
        ]);
    }

    /**
     * Get transaction by serial number
     */
    public function getTransactionBySerial(int $serialNo): JsonResponse
    {
        $transaction = Transaction::where('serial_no', $serialNo)
            ->with(['entries.financialAccount'])
            ->firstOrFail();

        return response()->json($transaction);
    }

    /**
     * Create transaction
     */
    public function create(Request $request): JsonResponse
    {
        $data = $request->all();
        $entries = $data['entries'] ?? [];
        unset($data['entries']);

        // Handle file uploads
        if ($request->hasFile('images')) {
            $this->fileService->validateFiles($request->file('images'));
            $data['images'] = $this->fileService->uploadFiles($request->file('images'));
        }

        $transaction = $this->transactionService->createTransaction($data, $entries);

        return response()->json($transaction, 201);
    }

    /**
     * Update transaction
     */
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        $data = $request->all();
        $entries = $data['entries'] ?? [];
        unset($data['entries']);
        $oldImages = $transaction->images ?? [];

        // Handle file uploads
        if ($request->hasFile('images')) {
            $this->fileService->validateFiles($request->file('images'));
            $data['images'] = $this->fileService->uploadFiles($request->file('images'));
            
            // Delete old images
            $this->fileService->deleteFiles($oldImages);
        }

        $transaction = $this->transactionService->updateTransaction($transaction, $data, $entries);

        return response()->json($transaction);
    }

    /**
     * Delete transaction
     */
    public function delete(Transaction $transaction): JsonResponse
    {
        // Delete images
        if ($transaction->images) {
            $this->fileService->deleteFiles($transaction->images);
        }

        // Delete related purchase/sale images
        if ($transaction->purchase && $transaction->purchase->images) {
            $this->fileService->deleteFiles($transaction->purchase->images);
        }
        if ($transaction->sale && $transaction->sale->images) {
            $this->fileService->deleteFiles($transaction->sale->images);
        }

        $this->transactionService->deleteTransaction($transaction);

        return response()->json(['message' => 'Transaction deleted successfully']);
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


