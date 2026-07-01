<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLocationController extends Controller
{
    private function validationRules(bool $isCreate): array
    {
        $required = $isCreate ? 'required' : 'sometimes';

        return [
            'city' => [$required, 'string', 'max:100'],
            'postcode' => [$required, 'string', 'max:10'],
            'region' => [$required, 'string', 'max:100'],
            'country_code' => [$required, 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'latitude' => [$required, 'numeric', 'between:-90,90'],
            'longitude' => [$required, 'numeric', 'between:-180,180'],
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $query = Location::query()->orderBy('city')->orderBy('postcode');

        $q = trim((string) $request->get('q', ''));
        if ($q !== '') {
            $term = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(function ($sub) use ($term) {
                $sub->where('city', 'like', $term)
                    ->orWhere('postcode', 'like', $term)
                    ->orWhere('region', 'like', $term)
                    ->orWhere('country_code', 'like', $term);
            });
        }

        $locations = $query->paginate($request->get('limit', 15));

        return $this->paginated($locations);
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->validate($this->validationRules(true));
        $data['country_code'] = strtoupper($data['country_code']);

        $location = Location::create($data);

        return $this->created($location);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $location = Location::findOrFail($id);

        $data = $request->validate($this->validationRules(false));

        if (array_key_exists('country_code', $data)) {
            $data['country_code'] = strtoupper($data['country_code']);
        }

        $location->update($data);

        return $this->success($location->fresh());
    }

    public function delete(int $id): JsonResponse
    {
        $location = Location::findOrFail($id);
        $location->delete();

        return $this->noContent();
    }
}
