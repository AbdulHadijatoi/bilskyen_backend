<?php

namespace App\Http\Controllers;

use App\Models\Dealer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin Dealer Controller
 */
class AdminDealerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $dealers = Dealer::query()
            ->paginate($request->get('limit', 15));

        return $this->paginated($dealers);
    }

    public function show(int $id): JsonResponse
    {
        $dealer = Dealer::with('users', 'vehicles')->findOrFail($id);
        return $this->success($dealer);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'cvr' => 'required|string|max:20|unique:dealers',
            'address' => 'sometimes|string',
            'city' => 'sometimes|string',
            'postcode' => 'sometimes|string',
            'country_code' => 'sometimes|string|max:2',
        ]);

        $dealer = Dealer::create($request->only(['cvr', 'address', 'city', 'postcode', 'country_code']));

        return $this->created($dealer);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $dealer = Dealer::findOrFail($id);

        $request->validate([
            'cvr' => 'sometimes|string|max:20|unique:dealers,cvr,' . $id,
            'address' => 'sometimes|string',
            'city' => 'sometimes|string',
            'postcode' => 'sometimes|string',
            'country_code' => 'sometimes|string|max:2',
        ]);

        $dealer->update($request->only(['cvr', 'address', 'city', 'postcode', 'country_code']));

        return $this->success($dealer);
    }

    public function destroy(int $id): JsonResponse
    {
        $dealer = Dealer::findOrFail($id);
        $dealer->delete(); // Soft delete

        return $this->noContent();
    }
}

