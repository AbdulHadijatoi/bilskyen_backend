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
            'key' => 'required|string|max:100|unique:features',
            'feature_value_type_id' => 'required|exists:feature_value_types,id',
            'description' => 'required|string|max:255',
        ]);

        $feature = Feature::create([
            'key' => $request->key,
            'feature_value_type_id' => $request->feature_value_type_id,
            'description' => $request->description,
            'created_at' => now(),
        ]);

        return $this->created($feature);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $feature = Feature::findOrFail($id);

        $request->validate([
            'key' => 'sometimes|string|max:100|unique:features,key,' . $id,
            'feature_value_type_id' => 'sometimes|exists:feature_value_types,id',
            'description' => 'sometimes|string|max:255',
            'label_en' => 'sometimes|nullable|string|max:255',
            'label_da' => 'sometimes|nullable|string|max:255',
        ]);

        $feature->update($request->only([
            'key',
            'feature_value_type_id',
            'description',
            'label_en',
            'label_da',
        ]));

        return $this->success($feature);
    }

    public function destroy(int $id): JsonResponse
    {
        $feature = Feature::findOrFail($id);
        $feature->delete();

        return $this->noContent();
    }
}

