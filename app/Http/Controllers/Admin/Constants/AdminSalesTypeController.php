<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\SalesType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


/**
 * Admin Sales Type Controller
 */
class AdminSalesTypeController extends Controller
{
    use ConstantsCacheHelper;
    public function index(Request $request): JsonResponse
    {
        $salesTypes = SalesType::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($salesTypes);
    }

    public function show(int $id): JsonResponse
    {
        $salesType = SalesType::findOrFail($id);
        return $this->success($salesType);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:sales_types,name',
        ]);

        $salesType = SalesType::create($request->only(['name']));

        // Clear cache
        $this->clearConstantsCache('sales_types');

        return $this->created($salesType);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $salesType = SalesType::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:sales_types,name,' . $id,
        ]);

        $salesType->update($request->only(['name']));

        // Clear cache
        $this->clearConstantsCache('sales_types');

        return $this->success($salesType);
    }

    public function delete(int $id): JsonResponse
    {
        $salesType = SalesType::findOrFail($id);
        $salesType->delete();

        // Clear cache
        $this->clearConstantsCache('sales_types');

        return $this->noContent();
    }
}
