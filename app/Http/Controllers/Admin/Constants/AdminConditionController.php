<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\Condition;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Admin Condition Controller
 */
class AdminConditionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $conditions = Condition::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($conditions);
    }

    public function show(int $id): JsonResponse
    {
        $condition = Condition::findOrFail($id);
        return $this->success($condition);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:conditions,name',
        ]);

        $condition = Condition::create($request->only(['name']));

        // Clear cache
        Cache::forget('constants_conditions');

        return $this->created($condition);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $condition = Condition::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:conditions,name,' . $id,
        ]);

        $condition->update($request->only(['name']));

        // Clear cache
        Cache::forget('constants_conditions');

        return $this->success($condition);
    }

    public function delete(int $id): JsonResponse
    {
        $condition = Condition::findOrFail($id);
        $condition->delete();

        // Clear cache
        Cache::forget('constants_conditions');

        return $this->noContent();
    }
}
