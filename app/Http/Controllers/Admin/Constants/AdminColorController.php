<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\Color;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


/**
 * Admin Color Controller
 */
class AdminColorController extends Controller
{
    use ConstantsCacheHelper;
    public function index(Request $request): JsonResponse
    {
        $colors = Color::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($colors);
    }

    public function show(int $id): JsonResponse
    {
        $color = Color::findOrFail($id);
        return $this->success($color);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:colors,name',
        ]);

        $color = Color::create($request->only(['name']));

        // Clear cache
        $this->clearConstantsCache('colors');

        return $this->created($color);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $color = Color::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:colors,name,' . $id,
        ]);

        $color->update($request->only(['name']));

        // Clear cache
        $this->clearConstantsCache('colors');

        return $this->success($color);
    }

    public function delete(int $id): JsonResponse
    {
        $color = Color::findOrFail($id);
        $color->delete();

        // Clear cache
        $this->clearConstantsCache('colors');

        return $this->noContent();
    }
}
