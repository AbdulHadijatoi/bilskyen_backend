<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Services\ExpenseService;
use App\Services\FileService;
use App\Helpers\FilterHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExpenseController extends Controller
{
    public function __construct(
        private ExpenseService $expenseService,
        private FileService $fileService
    ) {}

    /**
     * Get expenses list
     */
    public function getExpenses(Request $request): JsonResponse
    {
        $query = Expense::with(['vehicle', 'contact', 'paidFromFinancialAccount', 'transaction.entries.financialAccount']);

        // Apply filters
        $filters = json_decode($request->input('filters', '[]'), true);
        $joinOperator = $request->input('joinOperator', 'or');
        FilterHelper::applyFilters($query, $filters, $joinOperator);

        // Apply sorting
        $sort = json_decode($request->input('sort', '[]'), true);
        if (empty($sort)) {
            $sort = [['id' => 'date', 'desc' => true]];
        }
        FilterHelper::applySorting($query, $sort);

        // Paginate
        $perPage = $request->input('perPage', 10);
        $expenses = $query->paginate($perPage);

        // Add computed fields
        $expenses->getCollection()->transform(function ($expense) {
            return [
                ...$expense->toArray(),
                'payment_status' => $expense->payment_status,
            ];
        });

        return $this->paginatedResponse($expenses);
    }

    /**
     * Get expense by serial number
     */
    public function getExpenseBySerial(int $serialNo): JsonResponse
    {
        $expense = Expense::where('serial_no', $serialNo)
            ->with(['vehicle', 'contact', 'paidFromFinancialAccount', 'transaction.entries.financialAccount'])
            ->firstOrFail();

        return response()->json([
            ...$expense->toArray(),
            'payment_status' => $expense->payment_status,
        ]);
    }

    /**
     * Create expense
     */
    public function create(Request $request): JsonResponse
    {
        $data = $request->all();

        // Handle file uploads
        if ($request->hasFile('images')) {
            $this->fileService->validateFiles($request->file('images'));
            $data['images'] = $this->fileService->uploadFiles($request->file('images'));
        }

        $expense = $this->expenseService->createExpense($data);

        return response()->json($expense, 201);
    }

    /**
     * Update expense
     */
    public function update(Request $request, Expense $expense): JsonResponse
    {
        $data = $request->all();
        $oldImages = $expense->images ?? [];

        // Handle file uploads
        if ($request->hasFile('images')) {
            $this->fileService->validateFiles($request->file('images'));
            $data['images'] = $this->fileService->uploadFiles($request->file('images'));
            
            // Delete old images
            $this->fileService->deleteFiles($oldImages);
        }

        $expense = $this->expenseService->updateExpense($expense, $data);

        return response()->json($expense);
    }

    /**
     * Delete expense
     */
    public function delete(Expense $expense): JsonResponse
    {
        // Delete images
        if ($expense->images) {
            $this->fileService->deleteFiles($expense->images);
        }
        if ($expense->transaction && $expense->transaction->images) {
            $this->fileService->deleteFiles($expense->transaction->images);
        }

        $this->expenseService->deleteExpense($expense);

        return response()->json(['message' => 'Expense deleted successfully']);
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


