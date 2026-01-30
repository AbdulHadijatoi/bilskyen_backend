<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\Euronom;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Admin Euronom Controller
 */
class AdminEuronomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $euronoms = Euronom::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($euronoms);
    }

    public function show(int $id): JsonResponse
    {
        $euronom = Euronom::findOrFail($id);
        return $this->success($euronom);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:euronorms,name',
        ]);

        $euronom = Euronom::create($request->only(['name']));

        // Clear cache
        Cache::forget('constants_euronorms');

        return $this->created($euronom);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $euronom = Euronom::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:euronorms,name,' . $id,
        ]);

        $euronom->update($request->only(['name']));

        // Clear cache
        Cache::forget('constants_euronorms');

        return $this->success($euronom);
    }

    public function delete(int $id): JsonResponse
    {
        $euronom = Euronom::findOrFail($id);
        $euronom->delete();

        // Clear cache
        Cache::forget('constants_euronorms');

        return $this->noContent();
    }
}
