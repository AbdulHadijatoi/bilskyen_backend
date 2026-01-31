<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\ModelYear;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\ConstantsCacheTrait;


/**
 * Admin Model Year Controller
 */
class AdminModelYearController extends Controller
{
    use ConstantsCacheTrait;
    public function index(Request $request): JsonResponse
    {
        $modelYears = ModelYear::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($modelYears);
    }

    public function show(int $id): JsonResponse
    {
        $modelYear = ModelYear::findOrFail($id);
        return $this->success($modelYear);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:model_years,name',
        ]);

        $modelYear = ModelYear::create($request->only(['name']));

        // Clear cache
        $this->clearConstantsCache('model_years');

        return $this->created($modelYear);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $modelYear = ModelYear::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:model_years,name,' . $id,
        ]);

        $modelYear->update($request->only(['name']));

        // Clear cache
        $this->clearConstantsCache('model_years');

        return $this->success($modelYear);
    }

    public function delete(int $id): JsonResponse
    {
        $modelYear = ModelYear::findOrFail($id);
        $modelYear->delete();

        // Clear cache
        $this->clearConstantsCache('model_years');

        return $this->noContent();
    }
}
