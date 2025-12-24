<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin Feature Controller
 */
class AdminFeatureController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $features = Feature::paginate($request->get('limit', 15));

        return $this->paginated($features);
    }

    public function show(int $id): JsonResponse
    {
        $feature = Feature::findOrFail($id);
        return $this->success($feature);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:features',
            'description' => 'sometimes|string',
        ]);

        $feature = Feature::create($request->only(['name', 'slug', 'description']));

        return $this->created($feature);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $feature = Feature::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:features,slug,' . $id,
            'description' => 'sometimes|string',
        ]);

        $feature->update($request->only(['name', 'slug', 'description']));

        return $this->success($feature);
    }

    public function destroy(int $id): JsonResponse
    {
        $feature = Feature::findOrFail($id);
        $feature->delete();

        return $this->noContent();
    }
}

