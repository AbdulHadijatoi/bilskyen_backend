<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\Condition;
use App\Traits\ConstantsCacheTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


/**
 * Admin Condition Controller
 */
class AdminConditionController extends Controller
{
    use ConstantsCacheTrait;
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('conditions', 'name')->whereNull('deleted_at'),
            ],
        ]);

        $condition = Condition::create($request->only(['name']));

        // Clear cache
        $this->clearConstantsCache('conditions');

        return $this->created($condition);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $condition = Condition::findOrFail($id);

        $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('conditions', 'name')->ignore($id)->whereNull('deleted_at'),
            ],
        ]);

        $condition->update($request->only(['name']));

        // Clear cache
        $this->clearConstantsCache('conditions');

        return $this->success($condition);
    }

    public function delete(int $id): JsonResponse
    {
        $condition = Condition::findOrFail($id);
        $condition->delete();

        // Clear cache
        $this->clearConstantsCache('conditions');

        return $this->noContent();
    }
}
