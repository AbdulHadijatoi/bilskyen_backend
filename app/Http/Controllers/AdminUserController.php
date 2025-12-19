<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\FilterHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    /**
     * Get users list
     */
    public function getUsers(Request $request): JsonResponse
    {
        $query = User::query();

        // Apply filters
        $filters = json_decode($request->input('filters', '[]'), true);
        $joinOperator = $request->input('joinOperator', 'or');
        FilterHelper::applyFilters($query, $filters, $joinOperator);

        // Apply sorting
        $sort = json_decode($request->input('sort', '[]'), true);
        FilterHelper::applySorting($query, $sort);

        // Paginate
        $perPage = $request->input('perPage', 10);
        $users = $query->paginate($perPage);

        // Add computed status field
        $users->getCollection()->transform(function ($user) {
            return [
                ...$user->toArray(),
                'status' => $user->status,
            ];
        });

        return $this->paginatedResponse($users);
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

