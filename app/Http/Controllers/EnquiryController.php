<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Helpers\FilterHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EnquiryController extends Controller
{
    /**
     * Get enquiries list
     */
    public function getEnquiries(Request $request): JsonResponse
    {
        $query = Enquiry::with(['contact', 'user', 'vehicle']);

        // Apply search
        $search = $request->input('search');
        $searchableFields = ['subject', 'message', 'type', 'status', 'source'];
        FilterHelper::applySearch($query, $search, $searchableFields);

        // Apply filters
        $filters = json_decode($request->input('filters', '[]'), true);
        $joinOperator = $request->input('joinOperator', 'or');
        FilterHelper::applyFilters($query, $filters, $joinOperator);

        // Apply sorting
        $sort = json_decode($request->input('sort', '[]'), true);
        if (empty($sort)) {
            $sort = [['id' => 'created_at', 'desc' => true]];
        }
        FilterHelper::applySorting($query, $sort);

        // Paginate
        $perPage = $request->input('perPage', 10);
        $enquiries = $query->paginate($perPage);

        return $this->paginatedResponse($enquiries);
    }

    /**
     * Get enquiry by serial number
     */
    public function getEnquiryBySerial(int $serialNo): JsonResponse
    {
        $enquiry = Enquiry::where('serial_no', $serialNo)
            ->with(['contact', 'user', 'vehicle'])
            ->firstOrFail();

        return response()->json($enquiry);
    }

    /**
     * Create enquiry
     */
    public function create(Request $request): JsonResponse
    {
        $enquiry = Enquiry::create($request->all());

        return response()->json($enquiry, 201);
    }

    /**
     * Update enquiry
     */
    public function update(Request $request, Enquiry $enquiry): JsonResponse
    {
        $enquiry->update($request->all());

        return response()->json($enquiry);
    }

    /**
     * Delete enquiry
     */
    public function delete(Enquiry $enquiry): JsonResponse
    {
        $enquiry->delete();

        return response()->json(['message' => 'Enquiry deleted successfully']);
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


