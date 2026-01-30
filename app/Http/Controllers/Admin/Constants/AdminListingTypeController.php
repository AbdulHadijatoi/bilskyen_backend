<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\ListingType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


/**
 * Admin Listing Type Controller
 */
class AdminListingTypeController extends Controller
{
    use ConstantsCacheHelper;
{
    public function index(Request $request): JsonResponse
    {
        $listingTypes = ListingType::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($listingTypes);
    }

    public function show(int $id): JsonResponse
    {
        $listingType = ListingType::findOrFail($id);
        return $this->success($listingType);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:listing_types,name',
        ]);

        $listingType = ListingType::create($request->only(['name']));

        // Clear cache
        Cache::forget('constants_listing_types');

        return $this->created($listingType);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $listingType = ListingType::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:listing_types,name,' . $id,
        ]);

        $listingType->update($request->only(['name']));

        // Clear cache
        Cache::forget('constants_listing_types');

        return $this->success($listingType);
    }

    public function delete(int $id): JsonResponse
    {
        $listingType = ListingType::findOrFail($id);
        $listingType->delete();

        // Clear cache
        Cache::forget('constants_listing_types');

        return $this->noContent();
    }
}
