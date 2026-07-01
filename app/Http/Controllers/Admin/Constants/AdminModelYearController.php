<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\DmrFactVehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Admin model-year listing: years are distinct `model_aar` values from DMR (read-only).
 */
class AdminModelYearController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $limit = max(1, (int) $request->get('limit', 15));
        $page = max(1, (int) $request->get('page', 1));

        $items = DmrFactVehicle::query()
            ->whereNotNull('model_aar')
            ->distinct()
            ->orderByDesc('model_aar')
            ->pluck('model_aar')
            ->values()
            ->map(fn ($y) => ['id' => (int) $y, 'name' => (string) $y])
            ->all();

        $total = count($items);
        $offset = ($page - 1) * $limit;
        $slice = array_slice($items, $offset, $limit);

        $paginator = new LengthAwarePaginator(
            $slice,
            $total,
            $limit,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return $this->paginated($paginator);
    }

    public function show(int $id): JsonResponse
    {
        if ($id < 1950 || $id > 2100 || ! DmrFactVehicle::modelYearValueExists($id)) {
            return $this->error(__('messages.api.resource_not_found'), [], 404, 'NOT_FOUND');
        }

        return $this->success(['id' => $id, 'name' => (string) $id]);
    }

    public function create(Request $request): JsonResponse
    {
        return $this->error(
            __('messages.api.model_year_cannot_create_api'),
            [],
            405,
            'NOT_ALLOWED'
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return $this->error(
            __('messages.api.model_year_cannot_update_api'),
            [],
            405,
            'NOT_ALLOWED'
        );
    }

    public function delete(int $id): JsonResponse
    {
        return $this->error(
            __('messages.api.model_year_cannot_delete_api'),
            [],
            405,
            'NOT_ALLOWED'
        );
    }
}
