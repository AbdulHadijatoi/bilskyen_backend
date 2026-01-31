<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\ConstantsCacheTrait;


/**
 * Admin Type Controller
 */
class AdminTypeController extends Controller
{
    use ConstantsCacheTrait;
    public function index(Request $request): JsonResponse
    {
        $types = Type::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($types);
    }

    public function show(int $id): JsonResponse
    {
        $type = Type::findOrFail($id);
        return $this->success($type);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:types,name',
        ]);

        $type = Type::create($request->only(['name']));

        // Clear cache
        $this->clearConstantsCache('types');

        return $this->created($type);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $type = Type::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:types,name,' . $id,
        ]);

        $type->update($request->only(['name']));

        // Clear cache
        $this->clearConstantsCache('types');

        return $this->success($type);
    }

    public function delete(int $id): JsonResponse
    {
        $type = Type::findOrFail($id);
        $type->delete();

        // Clear cache
        $this->clearConstantsCache('types');

        return $this->noContent();
    }
}
