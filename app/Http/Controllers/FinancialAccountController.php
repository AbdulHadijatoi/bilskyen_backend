<?php

namespace App\Http\Controllers;

use App\Models\FinancialAccount;
use App\Services\FinancialAccountService;
use App\Helpers\FilterHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FinancialAccountController extends Controller
{
    public function __construct(
        private FinancialAccountService $financialAccountService
    ) {}

    /**
     * Get financial accounts list
     */
    public function getFinancialAccounts(Request $request): JsonResponse
    {
        $query = FinancialAccount::query();

        // Apply search
        $search = $request->input('search');
        $searchableFields = ['name', 'type', 'category'];
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
        $accounts = $query->withCount('transactionEntries')->paginate($perPage);

        // Add computed balance
        $accounts->getCollection()->transform(function ($account) {
            return [
                ...$account->toArray(),
                'balance' => $account->balance,
            ];
        });

        return $this->paginatedResponse($accounts);
    }

    /**
     * Get financial account by serial number
     */
    public function getFinancialAccountBySerial(int $serialNo): JsonResponse
    {
        $account = FinancialAccount::where('serial_no', $serialNo)
            ->withCount('transactionEntries')
            ->firstOrFail();

        return response()->json([
            ...$account->toArray(),
            'balance' => $account->balance,
        ]);
    }

    /**
     * Create financial account
     */
    public function create(Request $request): JsonResponse
    {
        $account = FinancialAccount::create($request->all());

        return response()->json($account, 201);
    }

    /**
     * Update financial account
     */
    public function update(Request $request, FinancialAccount $financialAccount): JsonResponse
    {
        $financialAccount->update($request->all());

        return response()->json($financialAccount);
    }

    /**
     * Delete financial account
     */
    public function delete(FinancialAccount $financialAccount): JsonResponse
    {
        if (!$this->financialAccountService->canDeleteAccount($financialAccount)) {
            return response()->json([
                'error' => 'Financial account cannot be deleted because it has associated transactions.',
            ], 400);
        }

        $financialAccount->delete();

        return response()->json(['message' => 'Financial account deleted successfully']);
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


