<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\BodyType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Admin Body Type Controller
 */
class AdminBodyTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $bodyTypes = BodyType::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($bodyTypes);
    }

    public function show(int $id): JsonResponse
    {
        $bodyType = BodyType::findOrFail($id);
        return $this->success($bodyType);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:body_types,name',
        ]);

        $bodyType = BodyType::create($request->only(['name']));

        // Clear cache
        Cache::forget('constants_body_types');

        return $this->created($bodyType);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $bodyType = BodyType::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:body_types,name,' . $id,
        ]);

        $bodyType->update($request->only(['name']));

        // Clear cache
        Cache::forget('constants_body_types');

        return $this->success($bodyType);
    }

    public function delete(int $id): JsonResponse
    {
        $bodyType = BodyType::findOrFail($id);
        $bodyType->delete();

        // Clear cache
        Cache::forget('constants_body_types');

        return $this->noContent();
    }
}
