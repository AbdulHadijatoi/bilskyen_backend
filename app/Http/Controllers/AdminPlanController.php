<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin Plan Controller
 */
class AdminPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $plans = Plan::with('features')->paginate($request->get('limit', 15));

        return $this->paginated($plans);
    }

    public function show(int $id): JsonResponse
    {
        $plan = Plan::with('features')->findOrFail($id);
        return $this->success($plan);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans',
            'description' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $plan = Plan::create($request->only(['name', 'slug', 'description', 'is_active']));

        return $this->created($plan);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:plans,slug,' . $id,
            'description' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $plan->update($request->only(['name', 'slug', 'description', 'is_active']));

        return $this->success($plan);
    }

    public function destroy(int $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);
        $plan->delete(); // Soft delete

        return $this->noContent();
    }

    public function getFeatures(int $id): JsonResponse
    {
        $plan = Plan::with('features')->findOrFail($id);
        return $this->success($plan->features);
    }

    public function assignFeature(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'feature_id' => 'required|exists:features,id',
            'value' => 'sometimes',
        ]);

        $plan = Plan::findOrFail($id);
        $plan->features()->syncWithoutDetaching([
            $request->feature_id => ['value' => $request->value ?? null]
        ]);

        return $this->success(['message' => 'Feature assigned successfully']);
    }

    public function removeFeature(int $id, int $featureId): JsonResponse
    {
        $plan = Plan::findOrFail($id);
        $plan->features()->detach($featureId);

        return $this->noContent();
    }
}

